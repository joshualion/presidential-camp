<?php

namespace App\Services\Sms;

use App\Models\SmsBatch;
use App\Models\SmsMessage;
use App\Models\SmsStatusEvent;
use Illuminate\Support\Facades\DB;

class SmsSettlementSyncService
{
    public function __construct(private PortalSmsClient $portal, private SmsWalletService $wallets, private SmsAuditService $audit) {}

    public function sync(SmsBatch $batch): array
    {
        if (!$batch->portal_batch_reference) return ['ok' => false, 'status' => 422, 'code' => 'batch_not_submitted', 'message' => 'This batch has no portal reference.', 'data' => []];
        $batchResponse = $this->portal->getBatch($batch->portal_batch_reference);
        if (!($batchResponse['ok'] ?? false)) return $batchResponse;
        $messagesResponse = $this->allMessages($batch->portal_batch_reference);
        if (!($messagesResponse['ok'] ?? false)) return $messagesResponse;
        $remote = (array) $batchResponse['data'];
        $messages = data_get($messagesResponse, 'data.messages', data_get($messagesResponse, 'data.data', $messagesResponse['data']));
        if (!is_array($messages)) $messages = [];

        try {
        DB::transaction(function () use ($batch, $remote, $messages) {
            $locked = SmsBatch::lockForUpdate()->findOrFail($batch->id);
            foreach ($messages as $remoteMessage) $this->mirrorMessage($locked, (array) $remoteMessage);
            $portalDebited = (string) (data_get($remote, 'debited_amount') ?? $locked->debited_amount);
            $portalRefunded = (string) (data_get($remote, 'refunded_amount') ?? $locked->refunded_amount);
            $portalReversed = (string) (data_get($remote, 'reversed_amount') ?? $locked->reversed_amount);
            $portalReleased = (string) (data_get($remote, 'released_amount') ?? $locked->released_amount);
            $portalCurrency = strtoupper((string) (data_get($remote, 'currency') ?? $locked->currency ?? $locked->wallet?->currency));
            if ($portalCurrency === '' || $portalCurrency !== strtoupper((string) $locked->wallet?->currency)) {
                throw new \RuntimeException('invalid_portal_settlement_amount');
            }
            if ($this->wallets->compare($portalDebited, (string) $locked->debited_amount) < 0
                || $this->wallets->compare($portalRefunded, (string) $locked->refunded_amount) < 0
                || $this->wallets->compare($portalReversed, (string) $locked->reversed_amount) < 0
                || $this->wallets->compare($portalReleased, (string) $locked->released_amount) < 0
                || $this->wallets->compare($portalRefunded, $portalDebited) > 0) {
                throw new \RuntimeException('invalid_portal_settlement_amount');
            }
            $debitDelta = $this->wallets->subtract($portalDebited, (string) $locked->debited_amount);
            $refundDelta = $this->wallets->subtract($portalRefunded, (string) $locked->refunded_amount);
            $reservation = $locked->reservation;
            if (!$reservation && ($this->wallets->compare($debitDelta, '0') > 0 || $this->wallets->compare($portalReleased, '0') > 0)) {
                throw new \RuntimeException('invalid_portal_settlement_amount');
            }
            if ($this->wallets->compare($debitDelta, '0') > 0) $this->wallets->debitReservation($reservation, $debitDelta, 'portal-debit:'.$locked->portal_batch_reference.':'.$portalDebited, ['portal_evidence' => 'reported_debited_amount']);
            if ($this->wallets->compare($refundDelta, '0') > 0) $this->wallets->refund($locked->wallet, $refundDelta, 'portal-refund:'.$locked->portal_batch_reference.':'.$portalRefunded, ['portal_evidence' => 'reported_refunded_amount']);
            $alreadyReleased = (string) $locked->released_amount;
            $releaseDelta = $this->wallets->subtract($portalReleased, $alreadyReleased);
            if ($this->wallets->compare($releaseDelta, '0') > 0) $this->wallets->releaseReservation($reservation, $releaseDelta, 'sms_reversal', 'portal-release:'.$locked->portal_batch_reference.':'.$portalReleased, ['portal_evidence' => 'reported_released_amount']);
            $locked->update([
                'debited_amount' => $portalDebited, 'refunded_amount' => $portalRefunded,
                'reversed_amount' => $portalReversed, 'released_amount' => $portalReleased,
                'settlement_status' => data_get($remote, 'settlement_status', $locked->settlement_status),
                'currency' => $portalCurrency,
                'status' => data_get($remote, 'status', $locked->status), 'portal_status' => data_get($remote, 'status', $locked->portal_status),
                'delivery_summary' => data_get($remote, 'counts', $locked->delivery_summary),
                'billing_review_count' => (int) (data_get($remote, 'billing_review_count') ?? collect($messages)->where('billing_review_required', true)->count()),
                'last_synced_at' => now(), 'messages_acknowledged_at' => null,
            ]);
            $this->audit->record('sms.batch.synced', $locked, ['portal_status' => $locked->portal_status, 'debited_amount' => $portalDebited, 'refunded_amount' => $portalRefunded]);
        }, 3);
        } catch (\Throwable $e) {
            if ($e instanceof \InvalidArgumentException || in_array($e->getMessage(), ['invalid_portal_settlement_amount', 'invalid_portal_release_amount'], true)) {
                $batch->update(['billing_review_count' => max(1, (int) $batch->billing_review_count), 'status' => 'billing_review_required', 'last_synced_at' => now()]);
                return ['ok' => false, 'status' => 409, 'code' => 'billing_review_required', 'message' => 'Portal settlement exceeds the supported local reservation state. Billing review is required.', 'data' => []];
            }
            throw $e;
        }

        $references = SmsMessage::where('sms_batch_id', $batch->id)->whereNotNull('portal_message_reference')->orderBy('id')->pluck('portal_message_reference')->all();
        $chunkSize = max(1, min((int) config('portal_sms.sync.message_ack_chunk_size', 10000), 10000));
        foreach (array_chunk($references, $chunkSize) as $chunk) {
            $ack = $this->portal->acknowledgeMessageSync(['batch_reference' => $batch->portal_batch_reference, 'message_references' => $chunk]);
            if (!($ack['ok'] ?? false)) {
                return ['ok' => false, 'status' => $ack['status'] ?? 0, 'code' => 'message_sync_ack_pending', 'message' => 'Delivery and billing state was saved locally, but portal message acknowledgement is pending retry.', 'data' => $batch->fresh('messages')];
            }
        }
        $batch->update(['messages_acknowledged_at' => now()]);
        return ['ok' => true, 'status' => 200, 'code' => null, 'message' => 'Batch delivery and billing state synchronized.', 'data' => $batch->fresh('messages')];
    }

    private function allMessages(string $batchReference): array
    {
        $all = []; $page = 1;
        do {
            $response = $this->portal->getBatchMessages($batchReference, ['per_page' => 100, 'page' => $page]);
            if (!($response['ok'] ?? false)) return $response;
            $items = data_get($response, 'data.messages', data_get($response, 'data.data', data_get($response, 'data', [])));
            if (!is_array($items)) $items = [];
            array_push($all, ...$items);
            $lastPage = (int) (data_get($response, 'meta.last_page') ?? $page);
            $maxPages = max(1, (int) config('portal_sms.sync.max_message_pages', 10000));
            if ($lastPage > $maxPages) return ['ok' => false, 'status' => 422, 'code' => 'message_sync_limit_exceeded', 'message' => 'This batch exceeds the configured message synchronization page limit.', 'data' => []];
            $page++;
        } while ($page <= $lastPage);

        return ['ok' => true, 'status' => 200, 'code' => null, 'message' => 'Messages loaded.', 'data' => ['messages' => $all]];
    }

    private function mirrorMessage(SmsBatch $batch, array $remote): void
    {
        $reference = data_get($remote, 'message_reference') ?? data_get($remote, 'reference');
        $phone = data_get($remote, 'phone') ?? data_get($remote, 'recipient_phone') ?? data_get($remote, 'recipient');
        $message = SmsMessage::where('sms_batch_id', $batch->id)->when($reference, fn ($q) => $q->where('portal_message_reference', $reference))->when(!$reference, fn ($q) => $q->where('recipient_phone', $phone))->first();
        if (!$message) return;
        $eventReference = data_get($remote, 'last_event_reference');
        if ($eventReference) SmsStatusEvent::firstOrCreate(['portal_event_reference' => $eventReference], ['sms_message_id' => $message->id, 'event_type' => data_get($remote, 'event_type', 'status_sync'), 'delivery_status' => data_get($remote, 'delivery_status'), 'billing_status' => data_get($remote, 'billing_status'), 'payload' => $remote, 'occurred_at' => data_get($remote, 'status_at')]);
        $delivery = data_get($remote, 'delivery_status') ?: data_get($remote, 'status', $message->delivery_status);
        $message->update([
            'portal_message_reference' => $reference ?: $message->portal_message_reference, 'delivery_status' => $delivery,
            'portal_status' => data_get($remote, 'status', $message->portal_status), 'billing_status' => data_get($remote, 'billing_status', $message->billing_status),
            'client_charge_amount' => data_get($remote, 'amount', $message->client_charge_amount), 'refund_eligible' => data_get($remote, 'refund_eligible', $message->refund_eligible),
            'refunded' => (bool) data_get($remote, 'refunded', $message->refunded), 'refund_reason' => data_get($remote, 'refund_reason', $message->refund_reason),
            'refund_evidence' => data_get($remote, 'refund_evidence', $message->refund_evidence),
            'billing_review_required' => (bool) data_get($remote, 'billing_review_required', $message->billing_review_required),
            'units' => (int) data_get($remote, 'units', $message->units), 'delivered_at' => data_get($remote, 'delivered_at', $message->delivered_at),
            'failed_at' => data_get($remote, 'failed_at', $message->failed_at), 'refunded_at' => data_get($remote, 'refunded_at', $message->refunded_at), 'last_synced_at' => now(),
        ]);
    }
}
