<?php

namespace App\Services\Enterprise;

use App\Models\User;
use App\Services\Enterprise\Payments\PaymentGateway;
use App\Services\Enterprise\Payments\PaystackGateway;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Provider test activity is kept separate from actual campaign balances. */
class CampaignPaystackService
{
    private PaymentGateway $gateway;

    public function __construct(private CampaignFinanceService $finance, PaystackGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function settings(): ?object
    {
        return DB::table('campaign_paystack_settings')->where('id', 1)
            ->first(['mode', 'collections', 'transfers', 'updated_at', 'updated_by']);
    }

    public function saveSettings(User $actor, #[\SensitiveParameter] array $keys, string $mode, bool $collections, bool $transfers): void
    {
        $this->manage($actor);
        if (!in_array($mode, ['test', 'live'], true) || ($mode === 'live' && ($collections || $transfers))) {
            $this->invalid('Live connections are available for verification only.');
        }
        $replacement = [];
        foreach (['test', 'live'] as $environment) {
            foreach (['public' => 'pk', 'secret' => 'sk', 'webhook' => 'whsec'] as $kind => $prefix) {
                $column = $environment.'_'.$kind;
                $key = trim((string) ($keys[$column] ?? ''));
                if ($key === '') {
                    continue;
                }
                $expected = $kind === 'webhook' ? 'whsec_' : $prefix.'_'.$environment.'_';
                if (!preg_match('/^'.preg_quote($expected, '/').'[A-Za-z0-9_-]{16,100}$/D', $key)) {
                    $this->invalid('A Paystack credential does not match its field and environment.');
                }
                $replacement[$column] = Crypt::encryptString($key);
            }
        }

        $old = DB::table('campaign_paystack_settings')->where('id', 1)->first();
        if ($mode === 'live' && ($old?->mode !== 'live' || isset($replacement['live_secret']) || isset($replacement['live_public']))) {
            $key = $replacement['live_secret'] ?? $old?->live_secret;
            if (!$key || !($replacement['live_public'] ?? $old?->live_public)) {
                $this->invalid('Save both live keys to select the live environment.');
            }
            $this->gateway->checkConnection(Crypt::decryptString($key));
        }

        DB::transaction(function () use ($actor, $replacement, $mode, $collections, $transfers, $old) {
            $table = DB::table('campaign_paystack_settings');
            $table->insertOrIgnore(['id' => 1, 'updated_at' => now(), 'updated_by' => $actor->id]);
            $current = $table->where('id', 1)->lockForUpdate()->first();
            if ($current->live_secret !== $old?->live_secret || $current->live_public !== $old?->live_public) {
                $this->invalid('The payment connection changed. Reload and try again.');
            }
            if (($collections || $transfers)
                && (!($replacement['test_secret'] ?? $current->test_secret) || !($replacement['test_public'] ?? $current->test_public))) {
                $this->invalid('Save both test keys before enabling test payments.');
            }
            $table->where('id', 1)->update($replacement + [
                'mode' => $mode, 'collections' => $collections, 'transfers' => $transfers,
                'updated_at' => now(), 'updated_by' => $actor->id,
            ]);
            DB::table('campaign_paystack_setting_events')->insert([
                'actor_id' => $actor->id, 'mode' => $mode, 'collections' => $collections, 'transfers' => $transfers,
                'rotated_fields' => json_encode(array_keys($replacement), JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);
        }, 3);
    }

    public function saveRecipient(User $actor, int $accountId, string $recipient): void
    {
        $this->manage($actor);
        $this->finance->account($actor, $accountId);
        if (!preg_match('/^RCP_[a-z0-9]{6,75}$/iD', $recipient)) {
            $this->invalid('Enter the test recipient code from the campaign Paystack account.');
        }
        $key = $this->connection('transfers');
        // Paystack checks that the recipient exists under this merchant.
        $details = $this->gateway->fetchRecipient($key, $recipient);
        if (($details['recipient_code'] ?? null) !== $recipient || ($details['currency'] ?? null) !== 'NGN'
            || ($details['domain'] ?? null) !== 'test' || ($details['active'] ?? null) !== true || !empty($details['is_deleted'])) {
            $this->invalid('Select an active Nigerian naira recipient in this Paystack test account.');
        }
        DB::table('campaign_paystack_recipients')->upsert([[
            'account_id' => $accountId, 'recipient_code' => $recipient, 'connection' => hash('sha256', $key),
            'updated_at' => now(), 'updated_by' => $actor->id,
        ]], ['account_id'], ['recipient_code', 'connection', 'updated_at', 'updated_by']);
    }

    public function operations(User $actor)
    {
        $this->finance->authorize($actor, 'finance.paystack.view');
        $accounts = $this->finance->accounts($actor)->select('campaign_finance_accounts.id');
        return DB::table('campaign_paystack_operations')->where(function ($query) use ($accounts) {
            $query->whereIn('account_id', clone $accounts)->orWhereIn('destination_id', clone $accounts);
        });
    }

    public function collect(User $actor, int $accountId, int $amount, string $email, string $callback): string
    {
        $this->finance->authorize($actor, 'finance.paystack.collect');
        $this->finance->account($actor, $accountId);
        if ($amount < 100 || $amount > 100000000000000) {
            $this->invalid('Enter an amount within the supported range.');
        }
        $key = $this->connection('collections');
        $reference = $this->begin($actor, $key, 'collection', $accountId, $amount);
        $response = $this->gateway->initializeCollection($key, [
            'amount' => (string) $amount, 'currency' => 'NGN', 'email' => $email,
            'reference' => $reference, 'callback_url' => $callback,
        ]);
        $url = $response['authorization_url'] ?? null;
        if (($response['reference'] ?? null) !== $reference || !is_string($url)
            || parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'checkout.paystack.com'
            || parse_url($url, PHP_URL_USER) !== null || parse_url($url, PHP_URL_PASS) !== null
            || parse_url($url, PHP_URL_PORT) !== null) {
            $this->invalid('Paystack did not return an expected checkout address.');
        }
        return $url;
    }

    public function approveTransfer(User $actor, int $requestId): void
    {
        $this->finance->authorize($actor, 'finance.paystack.approve');
        $key = $this->connection('transfers');
        DB::transaction(function () use ($actor, $requestId, $key) {
            $routing = DB::table('campaign_finance_request_routes')->where('request_id', $requestId)
                ->where('purpose', 'transfer')->firstOrFail();
            $request = DB::table('campaign_finance_requests')->where('id', $requestId)->lockForUpdate()->firstOrFail();
            $this->finance->account($actor, (int) $request->account_id);
            $this->finance->account($actor, (int) $routing->destination_id);
            abort_if((int) $request->requested_by === (int) $actor->id, 403, 'A requester cannot authorize their own payout.');
            app(CampaignFundingProposalService::class)->assertIndependentApprover($actor, $requestId);
            if ($request->status !== 'approved' || DB::table('campaign_paystack_operations')->where('request_id', $requestId)->exists()) {
                $this->invalid('An approved request may receive one Paystack test authorization.');
            }
            $recipient = $this->mappedRecipient((int) $routing->destination_id, $key);
            if (!$recipient) {
                $this->invalid('The destination needs a verified recipient under the campaign Paystack test account.');
            }
            $this->begin($actor, $key, 'transfer', (int) $request->account_id, (int) $request->amount_minor, [
                'request_id' => $requestId, 'destination_id' => $routing->destination_id,
                'recipient_code' => $recipient, 'status' => 'authorized',
            ]);
        }, 3);
    }

    public function transfer(User $actor, int $requestId): void
    {
        $this->finance->authorize($actor, 'finance.paystack.transfer');
        $key = $this->connection('transfers');
        $operation = DB::transaction(function () use ($actor, $requestId, $key) {
            $request = DB::table('campaign_finance_requests')->where('id', $requestId)->lockForUpdate()->firstOrFail();
            $operation = DB::table('campaign_paystack_operations')->where('request_id', $requestId)->lockForUpdate()->firstOrFail();
            $this->finance->account($actor, (int) $operation->account_id);
            $this->finance->account($actor, (int) $operation->destination_id);
            abort_if((int) $operation->created_by === (int) $actor->id || (int) $request->decided_by === (int) $actor->id
                || DB::table('campaign_finance_decisions')->where('request_id', $requestId)->where('actor_id', $actor->id)->exists(),
                403, 'An approver cannot execute the same payout.');
            if ($request->status !== 'approved' || $operation->status !== 'authorized' || $operation->mode !== 'test'
                || !hash_equals($operation->connection, hash('sha256', $key))
                || $this->mappedRecipient((int) $operation->destination_id, $key) !== $operation->recipient_code) {
                $this->invalid('This payout is no longer authorized for this recipient or has already been submitted.');
            }
            DB::table('campaign_paystack_operations')->where('id', $operation->id)
                ->update(['status' => 'pending', 'submitted_by' => $actor->id, 'updated_at' => now()]);
            return $operation;
        }, 3);

        // Persist before sending so an uncertain response can be verified without a second POST.
        $this->gateway->initiateTransfer($key, [
            'source' => 'balance', 'currency' => 'NGN', 'amount' => (int) $operation->amount_minor,
            'reference' => $operation->reference, 'recipient' => $operation->recipient_code,
            'reason' => 'Campaign allocation test #'.$requestId,
        ]);
    }

    public function linkBeneficiary(User $actor, int $beneficiaryId, string $recipient): void
    {
        $this->manage($actor);
        if (!preg_match('/^RCP_[a-z0-9]{6,75}$/iD', $recipient)) {
            $this->invalid('Enter a valid Paystack test recipient code.');
        }
        $beneficiary = DB::table('campaign_finance_request_recipients')->where('id', $beneficiaryId)->firstOrFail();
        $accountId = DB::table('campaign_finance_requests')->where('id', $beneficiary->request_id)->value('account_id');
        $this->finance->account($actor, (int) $accountId);
        $key = $this->connection('transfers');
        $details = $this->gateway->fetchRecipient($key, $recipient);
        $accountName = data_get($details, 'details.account_name');
        if (($details['recipient_code'] ?? null) !== $recipient || ($details['domain'] ?? null) !== 'test'
            || ($details['currency'] ?? null) !== 'NGN' || ($details['type'] ?? null) !== 'nuban'
            || ($details['active'] ?? null) !== true || !empty($details['is_deleted']) || !is_string($accountName)
            || strcasecmp(trim($accountName), trim($beneficiary->recipient_name)) !== 0) {
            $this->invalid('The active test bank account must match the approved beneficiary name.');
        }

        DB::transaction(function () use ($actor, $beneficiary, $recipient, $key) {
            $request = DB::table('campaign_finance_requests')->where('id', $beneficiary->request_id)->lockForUpdate()->firstOrFail();
            $this->finance->account($actor, (int) $request->account_id);
            $link = DB::table('campaign_paystack_beneficiary_links')->where('beneficiary_id', $beneficiary->id)->first();
            if ($request->status !== 'approved' || $link?->operation_id) {
                $this->invalid('The beneficiary requires an approved request and cannot change after payout authorization.');
            }
            DB::table('campaign_paystack_beneficiary_links')->updateOrInsert(['beneficiary_id' => $beneficiary->id], [
                'recipient_code' => $recipient, 'connection' => hash('sha256', $key),
                'linked_by' => $actor->id, 'linked_at' => now(),
            ]);
        }, 3);
    }

    public function approveBatch(User $actor, int $requestId): void
    {
        $this->finance->authorize($actor, 'finance.paystack.approve');
        $key = $this->connection('transfers');
        DB::transaction(function () use ($actor, $requestId, $key) {
            $request = DB::table('campaign_finance_requests')->where('id', $requestId)->lockForUpdate()->firstOrFail();
            $this->finance->account($actor, (int) $request->account_id);
            abort_if((int) $request->requested_by === (int) $actor->id, 403, 'A requester cannot authorize their own payout.');
            app(CampaignFundingProposalService::class)->assertIndependentApprover($actor, $requestId);
            $items = $this->batchItems($requestId)->get(['beneficiaries.id', 'beneficiaries.amount_minor',
                'links.recipient_code', 'links.connection', 'links.linked_by', 'links.operation_id']);
            if ($request->status !== 'approved' || $items->isEmpty() || $items->count() > 100
                || (int) $items->sum('amount_minor') !== (int) $request->amount_minor
                || $items->pluck('recipient_code')->unique()->count() !== $items->count()) {
                $this->invalid('The batch must match the approved total and use distinct verified recipients.');
            }
            foreach ($items as $item) {
                if (!$item->recipient_code || $item->operation_id || !hash_equals($item->connection, hash('sha256', $key))) {
                    $this->invalid('Verify every recipient in this test account before authorizing the batch.');
                }
                abort_if((int) $item->linked_by === (int) $actor->id, 403, 'A separate officer must authorize recipients you linked.');
                $reference = $this->begin($actor, $key, 'transfer', (int) $request->account_id, (int) $item->amount_minor, [
                    'recipient_code' => $item->recipient_code, 'status' => 'authorized',
                ]);
                $operationId = DB::table('campaign_paystack_operations')->where('reference', $reference)->value('id');
                DB::table('campaign_paystack_beneficiary_links')->where('beneficiary_id', $item->id)
                    ->update(['operation_id' => $operationId]);
            }
        }, 3);
    }

    public function submitBatch(User $actor, int $requestId): void
    {
        $this->finance->authorize($actor, 'finance.paystack.transfer');
        $key = $this->connection('transfers');
        $transfers = DB::transaction(function () use ($actor, $requestId, $key) {
            $request = DB::table('campaign_finance_requests')->where('id', $requestId)->lockForUpdate()->firstOrFail();
            $this->finance->account($actor, (int) $request->account_id);
            abort_if((int) $request->decided_by === (int) $actor->id
                || DB::table('campaign_finance_decisions')->where('request_id', $requestId)->where('actor_id', $actor->id)->exists(),
                403, 'A funding approver cannot execute this payout.');
            $items = $this->batchItems($requestId)
                ->leftJoin('campaign_paystack_operations as operations', 'operations.id', '=', 'links.operation_id')
                ->get(['operations.*', 'beneficiaries.amount_minor as approved_amount', 'links.recipient_code as mapped_recipient', 'links.linked_by']);
            if ($request->status !== 'approved' || $items->isEmpty()) {
                $this->invalid('This batch is unavailable for submission.');
            }
            $details = [];
            foreach ($items as $item) {
                if ($item->status !== 'authorized' || $item->mode !== 'test'
                    || !hash_equals($item->connection, hash('sha256', $key))
                    || (int) $item->amount_minor !== (int) $item->approved_amount || $item->recipient_code !== $item->mapped_recipient) {
                    $this->invalid('Authorize every batch item before submitting. A submitted batch is reconciled by its item references.');
                }
                abort_if((int) $item->created_by === (int) $actor->id || (int) $item->linked_by === (int) $actor->id,
                    403, 'The recipient linker and payout approver cannot execute the same batch.');
                DB::table('campaign_paystack_operations')->where('id', $item->id)
                    ->update(['status' => 'pending', 'submitted_by' => $actor->id, 'updated_at' => now()]);
                $details[] = ['amount' => (int) $item->amount_minor, 'recipient' => $item->recipient_code,
                    'reference' => $item->reference, 'reason' => 'Campaign beneficiary test #'.$requestId];
            }
            return $details;
        }, 3);

        $this->gateway->initiateBulkTransfer($key, ['currency' => 'NGN', 'source' => 'balance', 'transfers' => $transfers]);
    }

    private function batchItems(int $requestId)
    {
        return DB::table('campaign_finance_request_recipients as beneficiaries')
            ->leftJoin('campaign_paystack_beneficiary_links as links', 'links.beneficiary_id', '=', 'beneficiaries.id')
            ->where('beneficiaries.request_id', $requestId)->orderBy('beneficiaries.position');
    }

    private function mappedRecipient(int $accountId, #[\SensitiveParameter] string $key): ?string
    {
        return DB::table('campaign_paystack_recipients')->where('account_id', $accountId)
            ->where('connection', hash('sha256', $key))->value('recipient_code');
    }

    public function verify(User $actor, string $reference): string
    {
        $operation = $this->operations($actor)->where('reference', $reference)->firstOrFail();
        return $this->verifyOperation($operation);
    }

    public function reconcileRefund(User $actor, string $reference, int $refundId): string
    {
        $this->finance->authorize($actor, 'finance.reconcile');
        $operation = $this->operations($actor)->where('kind', 'collection')->where('reference', $reference)->firstOrFail();
        $secret = $this->connection(null, $operation->mode);
        if (!hash_equals($operation->connection, hash('sha256', $secret))) {
            $this->invalid('This refund belongs to a different payment connection.');
        }

        $transaction = $this->gateway->verifyCollection($secret, $reference);
        $refund = $this->gateway->fetchRefund($secret, $refundId);
        $transactionId = filter_var($transaction['id'] ?? null, FILTER_VALIDATE_INT);
        $amount = filter_var($refund['amount'] ?? null, FILTER_VALIDATE_INT);
        $status = $refund['status'] ?? null;
        if (!$transactionId || ($transaction['reference'] ?? null) !== $reference
            || ($transaction['domain'] ?? null) !== $operation->mode || ($refund['domain'] ?? null) !== $operation->mode
            || (int) ($refund['id'] ?? 0) !== $refundId || (int) ($refund['transaction'] ?? 0) !== $transactionId
            || ($refund['currency'] ?? null) !== 'NGN' || ($transaction['currency'] ?? null) !== 'NGN'
            || (int) ($transaction['amount'] ?? 0) !== (int) $operation->amount_minor
            || !$amount || $amount < 1 || $amount > (int) $operation->amount_minor
            || !in_array($status, ['pending', 'processing', 'needs-attention', 'failed', 'processed'], true)) {
            $this->invalid('Paystack did not confirm a matching refund for this collection.');
        }

        return DB::transaction(function () use ($actor, $operation, $refundId, $amount, $status) {
            DB::table('campaign_paystack_operations')->where('id', $operation->id)->lockForUpdate()->firstOrFail();
            $refunds = DB::table('campaign_paystack_refund_checks')->where('operation_id', $operation->id);
            $existing = (clone $refunds)->where('provider_refund_id', $refundId)->first();
            if ($existing && (int) $existing->amount_minor !== $amount) {
                $this->invalid('The verified refund amount differs from the recorded refund.');
            }
            if ($existing?->status === 'processed') {
                return 'processed';
            }
            $completed = (int) (clone $refunds)->where('status', 'processed')->sum('amount_minor');
            if ($status === 'processed' && $completed + $amount > (int) $operation->amount_minor) {
                $this->invalid('Processed refunds would exceed the original collection.');
            }
            DB::table('campaign_paystack_refund_checks')->updateOrInsert([
                'operation_id' => $operation->id, 'provider_refund_id' => $refundId,
            ], ['amount_minor' => $amount, 'status' => $status, 'checked_by' => $actor->id, 'checked_at' => now()]);
            return $status;
        }, 3);
    }

    public function receiveWebhook(string $mode, string $body, string $header, string $legacy): void
    {
        abort_unless(in_array($mode, ['test', 'live'], true) && strlen($body) <= 65536, 400);
        $settings = DB::table('campaign_paystack_settings')->where('id', 1)->first();
        $key = $settings?->{$mode.'_secret'};
        abort_unless($key, 401);
        $signingKey = $settings->{$mode.'_webhook'};
        abort_unless($this->gateway->authenticates($body, $header, $legacy,
            $signingKey ? Crypt::decryptString($signingKey) : null, Crypt::decryptString($key)), 401);
        $event = json_decode($body, true);
        abort_unless(is_array($event), 400);
        $name = $event['event'] ?? null;
        $kind = match ($name) {
            'charge.success' => 'collection',
            'transfer.success', 'transfer.failed', 'transfer.reversed' => 'transfer',
            default => null,
        };
        $reference = data_get($event, 'data.reference');
        if ($kind === null || !is_string($reference) || !Str::isUuid($reference) || data_get($event, 'data.domain') !== $mode) {
            return;
        }
        $operation = DB::table('campaign_paystack_operations')->where('mode', $mode)
            ->where('kind', $kind)->where('reference', $reference)->first();
        $digest = hash('sha256', $body);
        if (!$operation || DB::table('campaign_paystack_webhook_events')->where('mode', $mode)->where('digest', $digest)->exists()) {
            return;
        }

        $this->verifyOperation($operation);
        DB::table('campaign_paystack_webhook_events')->insertOrIgnore([
            'mode' => $mode, 'digest' => $digest, 'operation_id' => $operation->id,
            'event' => $name, 'created_at' => now(),
        ]);
    }

    private function verifyOperation(object $operation): string
    {
        if ($operation->status === 'authorized') {
            $this->invalid('Submit the authorized payout before verifying it.');
        }
        $reference = $operation->reference;
        $key = $this->connection(null, $operation->mode);
        if (!hash_equals($operation->connection, hash('sha256', $key))) {
            $this->invalid('This operation was initiated with a different campaign test key.');
        }
        $verified = $operation->kind === 'collection'
            ? $this->gateway->verifyCollection($key, $reference)
            : $this->gateway->verifyTransfer($key, $reference);
        $status = $verified['status'] ?? null;
        $amount = filter_var($verified['amount'] ?? null, FILTER_VALIDATE_INT);
        $recipient = $verified['recipient']['recipient_code'] ?? null;
        if (($verified['domain'] ?? null) !== $operation->mode || ($verified['reference'] ?? null) !== $reference
            || ($verified['currency'] ?? null) !== 'NGN' || $amount !== (int) $operation->amount_minor
            || ($operation->kind === 'transfer' && $recipient !== $operation->recipient_code)
            || !in_array($status, ['success', 'pending', 'failed', 'abandoned', 'reversed', 'otp', 'ongoing', 'queued', 'processing', 'blocked', 'rejected', 'received'], true)) {
            $this->invalid('Paystack returned a result that does not match this test operation.');
        }

        return DB::transaction(function () use ($operation, $status) {
            $table = DB::table('campaign_paystack_operations')->where('id', $operation->id);
            $current = $table->lockForUpdate()->value('status');
            if ($current !== $status && $current !== 'reversed' && ($current !== 'success' || $status === 'reversed')) {
                $table->update(['status' => $status, 'updated_at' => now()]);
                return $status;
            }
            return $current;
        }, 3);
    }

    public function manage(User $actor): void
    {
        abort_unless($actor->access_level === 'superadmin', 403);
        $this->finance->authorize($actor, 'finance.view');
    }

    private function begin(User $actor, string $key, string $kind, int $account, int $amount, array $routing = []): string
    {
        $reference = (string) Str::uuid();
        DB::table('campaign_paystack_operations')->insert($routing + [
            'account_id' => $account, 'kind' => $kind, 'amount_minor' => $amount,
            'reference' => $reference, 'mode' => 'test', 'status' => 'pending', 'connection' => hash('sha256', $key),
            'created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $reference;
    }

    private function connection(?string $enabled = null, ?string $mode = null): string
    {
        $settings = DB::table('campaign_paystack_settings')->where('id', 1)->first();
        $mode ??= $settings?->mode;
        $column = $mode === 'test' ? 'test_secret' : 'live_secret';
        if (!$settings?->{$column} || ($enabled !== null && ($mode !== 'test' || !$settings->{$enabled}))) {
            $this->invalid('This Paystack operation is unavailable in the selected environment.');
        }
        $key = Crypt::decryptString($settings->{$column});
        if (!str_starts_with($key, 'sk_'.$mode.'_')) {
            $this->invalid('The saved credential does not match this environment.');
        }
        return $key;
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['paystack' => $message]);
    }
}
