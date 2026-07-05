<?php

namespace App\Services\Sms;

use App\Models\SmsWallet;
use App\Models\SmsWalletReservation;
use App\Models\SmsWalletTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use App\Support\SmsMoney;

class SmsWalletService
{
    public function __construct(private SmsAuditService $audit) {}

    public function organizationWallet(?int $createdBy = null): SmsWallet
    {
        return SmsWallet::firstOrCreate(
            ['owner_key' => 'organization'],
            ['owner_type' => 'organization', 'user_id' => null, 'wallet_reference' => (string) Str::uuid(), 'status' => 'active', 'currency' => 'NGN', 'created_by' => $createdBy]
        );
    }

    public function userWallet(User $user, ?int $createdBy = null, ?string $reference = null): SmsWallet
    {
        return SmsWallet::firstOrCreate(
            ['owner_key' => 'user:'.$user->id],
            ['owner_type' => 'user', 'user_id' => $user->id, 'wallet_reference' => $reference ?: (string) Str::uuid(), 'access_level' => $user->access_level, 'status' => 'active', 'currency' => 'NGN', 'created_by' => $createdBy ?: $user->id]
        );
    }

    public function credit(SmsWallet $wallet, string $amount, string $type, string $idempotencyKey, array $metadata = [], ?int $actorId = null): SmsWalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $type, $idempotencyKey, $metadata, $actorId) {
            if ($existing = SmsWalletTransaction::where('idempotency_key', $idempotencyKey)->first()) return $this->matchingTransaction($existing, $wallet->id, $amount, $type);
            $locked = SmsWallet::lockForUpdate()->findOrFail($wallet->id);
            if ($existing = SmsWalletTransaction::where('idempotency_key', $idempotencyKey)->first()) return $this->matchingTransaction($existing, $locked->id, $amount, $type);
            $this->assertPositive($amount);
            $before = (string) $locked->balance;
            $after = $this->add($before, $amount);
            $locked->update(['balance' => $after]);
            return $this->ledger($locked, $type, 'credit', $amount, $before, $after, (string) $locked->reserved_balance, (string) $locked->reserved_balance, $idempotencyKey, $metadata, $actorId);
        }, 3);
    }

    public function reserve(SmsWallet $wallet, string $amount, string $batchReference, ?int $actorId = null): SmsWalletReservation
    {
        return DB::transaction(function () use ($wallet, $amount, $batchReference, $actorId) {
            if ($existing = SmsWalletReservation::where('local_batch_reference', $batchReference)->first()) return $this->matchingReservation($existing, $wallet, $amount);
            $locked = SmsWallet::lockForUpdate()->findOrFail($wallet->id);
            if ($existing = SmsWalletReservation::where('local_batch_reference', $batchReference)->first()) return $this->matchingReservation($existing, $locked, $amount);
            $this->assertPositive($amount);
            if ($this->compare($this->subtract((string) $locked->balance, (string) $locked->reserved_balance), $amount) < 0) {
                throw new RuntimeException('insufficient_local_wallet_balance');
            }
            $reservedBefore = (string) $locked->reserved_balance;
            $reservedAfter = $this->add($reservedBefore, $amount);
            $locked->update(['reserved_balance' => $reservedAfter]);
            $reservation = SmsWalletReservation::create(['sms_wallet_id' => $locked->id, 'local_batch_reference' => $batchReference, 'amount' => $amount]);
            $this->ledger($locked, 'sms_reserve', 'hold', $amount, (string) $locked->balance, (string) $locked->balance, $reservedBefore, $reservedAfter, 'reserve:'.$batchReference, ['batch_reference' => $batchReference], $actorId);
            return $reservation;
        }, 3);
    }

    public function debitReservation(SmsWalletReservation $reservation, string $amount, string $evidenceKey, array $metadata = []): ?SmsWalletTransaction
    {
        if ($this->compare($amount, '0') <= 0) return null;
        return DB::transaction(function () use ($reservation, $amount, $evidenceKey, $metadata) {
            if ($existing = SmsWalletTransaction::where('idempotency_key', $evidenceKey)->first()) return $this->matchingTransaction($existing, $reservation->sms_wallet_id, $amount, 'sms_debit');
            $lockedReservation = SmsWalletReservation::lockForUpdate()->findOrFail($reservation->id);
            $wallet = SmsWallet::lockForUpdate()->findOrFail($lockedReservation->sms_wallet_id);
            if ($existing = SmsWalletTransaction::where('idempotency_key', $evidenceKey)->first()) return $this->matchingTransaction($existing, $wallet->id, $amount, 'sms_debit');
            $remaining = $this->subtract((string) $lockedReservation->amount, $this->add((string) $lockedReservation->debited_amount, (string) $lockedReservation->released_amount));
            if ($this->compare($remaining, $amount) < 0 || $this->compare((string) $wallet->balance, $amount) < 0) throw new RuntimeException('invalid_portal_settlement_amount');
            $balanceBefore = (string) $wallet->balance;
            $reservedBefore = (string) $wallet->reserved_balance;
            if ($this->compare($reservedBefore, $amount) < 0) throw new RuntimeException('invalid_portal_settlement_amount');
            $balanceAfter = $this->subtract($balanceBefore, $amount);
            $reservedAfter = $this->subtract($reservedBefore, $amount);
            $wallet->update(['balance' => $balanceAfter, 'reserved_balance' => $reservedAfter]);
            $lockedReservation->update(['debited_amount' => $this->add((string) $lockedReservation->debited_amount, $amount)]);
            return $this->ledger($wallet, 'sms_debit', 'debit', $amount, $balanceBefore, $balanceAfter, $reservedBefore, $reservedAfter, $evidenceKey, $metadata);
        }, 3);
    }

    public function releaseReservation(SmsWalletReservation $reservation, string $amount, string $type, string $evidenceKey, array $metadata = []): ?SmsWalletTransaction
    {
        if ($this->compare($amount, '0') <= 0) return null;
        return DB::transaction(function () use ($reservation, $amount, $type, $evidenceKey, $metadata) {
            if ($existing = SmsWalletTransaction::where('idempotency_key', $evidenceKey)->first()) return $this->matchingTransaction($existing, $reservation->sms_wallet_id, $amount, $type);
            $lockedReservation = SmsWalletReservation::lockForUpdate()->findOrFail($reservation->id);
            $wallet = SmsWallet::lockForUpdate()->findOrFail($lockedReservation->sms_wallet_id);
            if ($existing = SmsWalletTransaction::where('idempotency_key', $evidenceKey)->first()) return $this->matchingTransaction($existing, $wallet->id, $amount, $type);
            $remaining = $this->subtract((string) $lockedReservation->amount, $this->add((string) $lockedReservation->debited_amount, (string) $lockedReservation->released_amount));
            if ($this->compare($remaining, $amount) < 0) throw new RuntimeException('invalid_portal_release_amount');
            $reservedBefore = (string) $wallet->reserved_balance;
            if ($this->compare($reservedBefore, $amount) < 0) throw new RuntimeException('invalid_portal_release_amount');
            $reservedAfter = $this->subtract($reservedBefore, $amount);
            $wallet->update(['reserved_balance' => $reservedAfter]);
            $released = $this->add((string) $lockedReservation->released_amount, $amount);
            $lockedReservation->update(['released_amount' => $released, 'status' => $this->compare($this->add((string) $lockedReservation->debited_amount, $released), (string) $lockedReservation->amount) >= 0 ? 'settled' : 'partially_settled', 'settled_at' => now()]);
            return $this->ledger($wallet, $type, 'release', $amount, (string) $wallet->balance, (string) $wallet->balance, $reservedBefore, $reservedAfter, $evidenceKey, $metadata);
        }, 3);
    }

    public function refund(SmsWallet $wallet, string $amount, string $evidenceKey, array $metadata = []): ?SmsWalletTransaction
    {
        if ($this->compare($amount, '0') <= 0) return null;
        return $this->credit($wallet, $amount, 'sms_refund', $evidenceKey, $metadata);
    }

    public function transfer(SmsWallet $from, SmsWallet $to, string $amount, User $actor, string $reason): array
    {
        return DB::transaction(function () use ($from, $to, $amount, $actor, $reason) {
            $this->assertPositive($amount);
            if ($from->id === $to->id) throw new RuntimeException('invalid_wallet_transfer');
            $ids = [$from->id, $to->id]; sort($ids);
            $wallets = SmsWallet::whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            $source = $wallets[$from->id]; $target = $wallets[$to->id];
            if ($source->status !== 'active' || $target->status !== 'active') throw new RuntimeException('wallet_unavailable');
            if ($this->compare($this->subtract((string) $source->balance, (string) $source->reserved_balance), $amount) < 0) throw new RuntimeException('insufficient_local_wallet_balance');
            $reference = (string) Str::uuid();
            $sourceBefore = (string) $source->balance; $sourceAfter = $this->subtract($sourceBefore, $amount);
            $targetBefore = (string) $target->balance; $targetAfter = $this->add($targetBefore, $amount);
            $source->update(['balance' => $sourceAfter]); $target->update(['balance' => $targetAfter]);
            $out = $this->ledger($source, 'admin_transfer_out', 'debit', $amount, $sourceBefore, $sourceAfter, (string) $source->reserved_balance, (string) $source->reserved_balance, 'transfer:'.$reference.':out', compact('reference', 'reason'), $actor->id);
            $in = $this->ledger($target, 'admin_transfer_in', 'credit', $amount, $targetBefore, $targetAfter, (string) $target->reserved_balance, (string) $target->reserved_balance, 'transfer:'.$reference.':in', compact('reference', 'reason'), $actor->id);
            $this->audit->record('sms.wallet.transfer_created', $out, ['reference' => $reference, 'from' => $from->wallet_reference, 'to' => $to->wallet_reference, 'amount' => $amount]);
            return [$out, $in];
        }, 3);
    }

    private function ledger(SmsWallet $wallet, string $type, string $direction, string $amount, string $balanceBefore, string $balanceAfter, string $reservedBefore, string $reservedAfter, string $key, array $metadata, ?int $actorId = null): SmsWalletTransaction
    {
        $transaction = SmsWalletTransaction::create(['sms_wallet_id' => $wallet->id, 'type' => $type, 'direction' => $direction, 'amount' => $amount, 'balance_before' => $balanceBefore, 'balance_after' => $balanceAfter, 'reserved_before' => $reservedBefore, 'reserved_after' => $reservedAfter, 'idempotency_key' => $key, 'external_reference' => $metadata['external_reference'] ?? null, 'metadata' => $metadata, 'created_by' => $actorId]);
        $this->audit->record('sms.wallet.'.$type.'_applied', $transaction, ['wallet_reference' => $wallet->wallet_reference, 'amount' => $amount]);
        return $transaction;
    }

    private function assertPositive(string $amount): void { if ($this->compare($amount, '0') <= 0) throw new RuntimeException('invalid_wallet_amount'); }
    private function matchingTransaction(SmsWalletTransaction $transaction, int $walletId, string $amount, string $type): SmsWalletTransaction
    {
        if ((int) $transaction->sms_wallet_id !== $walletId || $transaction->type !== $type || $this->compare((string) $transaction->amount, $amount) !== 0) {
            throw new RuntimeException('sms_ledger_idempotency_conflict');
        }
        return $transaction;
    }
    private function matchingReservation(SmsWalletReservation $reservation, SmsWallet $wallet, string $amount): SmsWalletReservation
    {
        if ((int) $reservation->sms_wallet_id !== (int) $wallet->id || $this->compare((string) $reservation->amount, $amount) !== 0) {
            throw new RuntimeException('sms_reservation_idempotency_conflict');
        }
        return $reservation;
    }
    public function add(string $a, string $b): string { return SmsMoney::add($a, $b); }
    public function subtract(string $a, string $b): string { return SmsMoney::subtract($a, $b); }
    public function compare(string $a, string $b): int { return SmsMoney::compare($a, $b); }
}
