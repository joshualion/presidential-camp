<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementAudienceService
{
    public const MANAGER_LEVELS = [
        'superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin',
        'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin',
    ];

    public function __construct(private readonly LocationScopeService $locationScope)
    {
    }

    public function canPublish(?User $user): bool
    {
        if (!$user || !in_array($user->access_level, self::MANAGER_LEVELS, true) || $user->status === 'inactive') {
            return false;
        }

        return $this->locationScope->getScopeType($user) === LocationScopeService::NATIONAL
            || filled($this->locationScope->getScopeId($user));
    }

    public function publicationScope(User $publisher): array
    {
        $type = $this->locationScope->getScopeType($publisher);
        $id = $this->locationScope->getScopeId($publisher);
        $payload = [
            'audience' => 'public',
            'scope_type' => $type === LocationScopeService::NATIONAL ? 'public' : $type,
            'region_id' => null,
            'state_id' => null,
            'senatorial_district_id' => null,
            'federal_constituency_id' => null,
            'lga_id' => null,
            'ward_id' => null,
            'polling_unit_id' => null,
        ];

        if ($type !== LocationScopeService::NATIONAL) {
            $payload[$this->scopeColumn($type)] = $id;
        }

        return $payload;
    }

    public function publicationScopeLabel(User $publisher): string
    {
        $type = $this->locationScope->getScopeType($publisher);
        $id = $this->locationScope->getScopeId($publisher);

        return match ($type) {
            LocationScopeService::REGION => 'Region: '.(Region::find($id)?->name ?? 'Unassigned'),
            LocationScopeService::STATE => 'State: '.(State::find($id)?->name ?? 'Unassigned'),
            LocationScopeService::SENATORIAL => 'Senatorial District: '.(SenatorialDistrict::find($id)?->name ?? 'Unassigned'),
            LocationScopeService::FEDERAL => 'Federal Constituency: '.(FederalConstituency::find($id)?->name ?? 'Unassigned'),
            LocationScopeService::LGA => 'LGA: '.(LocalGovernmentArea::find($id)?->name ?? 'Unassigned'),
            LocationScopeService::WARD => 'Ward: '.(Ward::find($id)?->name ?? 'Unassigned'),
            LocationScopeService::POLLING_UNIT => 'Polling Unit: '.(PollingUnit::find($id)?->name ?? 'Unassigned'),
            default => 'All campaign members',
        };
    }

    public function visibleToMember(User $member): Builder
    {
        return Announcement::query()
            ->with(['creator:id,firstname,lastname,username', 'region', 'state', 'senatorialDistrict', 'federalConstituency', 'lga', 'ward', 'pollingUnit'])
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(function (Builder $query) use ($member) {
                $query->where('scope_type', 'public');
                $this->orScope($query, LocationScopeService::REGION, 'region_id', $member->region_id);
                $this->orScope($query, LocationScopeService::STATE, 'state_id', $member->state_id);
                $this->orScope($query, LocationScopeService::SENATORIAL, 'senatorial_district_id', $member->senatorial_district_id);
                $this->orScope($query, LocationScopeService::FEDERAL, 'federal_constituency_id', $member->federal_constituency_id);
                $this->orScope($query, LocationScopeService::LGA, 'lga_id', $member->lga_id);
                $this->orScope($query, LocationScopeService::WARD, 'ward_id', $member->ward_id);
                $this->orScope($query, LocationScopeService::POLLING_UNIT, 'polling_unit_id', $member->polling_unit_id);
            })
            ->orderByDesc('priority')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function manageableBy(User $manager): Builder
    {
        $query = Announcement::query()->with(['creator:id,firstname,lastname,username', 'region', 'state', 'senatorialDistrict', 'federalConstituency', 'lga', 'ward', 'pollingUnit']);

        if (!in_array($manager->access_level, ['superadmin', 'nationaladmin'], true)) {
            $query->where('created_by', $manager->id);
        }

        return $query->latest('id');
    }

    public function canManage(User $manager, Announcement $announcement): bool
    {
        return $this->canPublish($manager)
            && (in_array($manager->access_level, ['superadmin', 'nationaladmin'], true)
                || (int) $announcement->created_by === (int) $manager->id);
    }

    public function scopeLabel(Announcement $announcement): string
    {
        return match ($announcement->scope_type) {
            LocationScopeService::REGION => 'Region: '.($announcement->region?->name ?? 'Unknown'),
            LocationScopeService::STATE => 'State: '.($announcement->state?->name ?? 'Unknown'),
            LocationScopeService::SENATORIAL => 'Senatorial District: '.($announcement->senatorialDistrict?->name ?? 'Unknown'),
            LocationScopeService::FEDERAL => 'Federal Constituency: '.($announcement->federalConstituency?->name ?? 'Unknown'),
            LocationScopeService::LGA => 'LGA: '.($announcement->lga?->name ?? 'Unknown'),
            LocationScopeService::WARD => 'Ward: '.($announcement->ward?->name ?? 'Unknown'),
            LocationScopeService::POLLING_UNIT => 'Polling Unit: '.($announcement->pollingUnit?->name ?? 'Unknown'),
            default => 'All campaign members',
        };
    }

    private function scopeColumn(string $scopeType): string
    {
        return match ($scopeType) {
            LocationScopeService::REGION => 'region_id',
            LocationScopeService::STATE => 'state_id',
            LocationScopeService::SENATORIAL => 'senatorial_district_id',
            LocationScopeService::FEDERAL => 'federal_constituency_id',
            LocationScopeService::LGA => 'lga_id',
            LocationScopeService::WARD => 'ward_id',
            LocationScopeService::POLLING_UNIT => 'polling_unit_id',
            default => throw new \InvalidArgumentException('Unsupported announcement scope.'),
        };
    }

    private function orScope(Builder $query, string $type, string $column, mixed $value): void
    {
        if (filled($value)) {
            $query->orWhere(fn (Builder $scope) => $scope->where('scope_type', $type)->where($column, $value));
        }
    }
}
