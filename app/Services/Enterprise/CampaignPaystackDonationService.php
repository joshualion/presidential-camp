<?php

namespace App\Services\Enterprise;

use App\Models\User;
use App\Services\Enterprise\Payments\PaystackGateway;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignPaystackDonationService
{
    // Conservative ceiling for this reviewed workflow, including declared off-platform gifts.
    private const MAX_TOTAL_MINOR = 50_000_000_000;

    public function __construct(private CampaignFinanceService $finance, private PaystackGateway $gateway) {}

    public function queue(User $actor): array
    {
        $this->finance->authorize($actor, 'finance.donations.manage');
        $this->finance->authorize($actor, 'finance.reconcile');
        return DB::table('campaign_paystack_settlement_matches as matches')
            ->join('campaign_finance_statement_checks as checks', 'checks.id', '=', 'matches.check_id')
            ->leftJoin('campaign_paystack_donation_reviews as reviews', 'reviews.match_id', '=', 'matches.id')
            ->whereIn('checks.account_id', $this->finance->accounts($actor)->select('campaign_finance_accounts.id'))
            ->where('checks.review_outcome', 'variance')->whereColumn('checks.variance_minor', 'matches.net_minor')
            ->orderByDesc('matches.id')->limit(50)
            ->get(['matches.id', 'matches.provider_settlement', 'matches.net_minor', 'checks.account_id',
                'reviews.reference', 'reviews.evidence', 'reviews.income_entry_id', 'reviews.reviewed_total_minor'])
            ->map(function (object $row): object {
                $row->evidence = $row->evidence === null ? null : json_decode(Crypt::decryptString($row->evidence), true, flags: JSON_THROW_ON_ERROR);
                return $row;
            })->all();
    }

    public function prepare(User $actor, int $matchId, string $reference, string $donorCase, string $reviewEvidence, int $otherMinor): void
    {
        $this->finance->authorize($actor, 'finance.donations.manage');
        $this->finance->authorize($actor, 'finance.reconcile');
        $match = $this->match($actor, $matchId);
        $donorCase = strtolower(trim($donorCase));
        if (!preg_match('/^[A-Za-z0-9.=\-]{1,100}$/D', $reference)
            || !preg_match('/^[a-z0-9_-]{4,64}$/D', $donorCase)
            || !preg_match('/^[A-Za-z0-9_.\/-]{4,120}$/D', $reviewEvidence)
            || $otherMinor < 0 || $otherMinor > self::MAX_TOTAL_MINOR) {
            $this->invalid('Enter the Paystack reference, internal donor case ID, review record ID and prior contributions.');
        }
        $key = $this->key();
        $providerId = $this->verifiedTransaction($match, $reference, $key);
        $digest = hash_hmac('sha256', $donorCase, config('app.key'));

        DB::transaction(function () use ($actor, $match, $reference, $reviewEvidence, $donorCase, $otherMinor, $key, $providerId, $digest) {
            $this->finance->account($actor, (int) $match->account_id, true);
            $this->assertSameConnection($key);
            $reviews = DB::table('campaign_paystack_donation_reviews');
            if ((clone $reviews)->where('match_id', $match->id)->orWhere('check_id', $match->check_id)
                ->orWhere('provider_transaction_id', $providerId)->orWhere('reference', $reference)->lockForUpdate()->first()) {
                $this->invalid('The statement, settlement or provider transaction already has a donation review.');
            }
            // The settings row serializes reservations across paying accounts for the same campaign.
            $committed = (int) (clone $reviews)->join('campaign_paystack_settlement_matches as matches', 'matches.id', '=', 'match_id')
                ->where('donor_digest', $digest)->lockForUpdate()->get(['matches.gross_minor'])->sum('gross_minor');
            $total = $committed + $otherMinor + (int) $match->gross_minor;
            if ($total > self::MAX_TOTAL_MINOR) {
                $this->invalid('The total reviewed contributions exceed the supported limit.');
            }
            $reviews->insert([
                'match_id' => $match->id, 'check_id' => $match->check_id,
                'provider_transaction_id' => $providerId, 'reference' => $reference,
                'connection' => hash('sha256', $key), 'donor_digest' => $digest,
                'evidence' => Crypt::encryptString(json_encode(['donor_case' => $donorCase, 'review_record' => $reviewEvidence], JSON_THROW_ON_ERROR)),
                'reviewed_total_minor' => $total, 'prepared_by' => $actor->id, 'prepared_at' => now(),
            ]);
            $this->audit($actor, $match, 'paystack.donation.prepared', ['reference' => $reference, 'reviewed_total_minor' => $total]);
        }, 3);
    }

    public function post(User $actor, int $matchId): int
    {
        foreach (['finance.donations.manage', 'finance.reconcile', 'finance.record'] as $permission) {
            $this->finance->authorize($actor, $permission);
        }
        $match = $this->match($actor, $matchId);
        $reviews = DB::table('campaign_paystack_donation_reviews');
        $review = (clone $reviews)->where('match_id', $matchId)->firstOrFail();
        abort_if(in_array((int) $actor->id, [(int) $review->prepared_by, (int) $match->prepared_by, (int) $match->reviewed_by], true), 403,
            'A separate officer must post this receipt.');
        if ($review->income_entry_id !== null) {
            return (int) $review->income_entry_id;
        }
        $key = $this->key();
        if (!hash_equals($review->connection, hash('sha256', $key))
            || $this->verifiedTransaction($match, $review->reference, $key) !== $review->provider_transaction_id) {
            $this->invalid('The previously verified receipt could not be confirmed.');
        }

        return DB::transaction(function () use ($actor, $match, $matchId, $key, $reviews) {
            $account = $this->finance->account($actor, (int) $match->account_id, true);
            $this->assertSameConnection($key);
            $review = (clone $reviews)->where('match_id', $matchId)->lockForUpdate()->firstOrFail();
            if ($review->income_entry_id !== null) {
                return (int) $review->income_entry_id;
            }
            app(CampaignFinancePeriodService::class)->assertOpen((int) $account->id, $match->settled_on);
            $entry = DB::table('campaign_finance_entries')->where('account_id', $account->id)
                ->latest('id')->lockForUpdate()->first();
            if ((int) ($entry->id ?? 0) !== (int) $match->ledger_entry_id
                || (int) $account->balance_minor !== (int) $match->ledger_balance_minor
                || DB::table('campaign_finance_statement_resolutions')->where('check_id', $match->check_id)->lockForUpdate()->first()) {
                $this->invalid('The journal or variance changed after statement review. Reconcile the difference before posting.');
            }
            $entries = $this->finance->postVerifiedDonation($actor, $account, $match, $review->reference);
            $reviews->where('match_id', $matchId)->update($entries + ['posted_by' => $actor->id, 'posted_at' => now()]);
            $this->audit($actor, $match, 'paystack.donation.posted', $entries + [
                'gross_minor' => (int) $match->gross_minor, 'fee_minor' => (int) $match->fee_minor,
                'net_minor' => (int) $match->net_minor,
            ]);
            return $entries['income_entry_id'];
        }, 3);
    }

    private function match(User $actor, int $matchId): object
    {
        $match = DB::table('campaign_paystack_settlement_matches as matches')
            ->join('campaign_finance_statement_checks as checks', 'checks.id', '=', 'matches.check_id')
            ->select('matches.*', 'checks.account_id', 'checks.review_outcome', 'checks.variance_minor',
                'checks.ledger_balance_minor', 'checks.ledger_entry_id', 'checks.prepared_by', 'checks.reviewed_by')
            ->where('matches.id', $matchId)->firstOrFail();
        $this->finance->account($actor, (int) $match->account_id);
        if ($match->review_outcome !== 'variance' || (int) $match->net_minor < 1
            || (int) $match->variance_minor !== (int) $match->net_minor
            || (int) $match->gross_minor > self::MAX_TOTAL_MINOR
            || (int) $match->gross_minor - (int) $match->fee_minor !== (int) $match->net_minor) {
            $this->invalid('The reviewed statement must show an unposted variance equal to this net settlement.');
        }

        return $match;
    }

    private function verifiedTransaction(object $match, string $reference, #[\SensitiveParameter] string $key): string
    {
        $members = $this->gateway->listSettlementTransactions($key, (int) $match->provider_settlement);
        $receipt = $this->gateway->verifyCollection($key, $reference);
        $id = $receipt['id'] ?? null;
        if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]{0,19}$/D', (string) $id)
            || count($members) !== 1 || !is_array($members[0] ?? null)) {
            $this->invalid('Only single-payment settlements with a verifiable provider receipt can be posted here.');
        }
        foreach ([$members[0], $receipt] as $transaction) {
            if ((string) ($transaction['id'] ?? '') !== (string) $id
                || ($transaction['reference'] ?? null) !== $reference
                || ($transaction['domain'] ?? null) !== 'live' || ($transaction['status'] ?? null) !== 'success'
                || ($transaction['currency'] ?? null) !== 'NGN' || ($transaction['amount'] ?? null) !== (int) $match->gross_minor
                || ($transaction['fees'] ?? null) !== (int) $match->fee_minor) {
                $this->invalid('Paystack did not confirm an exact successful payment for this settlement.');
            }
        }

        return (string) $id;
    }

    private function key(bool $lock = false): string
    {
        $settings = DB::table('campaign_paystack_settings')->where('id', 1);
        $encrypted = ($lock ? $settings->lockForUpdate() : $settings)->value('live_secret');
        $key = $encrypted ? Crypt::decryptString($encrypted) : '';
        if (!str_starts_with($key, 'sk_live_')) {
            $this->invalid('Configure the campaign’s verified live connection before reconciling receipts.');
        }
        return $key;
    }

    private function assertSameConnection(#[\SensitiveParameter] string $key): void
    {
        if (!hash_equals($key, $this->key(true))) {
            $this->invalid('The payment connection changed during review.');
        }
    }

    private function audit(User $actor, object $match, string $action, array $details): void
    {
        DB::table('campaign_finance_audits')->insert([
            'account_id' => $match->account_id, 'actor_id' => $actor->id, 'action' => $action,
            'details' => json_encode(['match_id' => $match->id, 'environment' => 'live'] + $details, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['donation' => $message]);
    }
}
