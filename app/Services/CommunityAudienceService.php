<?php

namespace App\Services;

use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\SupportGroup;
use App\Models\User;
use App\Support\SafeDatabase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CommunityAudienceService
{
    public const TYPE_OWN_SCOPE = 'own_scope';
    public const TYPE_GLOBAL = 'global';
    public const TYPE_SPECIFIC_SCOPE = 'specific_scope';
    public const TYPE_SUPPORT_GROUP = 'support_group';

    private const SCOPE_COLUMNS = [
        'region' => 'region_id',
        'state' => 'state_id',
        'senatorial_district' => 'senatorial_district_id',
        'federal_constituency' => 'federal_constituency_id',
        'lga' => 'lga_id',
        'ward' => 'ward_id',
        'pu' => 'polling_unit_id',
    ];

    public function __construct(
        private PackageScopeService $packageScope,
        private LicensedScopeQueryService $licensedScope,
    ) {
    }

    public function defaultSelection(User $user): array
    {
        return [
            'audience_type' => self::TYPE_GLOBAL,
            'audience_scope_type' => null,
            'audience_scope_id' => null,
            'audience_group_id' => null,
            'audience' => 'public',
            'label' => $this->globalLabel(),
        ];
    }

    public function options(User $user): array
    {
        $default = $this->defaultSelection($user);
        $options = [
            [
                'key' => self::TYPE_GLOBAL,
                'label' => $this->globalLabel(),
                'description' => 'Visible across this installed campaign scope.',
                'audience_type' => self::TYPE_GLOBAL,
                'audience_scope_type' => null,
                'audience_scope_id' => null,
                'audience_group_id' => null,
                'is_default' => true,
            ],
        ];

        foreach ($this->specificScopeOptions($user) as $scope) {
            $options[] = [
                'key' => self::TYPE_SPECIFIC_SCOPE.':'.$scope['type'].':'.$scope['id'],
                'label' => $this->scopeLabel($scope['type'], $scope['id']),
                'description' => 'Visible to members in this in-scope campaign location.',
                'audience_type' => self::TYPE_SPECIFIC_SCOPE,
                'audience_scope_type' => $scope['type'],
                'audience_scope_id' => $scope['id'],
                'audience_group_id' => null,
                'is_default' => false,
            ];
        }

        foreach ($this->supportGroupOptions($user) as $group) {
            $options[] = [
                'key' => self::TYPE_SUPPORT_GROUP.':'.$group['id'],
                'label' => $group['name'],
                'description' => 'Visible only to members of this group.',
                'audience_type' => self::TYPE_SUPPORT_GROUP,
                'audience_scope_type' => null,
                'audience_scope_id' => null,
                'audience_group_id' => $group['id'],
                'is_default' => false,
            ];
        }

        $supportGroupsEnabled = count($this->supportGroupOptions($user)) > 0;

        return [
            'default' => $default,
            'options' => $options,
            'support_group_enabled' => $supportGroupsEnabled,
            'support_group_status' => $supportGroupsEnabled ? 'enabled' : 'unavailable',
        ];
    }

    public function validateSelection(User $user, array $input): array
    {
        $data = Validator::make($input, [
            'audience_type' => 'nullable|string|in:own_scope,global,specific_scope,support_group',
            'audience_scope_type' => 'nullable|string|in:region,state,senatorial_district,federal_constituency,lga,ward,pu',
            'audience_scope_id' => 'nullable|integer|min:1',
            'audience_group_id' => 'nullable|integer|min:1',
            'audience' => 'nullable|string|in:public,region,state,lga,ward,pu',
        ])->validate();

        $type = $data['audience_type'] ?? null;

        if (!$type) {
            $type = isset($data['audience']) ? $this->typeFromLegacyAudience((string) $data['audience']) : self::TYPE_GLOBAL;
        }

        if ($type === self::TYPE_GLOBAL) {
            return [
                'audience_type' => self::TYPE_GLOBAL,
                'audience_scope_type' => null,
                'audience_scope_id' => null,
                'audience_group_id' => null,
                'audience' => 'public',
                'audience_metadata' => ['label' => $this->globalLabel()],
            ];
        }

        if ($type === self::TYPE_SUPPORT_GROUP) {
            $groupId = (int) ($data['audience_group_id'] ?? 0);
            if (!$groupId || !$this->userBelongsToSupportGroup($user, $groupId)) {
                $this->fail('Select a valid group you belong to.');
            }

            return [
                'audience_type' => self::TYPE_SUPPORT_GROUP,
                'audience_scope_type' => null,
                'audience_scope_id' => null,
                'audience_group_id' => $groupId,
                'audience' => 'public',
                'audience_metadata' => ['label' => $this->supportGroupName($groupId) ?? 'Selected Group'],
            ];
        }

        if ($type === self::TYPE_OWN_SCOPE) {
            $scope = $this->ownScope($user);
        } else {
            $scope = [
                'type' => $data['audience_scope_type'] ?? $this->scopeTypeFromLegacyAudience($data['audience'] ?? null),
                'id' => $data['audience_scope_id'] ?? null,
            ];

            if ($scope['type'] && !$scope['id']) {
                $scope['id'] = $this->userScopeId($user, (string) $scope['type']);
            }

            if ($scope['type'] && !$scope['id']) {
                $scope = $this->ownScope($user);
            }

            if (!$scope['type'] || !$scope['id']) {
                $this->fail('Select a valid audience scope.');
            }

            if (!$this->scopeAllowedForUser($user, (string) $scope['type'], (int) $scope['id'])) {
                $this->fail('The selected audience is outside your campaign scope.');
            }
        }

        return [
            'audience_type' => $type,
            'audience_scope_type' => $scope['type'],
            'audience_scope_id' => $scope['id'],
            'audience_group_id' => null,
            'audience' => $this->legacyAudience($scope['type'], $type),
            'audience_metadata' => ['label' => $this->scopeLabel($scope['type'], $scope['id'])],
        ];
    }

    public function applyVisibility(Builder $query, User $viewer): Builder
    {
        if (!$this->viewerWithinLicensedScope($viewer)) {
            return $query->whereRaw('1 = 0');
        }

        if (!$this->hasAudienceColumns()) {
            return $query->where(function (Builder $visibility) use ($viewer) {
                $visibility->where('user_id', $viewer->id)
                    ->orWhere(function (Builder $legacy) use ($viewer) {
                        $this->applyLegacyVisibility($legacy, $viewer);
                    });

                $this->applyFollowedAuthorVisibility($visibility, $viewer);
            });
        }

        return $query->where(function (Builder $visibility) use ($viewer) {
            $visibility->where('user_id', $viewer->id)
                ->orWhere('audience_type', self::TYPE_GLOBAL)
                ->orWhere(function (Builder $scoped) use ($viewer) {
                    $scoped->whereIn('audience_type', [self::TYPE_OWN_SCOPE, self::TYPE_SPECIFIC_SCOPE])
                        ->where(function (Builder $scopeQuery) use ($viewer) {
                            $this->applyViewerScopeMatches($scopeQuery, $viewer);
                        });
                })
                ->orWhere(function (Builder $legacy) use ($viewer) {
                    $legacy->whereNull('audience_type');
                    $this->applyLegacyVisibility($legacy, $viewer);
                });

            if (SafeDatabase::hasTable('support_groups') && SafeDatabase::hasTable('user_support_group') && $viewer->supportGroups()->exists()) {
                $groupIds = $viewer->supportGroups()->pluck('support_groups.id');
                $visibility->orWhere(function (Builder $groups) use ($groupIds) {
                    $groups->where('audience_type', self::TYPE_SUPPORT_GROUP)
                        ->whereIn('audience_group_id', $groupIds);
                });
            }

            $this->applyFollowedAuthorVisibility($visibility, $viewer, true);
        });
    }

    public function optionPayloadForView(User $user): string
    {
        return e(json_encode($this->options($user), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG));
    }

    private function ownScope(User $user): array
    {
        $preferred = match ($user->access_level) {
            'superadmin', 'nationaladmin' => $this->installationScopeType(),
            'regionaladmin' => 'region',
            'stateadmin' => 'state',
            'senatorialadmin' => 'senatorial_district',
            'federaladmin' => 'federal_constituency',
            'lgaadmin' => 'lga',
            'wardadmin' => 'ward',
            default => 'pu',
        };

        foreach (array_unique([$preferred, 'pu', 'ward', 'lga', 'federal_constituency', 'senatorial_district', 'state', 'region']) as $scopeType) {
            $id = $this->userScopeId($user, $scopeType);

            if ($id && $this->scopeAllowedForUser($user, $scopeType, $id)) {
                return ['type' => $scopeType, 'id' => (int) $id];
            }
        }

        return $this->installationScope();
    }

    private function specificScopeOptions(User $user): array
    {
        $options = [];

        foreach ($this->allowedSpecificScopeTypes() as $scopeType) {
            $id = $this->userScopeId($user, $scopeType);
            if ($id && $this->scopeAllowedForUser($user, $scopeType, (int) $id)) {
                $options[] = ['type' => $scopeType, 'id' => (int) $id];
            }
        }

        return $options;
    }

    private function allowedSpecificScopeTypes(): array
    {
        return match ($this->packageScope->current()->scope_type) {
            'state' => ['state', 'senatorial_district', 'federal_constituency', 'lga', 'ward', 'pu'],
            'senatorial_district' => ['senatorial_district', 'federal_constituency', 'lga', 'ward', 'pu'],
            'federal_constituency' => ['federal_constituency', 'lga', 'ward', 'pu'],
            'lga' => ['lga', 'ward', 'pu'],
            default => ['region', 'state', 'senatorial_district', 'federal_constituency', 'lga', 'ward', 'pu'],
        };
    }

    private function supportGroupOptions(User $user): array
    {
        if (!SafeDatabase::hasTable('support_groups') || !SafeDatabase::hasTable('user_support_group')) {
            return [];
        }

        return $user->supportGroups()
            ->select('support_groups.id', 'support_groups.name')
            ->orderBy('support_groups.name')
            ->get()
            ->map(fn (SupportGroup $group) => ['id' => (int) $group->id, 'name' => (string) $group->name])
            ->all();
    }

    private function scopeAllowedForUser(User $user, string $scopeType, int $scopeId): bool
    {
        $withinUser = match ($scopeType) {
            'region' => !$user->region_id || (int) $user->region_id === $scopeId,
            'state' => !$user->state_id || (int) $user->state_id === $scopeId,
            'senatorial_district' => !$user->senatorial_district_id || (int) $user->senatorial_district_id === $scopeId,
            'federal_constituency' => !$user->federal_constituency_id || (int) $user->federal_constituency_id === $scopeId,
            'lga' => !$user->lga_id || (int) $user->lga_id === $scopeId,
            'ward' => !$user->ward_id || (int) $user->ward_id === $scopeId,
            'pu' => !$user->polling_unit_id || (int) $user->polling_unit_id === $scopeId,
            default => false,
        };

        if (!$withinUser) {
            return false;
        }

        return match ($scopeType) {
            'region' => true,
            'state' => $this->packageScope->allowsState($scopeId),
            'senatorial_district' => $this->packageScope->allowsSenatorialDistrict($scopeId),
            'federal_constituency' => $this->packageScope->allowsFederalConstituency($scopeId),
            'lga' => $this->packageScope->allowsLga($scopeId),
            'ward' => $this->packageScope->allowsWard($scopeId),
            'pu' => $this->packageScope->allowsPollingUnit($scopeId),
            default => false,
        };
    }

    private function applyViewerScopeMatches(Builder $query, User $viewer): void
    {
        $query->whereRaw('1 = 0');

        foreach (self::SCOPE_COLUMNS as $scopeType => $column) {
            $id = $this->userScopeId($viewer, $scopeType);
            if ($id) {
                $query->orWhere(function (Builder $scope) use ($scopeType, $id) {
                    $scope->where('audience_scope_type', $scopeType)
                        ->where('audience_scope_id', $id);
                });
            }
        }
    }

    private function applyLegacyVisibility(Builder $query, User $viewer): void
    {
        $query->where(function (Builder $legacy) use ($viewer) {
            $legacy->where('audience', 'public')
                ->orWhere('user_id', $viewer->id);

            foreach ([
                'region' => 'region_id',
                'state' => 'state_id',
                'lga' => 'lga_id',
                'ward' => 'ward_id',
                'pu' => 'polling_unit_id',
            ] as $audience => $column) {
                $postColumn = $audience === 'pu' ? 'pu_id' : $column;
                $id = $viewer->{$column};
                if ($id) {
                    $legacy->orWhere(function (Builder $scope) use ($audience, $postColumn, $id) {
                        $scope->where('audience', $audience)->where($postColumn, $id);
                    });
                }
            }
        });
    }

    private function applyFollowedAuthorVisibility(Builder $query, User $viewer, bool $protectSupportGroups = false): void
    {
        if (!SafeDatabase::hasTable('followers')) {
            return;
        }

        $query->orWhere(function (Builder $followedPosts) use ($viewer, $protectSupportGroups) {
            $followedPosts->whereIn('user_id', function ($following) use ($viewer) {
                $following->select('user_id')
                    ->from('followers')
                    ->where('follower_id', $viewer->id);
            });

            if ($protectSupportGroups) {
                $followedPosts->where(function (Builder $audience) {
                    $audience->whereNull('audience_type')
                        ->orWhere('audience_type', '!=', self::TYPE_SUPPORT_GROUP);
                });
            }
        });
    }

    private function userScopeId(User $user, string $scopeType): ?int
    {
        $column = self::SCOPE_COLUMNS[$scopeType] ?? null;

        return $column ? ($user->{$column} ? (int) $user->{$column} : null) : null;
    }

    private function viewerWithinLicensedScope(User $viewer): bool
    {
        $query = User::query()->whereKey($viewer->getKey());
        $this->licensedScope->applyToUsersQuery($query);

        return $query->exists();
    }

    public function hasAudienceColumns(): bool
    {
        try {
            return SafeDatabase::hasTable('posts') && Schema::hasColumn('posts', 'audience_type');
        } catch (\Throwable) {
            return false;
        }
    }

    private function installationScope(): array
    {
        $scope = $this->packageScope->current();

        return match ($scope->scope_type) {
            'state' => ['type' => 'state', 'id' => (int) $scope->state_id],
            'senatorial_district' => ['type' => 'senatorial_district', 'id' => (int) $scope->senatorial_district_id],
            'federal_constituency' => ['type' => 'federal_constituency', 'id' => (int) $scope->federal_constituency_id],
            'lga' => ['type' => 'lga', 'id' => (int) $scope->lga_id],
            default => ['type' => 'state', 'id' => (int) ($scope->state_id ?: 0)],
        };
    }

    private function installationScopeType(): string
    {
        return $this->installationScope()['type'];
    }

    private function globalLabel(): string
    {
        return 'Global';
    }

    private function scopeLabel(?string $scopeType, ?int $scopeId): string
    {
        $name = $this->scopeName($scopeType, $scopeId);

        if ($name) {
            return $name;
        }

        return match ($scopeType) {
            'region' => 'Region',
            'state' => 'State',
            'senatorial_district' => 'Senatorial District',
            'federal_constituency' => 'Federal Constituency',
            'lga' => 'LGA',
            'ward' => 'Ward',
            'pu' => 'Polling Unit',
            default => 'Campaign Scope',
        };
    }

    private function scopeName(?string $scopeType, ?int $scopeId): ?string
    {
        if (!$scopeType || !$scopeId) {
            return null;
        }

        $modelClass = match ($scopeType) {
            'region' => Region::class,
            'state' => State::class,
            'senatorial_district' => SenatorialDistrict::class,
            'federal_constituency' => FederalConstituency::class,
            'lga' => LocalGovernmentArea::class,
            'ward' => \App\Models\Ward::class,
            'pu' => PollingUnit::class,
            default => null,
        };

        if (!$modelClass || !SafeDatabase::hasTable((new $modelClass())->getTable())) {
            return null;
        }

        return $modelClass::query()->whereKey($scopeId)->value('name');
    }

    private function userBelongsToSupportGroup(User $user, int $groupId): bool
    {
        return SafeDatabase::hasTable('user_support_group')
            && $user->supportGroups()->where('support_groups.id', $groupId)->exists();
    }

    private function supportGroupName(int $groupId): ?string
    {
        if (!SafeDatabase::hasTable('support_groups')) {
            return null;
        }

        return SupportGroup::query()->whereKey($groupId)->value('name');
    }

    private function legacyAudience(?string $scopeType, string $type): string
    {
        if ($type === self::TYPE_GLOBAL) {
            return 'public';
        }

        return match ($scopeType) {
            'region' => 'region',
            'state', 'senatorial_district', 'federal_constituency' => 'state',
            'lga' => 'lga',
            'ward' => 'ward',
            'pu' => 'pu',
            default => 'public',
        };
    }

    private function typeFromLegacyAudience(string $audience): string
    {
        return $audience === 'public' ? self::TYPE_GLOBAL : self::TYPE_SPECIFIC_SCOPE;
    }

    private function scopeTypeFromLegacyAudience(?string $audience): ?string
    {
        return match ($audience) {
            'region' => 'region',
            'state' => 'state',
            'lga' => 'lga',
            'ward' => 'ward',
            'pu' => 'pu',
            default => null,
        };
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['audience_type' => $message]);
    }
}
