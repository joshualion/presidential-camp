<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Services\LicensedScopeQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationRecipientResolver
{
    private const ADMIN_LEVELS = [
        'superadmin',
        'nationaladmin',
        'regionaladmin',
        'stateadmin',
        'senatorialadmin',
        'federaladmin',
        'lgaadmin',
        'wardadmin',
        'puadmin',
    ];

    public function resolve(string $eventType, ?User $actor, NotificationContext $context, bool $excludeActor = true): Collection
    {
        $query = User::query()
            ->where('status', 'active')
            ->whereIn('access_level', self::ADMIN_LEVELS)
            ->when($excludeActor && $actor, fn (Builder $q) => $q->whereKeyNot($actor->id))
            ->where(function (Builder $query) use ($context) {
                $query->whereIn('access_level', ['superadmin', 'nationaladmin']);

                $this->orJurisdictionMatch($query, 'regionaladmin', 'region_id', $context->regionId);
                $this->orJurisdictionMatch($query, 'stateadmin', 'state_id', $context->stateId);
                $this->orJurisdictionMatch($query, 'senatorialadmin', 'senatorial_district_id', $context->senatorialDistrictId);
                $this->orJurisdictionMatch($query, 'federaladmin', 'federal_constituency_id', $context->federalConstituencyId);
                $this->orJurisdictionMatch($query, 'lgaadmin', 'lga_id', $context->lgaId);
                $this->orJurisdictionMatch($query, 'wardadmin', 'ward_id', $context->wardId);
                $this->orJurisdictionMatch($query, 'puadmin', 'polling_unit_id', $context->pollingUnitId);
            });

        app(LicensedScopeQueryService::class)->applyToUsersQuery($query);

        return $query->get();
    }

    private function orJurisdictionMatch(Builder $query, string $accessLevel, string $column, ?int $value): void
    {
        if (!$value) {
            return;
        }

        $query->orWhere(fn (Builder $q) => $q
            ->where('access_level', $accessLevel)
            ->where($column, $value));
    }
}
