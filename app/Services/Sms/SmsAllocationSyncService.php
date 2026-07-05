<?php

namespace App\Services\Sms;

use App\Models\SmsAllocation;
use App\Models\SmsTopup;
use App\Models\SmsCreditRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsAllocationSyncService
{
    public function __construct(private PortalSmsClient $portal, private SmsWalletService $wallets, private SmsAuditService $audit) {}

    public function sync(int $limit = 100): array
    {
        $response = $this->portal->listAllocations(['status' => 'confirmed', 'per_page' => min($limit, 100)]);
        if (!($response['ok'] ?? false)) return $response;
        $items = data_get($response, 'data.allocations', data_get($response, 'data.data', data_get($response, 'data', [])));
        if (!is_array($items)) $items = [];
        $credited = 0; $acknowledged = 0; $errors = [];

        foreach ($items as $item) {
            try {
                $reference = (string) (data_get($item, 'allocation_reference') ?? data_get($item, 'reference'));
                if ($reference === '') throw new RuntimeException('Allocation reference is missing.');
                $allocation = DB::transaction(function () use ($item, $reference, &$credited) {
                    $allocation = SmsAllocation::where('portal_reference', $reference)->lockForUpdate()->first();
                    if ($allocation?->credited_at) return $allocation;
                    $type = (string) data_get($item, 'allocation_type');
                    if (!in_array($type, ['organization_wallet', 'user_wallet'], true)) throw new RuntimeException('Allocation wallet type is invalid.');
                    $wallet = $type === 'organization_wallet' ? $this->wallets->organizationWallet() : $this->resolveUserWallet($item);
                    $currency = strtoupper((string) data_get($item, 'currency', 'NGN'));
                    if ($currency !== strtoupper($wallet->currency)) throw new RuntimeException('Allocation currency does not match the local wallet.');
                    $portalWalletReference = (string) data_get($item, 'core_wallet_reference');
                    if ($portalWalletReference !== '' && !hash_equals((string) $wallet->wallet_reference, $portalWalletReference)) {
                        throw new RuntimeException('Allocation wallet mapping does not match the local wallet.');
                    }
                    $allocation ??= SmsAllocation::create(['portal_reference' => $reference, 'allocation_type' => $type, 'sms_wallet_id' => $wallet->id, 'core_user_reference' => data_get($item, 'core_user_reference'), 'core_wallet_reference' => data_get($item, 'core_wallet_reference'), 'amount' => (string) data_get($item, 'amount'), 'currency' => $currency, 'status' => 'confirmed', 'portal_payload' => $item]);
                    $transaction = $this->wallets->credit($wallet, (string) $allocation->amount, $this->creditType($item), 'allocation:'.$reference, ['external_reference' => $reference, 'allocation_type' => $type]);
                    $allocation->update(['sms_wallet_id' => $wallet->id, 'credited_at' => now()]);
                    $sourceType = (string) data_get($item, 'source_type'); $sourceReference = data_get($item, 'source_reference');
                    if ($sourceType === 'topup' && filled($sourceReference)) SmsTopup::where(fn ($query) => $query->where('payment_reference', $sourceReference)->orWhere('portal_reference', $sourceReference))->update(['status' => 'confirmed', 'confirmed_at' => now()]);
                    if ($sourceType === 'admin_credit' && filled($sourceReference)) SmsCreditRequest::where('portal_reference', $sourceReference)->update(['status' => 'fulfilled', 'last_synced_at' => now()]);
                    $this->audit->record('sms.allocation.synced', $allocation, ['wallet_reference' => $wallet->wallet_reference, 'transaction_reference' => $transaction->transaction_reference]);
                    $credited++;
                    return $allocation;
                }, 3);

                $ack = $this->portal->markAllocationSynced($reference);
                if ($ack['ok'] ?? false) { $allocation->update(['portal_synced_at' => now()]); $acknowledged++; }
                else $errors[] = ['reference' => $reference, 'message' => $ack['message']];
            } catch (\Throwable $e) {
                $errors[] = ['reference' => data_get($item, 'allocation_reference'), 'message' => str_starts_with($e->getMessage(), 'Allocation') ? $e->getMessage() : 'Allocation could not be applied locally.'];
            }
        }

        return ['ok' => $errors === [], 'status' => 200, 'code' => $errors === [] ? null : 'partial_sync', 'message' => $errors === [] ? 'Allocations synchronized.' : 'Some allocations require attention.', 'data' => compact('credited', 'acknowledged', 'errors')];
    }

    private function resolveUserWallet(array $item)
    {
        $reference = (string) data_get($item, 'core_user_reference');
        $user = User::query()->where('uuid', $reference)->orWhere('id', ctype_digit($reference) ? (int) $reference : 0)->first();
        if (!$user) throw new RuntimeException('Allocation user mapping was not found.');
        return $this->wallets->userWallet($user, null, data_get($item, 'core_wallet_reference'));
    }

    private function creditType(array $item): string
    {
        return match ((string) data_get($item, 'source_type')) {
            'topup', 'self_topup' => 'self_topup_credit',
            'credit_request', 'admin_credit' => 'credit_request_fulfilled',
            'organization_contribution' => 'organization_contribution',
            default => 'allocation_credit',
        };
    }
}
