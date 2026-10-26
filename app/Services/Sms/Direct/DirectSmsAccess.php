<?php

namespace App\Services\Sms\Direct;

use App\Models\SmsDirectBatch;
use App\Models\SmsDirectMessage;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PackageScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DirectSmsAccess
{
    public const LEVELS = ['nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin'];

    public const COLUMNS = ['region' => 'region_id', 'state' => 'state_id', 'senatorial' => 'senatorial_district_id', 'federal' => 'federal_constituency_id', 'lga' => 'lga_id', 'ward' => 'ward_id', 'polling_unit' => 'polling_unit_id'];

    public function enabled(?User $user): bool
    {
        if (! $user || $user->status !== 'active' || ! in_array($user->access_level, array_merge(['superadmin'], self::LEVELS), true) || ! Schema::hasTable('sms_provider_grants')) {
            return false;
        }
        if ($user->access_level === 'superadmin') {
            return true;
        }
        $s = SystemSetting::first();
        if (! $s?->direct_sms_enabled) {
            return false;
        }
        $grant = DB::table('sms_provider_grants')->where('user_id', $user->id)->first();

        return $grant && $grant->enabled !== null ? (bool) $grant->enabled : in_array($user->access_level, $s->direct_sms_levels ?? [], true);
    }

    public function canSend(User $user): bool
    {
        if (! app(\App\Services\CampaignCapabilityService::class)->allows($user, 'direct-sms.create')) { return false; }

        return $user->status === 'active' && $this->enabled($user) && (bool) SystemSetting::first()?->direct_sms_enabled;
    }

    public function assertEnabled(User $user): void
    {
        abort_unless($this->enabled($user), 403, 'SMS access has not been enabled for your account.');
    }

    public function scope(User $user, bool $audit = false): array
    {
        if ($audit && $user->access_level !== 'superadmin') {
            $grant = DB::table('sms_provider_grants')->where('user_id', $user->id)->first();
            if ($grant?->audit_scope) {
                return [$grant->audit_scope, $grant->audit_scope_id];
            }
        }

        return match ($user->access_level) {
            'superadmin','nationaladmin' => ['national', null],'regionaladmin' => ['region', $user->region_id],'stateadmin' => ['state', $user->state_id],'senatorialadmin' => ['senatorial', $user->senatorial_district_id],'federaladmin' => ['federal', $user->federal_constituency_id],'lgaadmin' => ['lga', $user->lga_id],'wardadmin' => ['ward', $user->ward_id],'puadmin' => ['polling_unit', $user->polling_unit_id],default => ['denied', null]
        };
    }

    public function restrict(Builder $q, User $user, bool $audit = false): Builder
    {
        [$type,$id] = $this->scope($user, $audit);
        $this->boundary($q, $type, $id);
        $scope = app(PackageScopeService::class)->current();
        $type = match ($scope->scope_type) {
            'senatorial_district' => 'senatorial','federal_constituency' => 'federal','local_government' => 'lga',default => $scope->scope_type
        };
        if (in_array($type, ['state', 'senatorial', 'federal', 'lga'], true)) {
            $column = self::COLUMNS[$type];
            $this->boundary($q, $type, $scope->{$column});
        } elseif ($scope->fallback_used || ! in_array($type, ['national', 'country'], true)) {
            $q->whereRaw('1=0');
        }

        return $q;
    }

    private function boundary(Builder $q, ?string $type, $id): void
    {
        if (in_array($type, ['national', 'country'], true)) {
            return;
        }
        if (! $id || ! isset(self::COLUMNS[$type ?? ''])) {
            $q->whereRaw('1=0');

            return;
        }
        $q->where($q->getModel()->getTable().'.'.self::COLUMNS[$type], $id);
    }

    public function messages(User $user): Builder
    {
        return $this->restrict(SmsDirectMessage::query(), $user, true);
    }

    public function batches(User $user): Builder
    {
        return SmsDirectBatch::whereHas('messages', fn ($q) => $this->restrict($q, $user, true));
    }

    public function fullBatch(User $user, SmsDirectBatch $batch): bool
    {
        return ! $batch->messages()->whereNotIn('id', $this->messages($user)->select('sms_direct_messages.id'))->exists();
    }
}
