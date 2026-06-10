<?php

namespace App\Services;

use App\Jobs\RefreshDashboardStatSnapshotJob;
use App\Models\DashboardStatSnapshot;
use App\Models\ElectionIncident;
use App\Models\PictureEvidence;
use App\Models\User;
use App\Models\VideoEvidence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DashboardStatSnapshotService
{
    public function __construct(
        private LocationDashboardMetricsService $metricsService,
        private LocationScopeService $scopeService,
        private PackageVisibilityService $packageVisibilityService
    ) {
    }

    public function payloadForUser(?User $user): array
    {
        if (!$this->snapshotsTableExists()) {
            return $this->metricsService->cachedStatCardPayloadForUser($user);
        }

        $identity = $this->identityForUser($user);
        $snapshot = DashboardStatSnapshot::query()
            ->where('package', $identity['package'])
            ->where('access_level', $identity['access_level'])
            ->where('scope_key', $identity['scope_key'])
            ->first();

        if ($snapshot) {
            if (!$snapshot->refreshed_at || $snapshot->refreshed_at->lt(now()->subSeconds(25))) {
                if ($snapshot->refreshed_at?->lt(now()->subMinutes(5)) && Cache::add('dashboard-stat-hard-refresh:' . $identity['package'] . ':' . $identity['access_level'] . ':' . $identity['scope_key'], true, now()->addMinute())) {
                    return $this->refreshForUser($user);
                }

                $this->queueRefresh($identity['access_level'], $identity['scope_type'], $identity['scope_id']);
            }

            return $snapshot->payload;
        }

        return $this->refreshForUser($user);
    }

    public function refreshForUser(?User $user): array
    {
        $identity = $this->identityForUser($user);
        $payload = $this->metricsService->rawStatCardPayloadForUser($user);
        $payload['snapshot'] = [
            'source' => 'precomputed',
            'refreshed_at' => now()->toIso8601String(),
        ];

        if ($this->snapshotsTableExists()) {
            DashboardStatSnapshot::query()->updateOrCreate(
                [
                    'package' => $identity['package'],
                    'access_level' => $identity['access_level'],
                    'scope_key' => $identity['scope_key'],
                ],
                [
                    'scope_type' => $identity['scope_type'],
                    'scope_id' => $identity['scope_id'],
                    'payload' => $payload,
                    'refreshed_at' => now(),
                ]
            );
        }

        return $payload;
    }

    public function refreshForScope(string $accessLevel, string $scopeType, ?int $scopeId = null): void
    {
        $user = $this->resolveUserContext($accessLevel, $scopeType, $scopeId);

        if (!$user) {
            return;
        }

        $this->refreshForUser($user);
    }

    public function queueRefresh(string $accessLevel, string $scopeType, ?int $scopeId = null): void
    {
        $identity = $this->identity($accessLevel, $scopeType, $scopeId);
        $debounceKey = 'dashboard-stat-refresh:' . $identity['package'] . ':' . $identity['access_level'] . ':' . $identity['scope_key'];

        if (!Cache::add($debounceKey, true, now()->addSeconds(10))) {
            return;
        }

        RefreshDashboardStatSnapshotJob::dispatch($accessLevel, $scopeType, $scopeId)->delay(now()->addSeconds(2));
    }

    public function queueAffectedRefreshes(Model $model): void
    {
        $record = $this->recordForScope($model);

        foreach ($this->affectedScopesFromAttributes($record->getAttributes()) as $scope) {
            foreach ($this->accessLevelsForScope($scope['type']) as $accessLevel) {
                $this->queueRefresh($accessLevel, $scope['type'], $scope['id']);
            }
        }
    }

    public function queueAffectedRefreshesForAttributes(array $attributes): void
    {
        foreach ($this->affectedScopesFromAttributes($attributes) as $scope) {
            foreach ($this->accessLevelsForScope($scope['type']) as $accessLevel) {
                $this->queueRefresh($accessLevel, $scope['type'], $scope['id']);
            }
        }
    }

    private function identityForUser(?User $user): array
    {
        return $this->identity(
            $user?->access_level ?? 'guest',
            $this->scopeService->getScopeType($user),
            $this->scopeService->getScopeId($user)
        );
    }

    private function identity(string $accessLevel, string $scopeType, ?int $scopeId = null): array
    {
        return [
            'package' => $this->packageVisibilityService->package(),
            'access_level' => $accessLevel,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'scope_key' => $scopeType . ':' . ($scopeId ?: 'all'),
        ];
    }

    private function resolveUserContext(string $accessLevel, string $scopeType, ?int $scopeId = null): ?User
    {
        $query = User::query()->where('access_level', $accessLevel);

        match ($scopeType) {
            LocationScopeService::REGION => $query->where('region_id', $scopeId),
            LocationScopeService::STATE => $query->where('state_id', $scopeId),
            LocationScopeService::SENATORIAL => $query->where('senatorial_district_id', $scopeId),
            LocationScopeService::FEDERAL => $query->where('federal_constituency_id', $scopeId),
            LocationScopeService::LGA => $query->where('lga_id', $scopeId),
            LocationScopeService::WARD => $query->where('ward_id', $scopeId),
            LocationScopeService::POLLING_UNIT => $query->where('polling_unit_id', $scopeId),
            default => null,
        };

        return $query->first();
    }

    private function recordForScope(Model $model): Model
    {
        if ($model instanceof PictureEvidence || $model instanceof VideoEvidence) {
            return $model->incident ?: $model;
        }

        return $model;
    }

    private function affectedScopesFromAttributes(array $attributes): array
    {
        $scopes = [
            ['type' => LocationScopeService::NATIONAL, 'id' => null],
        ];

        $columns = [
            LocationScopeService::REGION => 'region_id',
            LocationScopeService::STATE => 'state_id',
            LocationScopeService::SENATORIAL => 'senatorial_district_id',
            LocationScopeService::FEDERAL => 'federal_constituency_id',
            LocationScopeService::LGA => 'lga_id',
            LocationScopeService::WARD => 'ward_id',
            LocationScopeService::POLLING_UNIT => 'polling_unit_id',
        ];

        foreach ($columns as $scopeType => $column) {
            if (!empty($attributes[$column])) {
                $scopes[] = ['type' => $scopeType, 'id' => (int) $attributes[$column]];
            }
        }

        return $scopes;
    }

    private function accessLevelsForScope(string $scopeType): array
    {
        return match ($scopeType) {
            LocationScopeService::NATIONAL => ['superadmin', 'nationaladmin'],
            LocationScopeService::REGION => ['regionaladmin'],
            LocationScopeService::STATE => ['stateadmin'],
            LocationScopeService::SENATORIAL => ['senatorialadmin'],
            LocationScopeService::FEDERAL => ['federaladmin'],
            LocationScopeService::LGA => ['lgaadmin'],
            LocationScopeService::WARD => ['wardadmin'],
            LocationScopeService::POLLING_UNIT => ['puadmin'],
            default => [],
        };
    }

    private function snapshotsTableExists(): bool
    {
        return Schema::hasTable('dashboard_stat_snapshots');
    }
}
