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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardStatSnapshotService
{
    public function __construct(
        private LocationDashboardMetricsService $metricsService,
        private LocationScopeService $scopeService,
        private PackageVisibilityService $packageVisibilityService,
        private PackageScopeService $packageScopeService
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
            try {
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
            } catch (Throwable $e) {
                $this->reportNonCriticalFailure('refresh', $e);
            }
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

        try {
            if (!Cache::add($debounceKey, true, now()->addSeconds(10))) {
                return;
            }

            RefreshDashboardStatSnapshotJob::dispatch($accessLevel, $scopeType, $scopeId)->delay(now()->addSeconds(2));
        } catch (Throwable $e) {
            $this->reportNonCriticalFailure('queue', $e);
        }
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
        $licenseScopeKey = $this->licenseScopeKey();

        return [
            'package' => $this->packageVisibilityService->package(),
            'access_level' => $accessLevel,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'scope_key' => ($licenseScopeKey ? $licenseScopeKey.':' : '').$scopeType . ':' . ($scopeId ?: 'all'),
        ];
    }

    private function licenseScopeKey(): ?string
    {
        try {
            if (!$this->packageScopeService->isSelfHosted() || !$this->packageScopeService->hasLocalLicense()) {
                return null;
            }

            $scope = $this->packageScopeService->current();

            return implode(':', array_filter([
                'license',
                $scope->deployment_mode,
                $scope->package_type,
                $scope->scope_type,
                $scope->state_id,
                $scope->senatorial_district_id,
                $scope->federal_constituency_id,
                $scope->lga_id,
            ], fn ($value) => $value !== null && $value !== ''));
        } catch (\Throwable) {
            return null;
        }
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

    private function reportNonCriticalFailure(string $stage, Throwable $e): void
    {
        Log::warning('Dashboard stat snapshot '.$stage.' skipped.', [
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }
}
