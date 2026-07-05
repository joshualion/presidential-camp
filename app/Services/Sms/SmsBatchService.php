<?php

namespace App\Services\Sms;

use App\Models\SmsBatch;
use App\Models\SmsMessage;
use App\Models\SmsSenderId;
use App\Models\SmsWallet;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SmsBatchService
{
    public function __construct(
        private PortalSmsClient $portal,
        private SmsWalletService $wallets,
        private SmsAuditService $audit,
        private SmsPhoneNormalizer $phones,
    ) {}

    public function create(array $payload, SmsWallet $wallet, User $actor): array
    {
        $settings = SystemSetting::first();
        if (!$settings?->portal_sms_enabled || !$settings->portal_sms_sending_enabled) return $this->error('sms_module_disabled', 'SMS sending is disabled.');
        foreach (['route', 'sender_id', 'message', 'recipients'] as $required) if (!isset($payload[$required]) || $payload[$required] === '' || $payload[$required] === []) return $this->error('validation_failed', 'The '.$required.' field is required.');
        if (!in_array($payload['route'], ['regular', 'priority', 'system'], true)) return $this->error('sms_route_not_configured', 'The selected SMS route is invalid.');
        if ($payload['route'] === 'system' && !$settings->portal_sms_system_route_enabled) return $this->error('sms_route_not_configured', 'The system SMS route is disabled.');
        if ($wallet->status !== 'active') return $this->error('wallet_unavailable', 'The selected SMS wallet is not active.');
        if (!SmsSenderId::where('sender_id', $payload['sender_id'])->where('status', 'approved')->exists()) return $this->error('sender_id_not_approved', 'The selected sender ID is not approved.');
        $prepared = $this->phones->prepare((array) $payload['recipients']);
        $valid = $prepared['recipients']; $invalid = $prepared['invalid_rows']; $duplicates = $prepared['duplicates'];
        if ($valid === []) return $this->error('invalid_recipients', 'No valid SMS recipients were provided.', ['invalid_recipients' => $invalid]);

        $portalPayload = [
            'route' => $payload['route'], 'sender_id' => $payload['sender_id'], 'message' => $payload['message'],
            'recipients' => array_values($valid), 'source_context' => $payload['source_context'] ?? null,
            'charge_allocation_type' => $wallet->owner_type === 'organization' ? 'organization_wallet' : 'user_wallet',
            'charge_core_user_reference' => $wallet->user?->uuid,
            'charge_core_wallet_reference' => $wallet->wallet_reference,
            'scheduled_at' => $payload['scheduled_at'] ?? null,
        ];
        $estimate = $this->portal->estimate([
            'route' => $payload['route'], 'sender_id' => $payload['sender_id'], 'message' => $payload['message'],
            'recipients_count' => count($valid),
        ]);
        if (!($estimate['ok'] ?? false)) return $estimate;
        $amount = (string) (data_get($estimate, 'data.estimated_total') ?? '0');
        $units = (int) (data_get($estimate, 'data.total_units') ?? count($valid));
        $estimateCurrency = strtoupper((string) data_get($estimate, 'data.currency', $wallet->currency));
        if ($estimateCurrency !== strtoupper($wallet->currency)) return $this->error('portal_currency_mismatch', 'The portal estimate currency does not match the selected local wallet.');
        try {
            if ($this->wallets->compare($amount, '0') <= 0) return $this->error('portal_estimate_invalid', 'The portal returned an invalid SMS estimate.');
            if ($this->wallets->compare($wallet->available_balance, $amount) < 0) return $this->error('insufficient_local_wallet_balance', 'The selected local SMS allocation wallet has insufficient available balance.');
        } catch (\InvalidArgumentException) {
            return $this->error('portal_estimate_invalid', 'The portal returned an invalid SMS estimate.');
        }
        if (data_get($estimate, 'data.can_afford') === false) return $this->error('insufficient_wallet_balance', 'The portal master SMS wallet cannot currently fund this batch.');
        $localReference = (string) Str::uuid(); $idempotencyKey = 'core-batch-'.$localReference;

        $recipientStats = (array) ($payload['recipient_stats'] ?? []);
        $invalidCount = max(count($invalid), (int) ($recipientStats['invalid'] ?? 0));
        $duplicateCount = max($duplicates, (int) ($recipientStats['duplicates'] ?? 0));

        try {
            $batch = DB::transaction(function () use ($payload, $wallet, $actor, $valid, $invalidCount, $duplicateCount, $amount, $units, $localReference, $idempotencyKey) {
                $batch = SmsBatch::create([
                    'local_batch_reference' => $localReference, 'sms_wallet_id' => $wallet->id, 'selected_wallet_reference' => $wallet->wallet_reference,
                    'wallet_owner_type' => $wallet->owner_type, 'wallet_owner_user_id' => $wallet->user_id, 'currency' => $wallet->currency, 'route' => $payload['route'],
                    'sender_id' => $payload['sender_id'], 'message_body' => $payload['message'], 'source_context' => $payload['source_context'] ?? null,
                    'target_scope_metadata' => $payload['target_scope_metadata'] ?? [],
                    'metadata' => array_merge($payload['metadata'] ?? [], ['scheduled_at' => $payload['scheduled_at'] ?? null]),
                    'valid_recipient_count' => count($valid), 'invalid_recipient_count' => $invalidCount, 'duplicate_recipient_count' => $duplicateCount,
                    'total_units' => $units, 'estimated_amount' => $amount, 'reserved_amount' => $amount, 'idempotency_key' => $idempotencyKey,
                    'created_by' => $actor->id, 'status' => 'pending',
                ]);
                foreach ($valid as $recipient) SmsMessage::create(['sms_batch_id' => $batch->id, 'recipient_phone' => $recipient['phone'], 'recipient_name' => $recipient['name'] ?? null, 'local_reference' => $recipient['local_reference'] ?? null, 'units' => $recipient['units'] ?? 1, 'estimated_amount' => $recipient['estimated_amount'] ?? 0]);
                $this->wallets->reserve($wallet, $amount, $localReference, $actor->id);
                return $batch;
            }, 3);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), $e->getMessage() === 'insufficient_local_wallet_balance' ? 'The selected local SMS allocation wallet has insufficient available balance.' : 'The local SMS reservation could not be created.');
        }

        $create = $this->portal->createBatch($portalPayload, $idempotencyKey);
        if (!($create['ok'] ?? false)) {
            $definitiveRejection = ($create['status'] ?? 0) >= 400 && ($create['status'] ?? 0) < 500 && ($create['code'] ?? null) !== 'duplicate_idempotency_key';
            $batch->update(['status' => $definitiveRejection ? 'failed' : 'reconciliation_required', 'billing_review_count' => $definitiveRejection ? 0 : 1]);
            if ($definitiveRejection) {
                $this->wallets->releaseReservation($batch->reservation, $amount, 'sms_reversal', 'batch-create-failed:'.$localReference, ['portal_error_code' => $create['code']]);
                $this->audit->record('sms.batch.reservation_reversed', $batch, ['portal_error_code' => $create['code'], 'amount' => $amount]);
            }
            return $create + ['local_batch_reference' => $localReference];
        }
        $portalReference = (string) (data_get($create, 'data.batch_reference') ?? data_get($create, 'data.reference'));
        if ($portalReference === '') {
            $batch->update(['status' => 'reconciliation_required', 'billing_review_count' => 1]);
            return $this->error('portal_reference_missing', 'The portal accepted the request without returning a batch reference. The reservation is held for reconciliation.', ['local_batch_reference' => $localReference]);
        }
        $batch->update(['portal_batch_reference' => $portalReference, 'status' => 'submitted', 'portal_status' => data_get($create, 'data.status', 'submitted'), 'submitted_at' => now()]);
        $this->audit->record('sms.batch.submitted', $batch, ['portal_batch_reference' => $portalReference]);
        return ['ok' => true, 'status' => 201, 'code' => null, 'message' => 'SMS batch submitted to Campaign Manager Portal.', 'data' => $batch->fresh()];
    }

    private function error(string $code, string $message, array $data = []): array { return ['ok' => false, 'status' => 422, 'code' => $code, 'message' => $message, 'data' => $data]; }
}
