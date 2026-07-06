<?php

namespace App\Services;

use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\PollingUnitAgentAssignment;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EmailNotificationAudienceService
{
    private const ACCESS_LEVEL_RANKS = [
        'superadmin' => 1,
        'nationaladmin' => 2,
        'regionaladmin' => 3,
        'stateadmin' => 4,
        'senatorialadmin' => 5,
        'federaladmin' => 5,
        'lgaadmin' => 6,
        'wardadmin' => 7,
        'puadmin' => 8,
        'user' => 9,
    ];

    public function __construct(
        private LocationScopeService $locationScope,
        private LicensedScopeQueryService $licensedScope,
        private StructuralLocationAccessService $structuralLocations,
        private CampaignPackageRoleService $packageRoles,
    ) {
    }

    public function accessLevelsFor(User $sender): array
    {
        $this->assertSenderHasJurisdiction($sender);
        $senderRank = self::ACCESS_LEVEL_RANKS[$sender->access_level] ?? PHP_INT_MAX;

        return collect($this->packageRoles->allowedAccessLevels())
            ->filter(function (string $label, string $level) use ($sender, $senderRank) {
                if ($sender->access_level === 'superadmin') {
                    return true;
                }

                return (self::ACCESS_LEVEL_RANKS[$level] ?? 0) >= $senderRank;
            })
            ->all();
    }

    public function rolesFor(User $sender): Collection
    {
        $levels = array_keys($this->accessLevelsFor($sender));

        return Role::query()
            ->whereIn('group_name', $levels)
            ->orderBy('group_name')
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role) => $this->packageRoles->roleIsPackageAllowed($role))
            ->values();
    }

    public function locationOptions(User $sender, string $type, array $filters = []): Collection
    {
        $this->assertSenderHasJurisdiction($sender);
        $query = match ($type) {
            'states' => State::query()->select(['id', 'name']),
            'lgas' => LocalGovernmentArea::query()
                ->select(['id', 'name', 'state_id'])
                ->when($filters['state_id'] ?? null, fn (Builder $builder, $id) => $builder->where('state_id', $id)),
            'wards' => Ward::query()
                ->select(['id', 'name', 'lga_id'])
                ->when($filters['lga_id'] ?? null, fn (Builder $builder, $id) => $builder->where('lga_id', $id)),
            'polling_units' => PollingUnit::query()
                ->select(['id', 'name', 'ward_id'])
                ->when($filters['ward_id'] ?? null, fn (Builder $builder, $id) => $builder->where('ward_id', $id)),
            default => throw ValidationException::withMessages(['type' => 'Invalid location option type.']),
        };

        $subject = match ($type) {
            'states' => 'states',
            'lgas' => 'local_government_areas',
            'wards' => 'wards',
            'polling_units' => 'polling_units',
        };

        return $this->structuralLocations
            ->applyScope($query, $sender, $subject)
            ->orderBy('name')
            ->get();
    }

    public function recipients(User $sender, array $data): Collection
    {
        return $this->recipientQuery($sender, $data)->distinct()->get();
    }

    public function recipientCount(User $sender, array $data): int
    {
        return (int) $this->recipientQuery($sender, $data)->distinct()->count('users.id');
    }

    public function sendingScopeLabel(User $sender): string
    {
        $this->assertSenderHasJurisdiction($sender);
        $sender->loadMissing(['region', 'state', 'senatorialDistrict', 'federalConstituency', 'lga', 'ward', 'pollingUnit']);

        return match ($sender->access_level) {
            'superadmin', 'nationaladmin' => 'Campaignwide',
            'regionaladmin' => $this->withSuffix($sender->region?->name, 'Region'),
            'stateadmin' => $this->withSuffix($sender->state?->name, 'State'),
            'senatorialadmin' => $this->withSuffix($sender->senatorialDistrict?->name, 'Senatorial District'),
            'federaladmin' => $this->withSuffix($sender->federalConstituency?->name, 'Federal Constituency'),
            'lgaadmin' => $this->withSuffix($sender->lga?->name, 'LGA'),
            'wardadmin' => 'Ward: '.($sender->ward?->name ?: 'Assigned ward'),
            'puadmin' => 'Polling Unit: '.($sender->pollingUnit?->name ?: 'Assigned polling unit'),
            default => 'Assigned jurisdiction',
        };
    }

    public function summaryFor(User $sender, array $data): array
    {
        $accessLevelLabels = $this->accessLevelsFor($sender);
        $roles = $this->rolesFor($sender)->keyBy('id');
        $filters = $data['audience_filters'];

        return [
            'scope' => $this->sendingScopeLabel($sender),
            'recipient_group' => [
                'all' => 'All eligible users',
                'admins' => 'Admins only',
                'members' => 'Members only',
                'agents' => 'Agents only',
                'access_levels' => 'Selected access levels',
                'roles' => 'Selected roles',
            ][$data['recipient_group']] ?? 'All eligible users',
            'access_levels' => $data['recipient_group'] === 'access_levels'
                ? collect($data['access_levels'] ?? [])
                    ->map(fn (string $level) => $accessLevelLabels[$level] ?? $level)
                    ->values()
                    ->all()
                : [],
            'roles' => $data['recipient_group'] === 'roles'
                ? collect($data['role_ids'] ?? [])
                    ->map(fn ($id) => $roles->get((int) $id)?->name)
                    ->filter()
                    ->values()
                    ->all()
                : [],
            'locations' => collect([
                'State' => $filters['state_id'] ? State::query()->whereKey($filters['state_id'])->value('name') : null,
                'LGA' => $filters['lga_id'] ? LocalGovernmentArea::query()->whereKey($filters['lga_id'])->value('name') : null,
                'Ward' => $filters['ward_id'] ? Ward::query()->whereKey($filters['ward_id'])->value('name') : null,
                'Polling Unit' => $filters['polling_unit_id'] ? PollingUnit::query()->whereKey($filters['polling_unit_id'])->value('name') : null,
            ])->filter()->all(),
        ];
    }

    private function recipientQuery(User $sender, array $data): Builder
    {
        $allowedLevels = array_keys($this->accessLevelsFor($sender));
        $selectedLevels = array_values(array_unique($data['access_levels'] ?? []));
        $selectedRoleIds = array_values(array_unique(array_map('intval', $data['role_ids'] ?? [])));

        $this->validateAudienceSelection($sender, $data, $allowedLevels, $selectedLevels, $selectedRoleIds);

        $query = User::query()
            ->select(['users.id', 'users.email'])
            ->whereKeyNot($sender->getKey())
            ->whereNotNull('users.email')
            ->whereRaw("TRIM(users.email) <> ''")
            ->whereIn('users.access_level', $allowedLevels);

        // Jurisdiction is always applied before optional audience filters.
        $this->locationScope->applyScope($query, $sender, 'users', 'users');
        $this->licensedScope->applyToUsersQuery($query);

        $query->when(($data['audience_filters']['active_only'] ?? true), fn (Builder $builder) => $builder->where('users.status', 'active'));
        $query->when(($data['audience_filters']['verified_only'] ?? false), fn (Builder $builder) => $builder->whereNotNull('users.email_verified_at'));

        match ($data['recipient_group']) {
            'admins' => $query->whereNotIn('users.access_level', ['user', 'puadmin']),
            'members' => $query->where('users.access_level', 'user'),
            'agents' => $query->whereHas('pollingUnitAgentAssignments', fn (Builder $builder) => $builder->where('status', PollingUnitAgentAssignment::STATUS_APPROVED)),
            'access_levels' => $query->whereIn('users.access_level', $selectedLevels),
            'roles' => $query->whereHas('roles', fn (Builder $builder) => $builder->whereIn('roles.id', $selectedRoleIds)),
            default => $query,
        };

        $filters = $data['audience_filters'];
        if ($filters['polling_unit_id'] ?? null) {
            $this->locationScope->applyBoundaryFilter($query, LocationScopeService::POLLING_UNIT, (int) $filters['polling_unit_id'], 'users', 'users');
        } elseif ($filters['ward_id'] ?? null) {
            $this->locationScope->applyBoundaryFilter($query, LocationScopeService::WARD, (int) $filters['ward_id'], 'users', 'users');
        } elseif ($filters['lga_id'] ?? null) {
            $this->locationScope->applyBoundaryFilter($query, LocationScopeService::LGA, (int) $filters['lga_id'], 'users', 'users');
        } elseif ($filters['state_id'] ?? null) {
            $this->locationScope->applyBoundaryFilter($query, LocationScopeService::STATE, (int) $filters['state_id'], 'users', 'users');
        }

        return $query;
    }

    private function validateAudienceSelection(User $sender, array $data, array $allowedLevels, array $selectedLevels, array $selectedRoleIds): void
    {
        if ($data['recipient_group'] === 'access_levels' && ($selectedLevels === [] || array_diff($selectedLevels, $allowedLevels))) {
            throw ValidationException::withMessages(['access_levels' => 'Select one or more eligible access levels.']);
        }

        $eligibleRoles = $this->rolesFor($sender);
        if ($data['recipient_group'] === 'roles' && ($selectedRoleIds === [] || array_diff($selectedRoleIds, $eligibleRoles->pluck('id')->all()))) {
            throw ValidationException::withMessages(['role_ids' => 'Select one or more eligible roles.']);
        }

        $filters = $data['audience_filters'];
        $stateId = $filters['state_id'] ?? null;
        $lgaId = $filters['lga_id'] ?? null;
        $wardId = $filters['ward_id'] ?? null;
        $pollingUnitId = $filters['polling_unit_id'] ?? null;

        if ($stateId && !$this->scopedLocationExists(State::query()->whereKey($stateId), $sender, 'states')) {
            $this->invalidLocation('state_id');
        }
        if ($lgaId && !$this->scopedLocationExists(LocalGovernmentArea::query()->whereKey($lgaId)->when($stateId, fn (Builder $query) => $query->where('state_id', $stateId)), $sender, 'local_government_areas')) {
            $this->invalidLocation('lga_id');
        }
        if ($wardId && !$this->scopedLocationExists(
            Ward::query()->whereKey($wardId)
                ->when($lgaId, fn (Builder $query) => $query->where('lga_id', $lgaId))
                ->when($stateId && !$lgaId, fn (Builder $query) => $query->whereHas('localGovernmentArea', fn (Builder $lgaQuery) => $lgaQuery->where('state_id', $stateId))),
            $sender,
            'wards'
        )) {
            $this->invalidLocation('ward_id');
        }
        if ($pollingUnitId && !$this->scopedLocationExists(
            PollingUnit::query()->whereKey($pollingUnitId)
                ->when($wardId, fn (Builder $query) => $query->where('ward_id', $wardId))
                ->when($lgaId && !$wardId, fn (Builder $query) => $query->whereHas('ward', fn (Builder $wardQuery) => $wardQuery->where('lga_id', $lgaId)))
                ->when($stateId && !$lgaId && !$wardId, fn (Builder $query) => $query->whereHas('ward.localGovernmentArea', fn (Builder $lgaQuery) => $lgaQuery->where('state_id', $stateId))),
            $sender,
            'polling_units'
        )) {
            $this->invalidLocation('polling_unit_id');
        }
    }

    private function scopedLocationExists(Builder $query, User $sender, string $subject): bool
    {
        return $this->structuralLocations->applyScope($query, $sender, $subject)->exists();
    }

    private function invalidLocation(string $field): never
    {
        throw ValidationException::withMessages([$field => 'The selected location is outside your jurisdiction or licensed campaign scope.']);
    }

    private function assertSenderHasJurisdiction(User $sender): void
    {
        if (in_array($sender->access_level, ['superadmin', 'nationaladmin'], true)) {
            return;
        }

        abort_unless(
            $this->locationScope->getScopeId($sender),
            403,
            'Your account does not have an assigned administrative jurisdiction.'
        );
    }

    private function withSuffix(?string $name, string $suffix): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'Assigned '.$suffix;
        }

        return str_ends_with(strtolower($name), strtolower($suffix)) ? $name : "{$name} {$suffix}";
    }
}
