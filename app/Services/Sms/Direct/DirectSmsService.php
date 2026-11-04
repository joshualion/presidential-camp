<?php

namespace App\Services\Sms\Direct;

use App\Models\SmsDirectBatch;
use App\Models\SmsDirectMessage;
use App\Models\SmsProviderAccount;
use App\Models\User;
use App\Services\PackageScopeService;
use App\Services\Sms\SmsPhoneNormalizer;
use App\Services\Sms\SmsRecipientService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class DirectSmsService
{
    public function __construct(private DirectSmsAccess $access, private DirectSmsProvider $provider) {}

    public function account(User $user): ?SmsProviderAccount
    {
        return SmsProviderAccount::where('user_id', $user->id)->where('is_active', true)->latest('id')->first();
    }

    public function audience(User $user, array $selection): array
    {
        // Snapshot geography alongside recipient identity; later user edits cannot move old audits.
        $r = app(SmsRecipientService::class)->resolve($user, $selection);
        if ($r['valid'] > config('direct_sms.maximum_recipients')) {
            throw ValidationException::withMessages(['recipient_source' => 'Split this audience into batches of at most '.config('direct_sms.maximum_recipients').' recipients.']);
        }
        $records = collect($r['recipients']);
        $refs = $records->pluck('local_reference')->filter()->all();
        $byReference = $this->access->restrict(User::query(), $user)->whereIn('uuid', $refs)->get()->keyBy('uuid');
        $manual = ($selection['recipient_source'] ?? 'members') === 'manual';
        $known = [];
        if ($manual) {
            $normalizer = app(SmsPhoneNormalizer::class);
            $this->access->restrict(User::query(), $user)->select(array_merge(['id', 'uuid', 'firstname', 'lastname', 'phone'], array_values(DirectSmsAccess::COLUMNS)))->chunkById(500, function ($rows) use (&$known, $normalizer) {
                foreach ($rows as $u) {
                    $phone = $normalizer->normalize($u->phone);
                    if ($phone) {
                        $known[$phone] = $u;
                    }
                }
            });
        }
        $scope = app(PackageScopeService::class)->current();
        $unrestricted = $this->access->scope($user)[0] === 'national' && in_array($scope->scope_type, ['national', 'country'], true);
        foreach ($r['recipients'] as &$row) {
            $u = $manual ? ($known[$row['phone']] ?? null) : ($byReference[$row['local_reference'] ?? ''] ?? null);
            if (! $u && (! $manual || ! $unrestricted)) {
                throw ValidationException::withMessages(['manual_recipients' => 'Scoped administrators may paste only numbers already stored in their permitted contacts. Add or select those contacts first.']);
            }
            $row['recipient_user_id'] = $u?->id;
            foreach (DirectSmsAccess::COLUMNS as $column) {
                $row[$column] = $u?->{$column};
            }
        }unset($row);
        $blocked = array_fill_keys($this->suppressedPhones($user), true);
        $r['recipients'] = array_values(array_filter($r['recipients'], fn ($row) => ! isset($blocked[$row['phone']])));
        $r['suppressed'] = $r['valid'] - count($r['recipients']);
        $r['valid'] = count($r['recipients']);

        return $r;
    }

    public function suppressedPhones(User $user): array
    {
        return DB::table('sms_direct_suppressions')->whereIn('owner_key', ['campaign', 'user:'.$user->id])->pluck('phone')->all();
    }

    public function create(User $actor, SmsProviderAccount $account, array $selection, string $body, string $token, ?string $scheduledAt = null): SmsDirectBatch
    {
        abort_unless($this->access->canSend($actor) && $account->user_id === $actor->id && $account->is_active, 403);
        if (config('queue.default') === 'sync') {
            throw ValidationException::withMessages(['message' => 'Configure a database queue and worker or scheduled queue runner before sending bulk SMS.']);
        }
        $audience = $this->audience($actor, $selection);
        if (! $audience['valid']) {
            throw ValidationException::withMessages(['recipient_source' => 'No valid recipients were found.']);
        }

        return DB::transaction(function () use ($actor, $account, $selection, $body, $token, $scheduledAt, $audience) {
            $existing = SmsDirectBatch::where('uuid', $token)->first();
            if ($existing) {
                abort_unless($existing->created_by === $actor->id, 403);

                return $existing;
            }
            $batch = SmsDirectBatch::create(['uuid' => $token, 'account_id' => $account->id, 'created_by' => $actor->id, 'provider' => $account->provider, 'sender_id' => $account->sender_id, 'message' => $body, 'source' => $selection['recipient_source'] ?? 'members', 'status' => 'queued', 'recipient_count' => $audience['valid'], 'scheduled_at' => $scheduledAt, 'confirmed_at' => now()]);
            foreach (array_chunk($audience['recipients'], 400) as $chunk) {
                $rows = [];
                foreach ($chunk as $row) {
                    $rows[] = array_merge(array_intersect_key($row, array_flip(array_merge(['phone', 'name', 'recipient_user_id'], array_values(DirectSmsAccess::COLUMNS)))), ['batch_id' => $batch->id, 'status' => 'queued', 'created_at' => now(), 'updated_at' => now()]);
                }SmsDirectMessage::insert($rows);
            }
            $this->event($batch, 'batch.confirmed', $actor->id, ['recipients' => $audience['valid'], 'provider' => $batch->provider]);

            return $batch;
        });
    }

    public function event(?SmsDirectBatch $batch, string $event, ?int $actor, array $details = []): void
    {
        DB::table('sms_direct_events')->insert(['batch_id' => $batch?->id, 'actor_id' => $actor, 'event' => $event, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }

    public function process(int $batchId): void
    {
        $batch = SmsDirectBatch::with('account', 'sender')->find($batchId);
        if (! $batch || ($batch->scheduled_at && $batch->scheduled_at->isFuture())) {
            return;
        }
        if (! app(\App\Services\Sms\SmsAddonAccess::class)->allowed() || ! $batch->account?->is_active || ! $batch->sender || ! $this->access->canSend($batch->sender)) {
            $batch->messages()->where('status', 'queued')->update(['status' => 'cancelled', 'diagnostic_code' => 'sending_disabled']);
            $batch->messages()->where('status', 'submitting')->update(['status' => 'unconfirmed', 'diagnostic_code' => 'sending_disabled_uncertain']);
            $batch->update(['status' => $batch->messages()->where('status', 'unconfirmed')->exists() ? 'reconciliation_required' : 'cancelled']);

            return;
        }
        $batch->messages()->where('status', 'queued')->whereNot(function ($q) use ($batch) {
            $q->whereRaw('1=1');
            $this->access->restrict($q, $batch->sender);
        })->update(['status' => 'cancelled', 'diagnostic_code' => 'scope_changed']);
        $batch->messages()->where('status', 'submitting')->where('submitted_at', '<', now()->subMinutes(5))->update(['status' => 'unconfirmed', 'diagnostic_code' => 'worker_interrupted']);
        $key = 'direct-sms-account-'.$batch->account_id;
        if (RateLimiter::tooManyAttempts($key, config('direct_sms.requests_per_minute'))) {
            return;
        }
        $ids = DB::transaction(function () use ($batch) {
            $rows = $batch->messages()->where('status', 'queued')->orderBy('id')->lockForUpdate()->limit(config('direct_sms.batch_size'))->get();
            SmsDirectMessage::whereIn('id', $rows->pluck('id'))->update(['status' => 'submitting', 'submitted_at' => now()]);

            return $rows->pluck('id')->all();
        });
        if (! $ids) {
            $this->settle($batch);

            return;
        }
        $blocked = $this->suppressedPhones($batch->sender);
        SmsDirectMessage::whereIn('id', $ids)->whereIn('phone', $blocked)->update(['status' => 'suppressed', 'diagnostic_code' => 'contact_opt_out']);
        $ids = SmsDirectMessage::whereIn('id', $ids)->where('status', 'submitting')->pluck('id')->all();
        if (! $ids) {
            $this->settle($batch);

            return;
        }
        RateLimiter::hit($key, 60);
        $phones = SmsDirectMessage::whereIn('id', $ids)->pluck('phone')->all();
        $result = $this->provider->send($batch->account, $batch->sender_id, $batch->message, $phones);
        $status = $result['ok'] ? 'accepted' : ($result['uncertain'] ? 'unconfirmed' : 'failed');
        SmsDirectMessage::whereIn('id', $ids)->update(['status' => $status, 'provider_reference' => $result['reference'], 'diagnostic_code' => $result['code']]);
        $this->event($batch, 'provider.'.$status, null, ['recipients' => count($ids), 'code' => $result['code']]);
        $this->settle($batch);
    }

    private function settle(SmsDirectBatch $batch): void
    {
        $state = $batch->messages()->whereIn('status', ['queued', 'submitting'])->exists() ? 'queued' : ($batch->messages()->where('status', 'unconfirmed')->exists() ? 'reconciliation_required' : 'submitted');
        $batch->update(['status' => $state]);
    }

    public function sync(SmsDirectBatch $batch): void
    {
        // Poll a small, oldest-first set. Recording every attempt prevents one missing report
        // from starving later batches. Stop automatic polling after seven days.
        $refs = $batch->messages()->where('status', 'accepted')->whereNotNull('provider_reference')->where('submitted_at', '>=', now()->subDays(7))
            ->where(fn ($q) => $q->whereNull('last_synced_at')->orWhere('last_synced_at', '<=', now()->subMinutes(10)))
            ->select('provider_reference')->selectRaw('MIN(last_synced_at) as last_poll')->groupBy('provider_reference')->orderBy('last_poll')->limit(2)->pluck('provider_reference');
        foreach ($refs as $ref) {
            $batch->messages()->where('provider_reference', $ref)->where('status', 'accepted')->update(['last_synced_at' => now()]);
            $r = $this->provider->reports($batch->account, $ref);
            if (! $r['ok']) {
                continue;
            }
            $rows = $batch->provider === 'termii' ? (array_is_list($r['data']) ? $r['data'] : data_get($r, 'data.data', [])) : (data_get($r, 'data.data.reports', []));
            if (! is_array($rows)) {
                continue;
            }
            $changed = 0;
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }$phone = app(SmsPhoneNormalizer::class)->normalize($row['recipient'] ?? $row['receiver'] ?? $row['to'] ?? null);
                if (! $phone) {
                    continue;
                }
                $state = $this->provider->delivery($row['delivery_status'] ?? $row['status'] ?? '');
                if ($state === 'accepted') {
                    continue;
                }
                $changed += $batch->messages()->where('provider_reference', $ref)->where('phone', $phone)->where('status', 'accepted')->update(['status' => $state]);
            }
            if ($changed) {
                $this->event($batch, 'provider.delivery_checked', null, ['updated_recipients' => $changed]);
            }
        }
    }
}
