<?php

namespace App\Services;

use App\Models\AgeGrade;
use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\Religion;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Support\SafeDatabase;
use Illuminate\Database\Eloquent\Builder;

class CampaignDashboardChartService
{
    private const COLORS = [
        '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c',
        '#d35400', '#c0392b', '#34495e', '#27ae60', '#2980b9', '#8e44ad',
        '#16a085', '#7f8c8d', '#2c3e50', '#f1c40f', '#e67e22', '#95a5a6',
    ];

    public function __construct(
        private LocationScopeService $locationScope,
        private LicensedScopeQueryService $licensedScope,
        private CampaignPackageUiService $packageUi,
        private PackageScopeService $packageScope,
    ) {
    }

    public function visibleCharts(): array
    {
        return collect($this->packageUi->visibleChartKeys())
            ->map(fn (string $key) => [
                'key' => $key,
                'title' => $this->packageUi->chartTitle($key),
                'empty_message' => $this->emptyMessageFor($key),
            ])
            ->values()
            ->all();
    }

    public function gender(?User $viewer = null, ?array $boundary = null): array
    {
        $data = [
            $this->countMembersByGender('male', $viewer, $boundary),
            $this->countMembersByGender('female', $viewer, $boundary),
        ];

        return $this->payload(['Male', 'Female'], $data, 'Members', 'gender');
    }

    private function countMembersByGender(string $gender, ?User $viewer = null, ?array $boundary = null): int
    {
        return $this->memberQuery($viewer, $boundary)
            ->whereRaw('LOWER(TRIM(gender)) = ?', [$gender])
            ->count();
    }

    public function age(?User $viewer = null, ?array $boundary = null): array
    {
        $ageGrades = SafeDatabase::hasTable('age_grades') ? AgeGrade::query()->orderBy('id')->get() : collect();

        $data = $ageGrades
            ->map(fn (AgeGrade $ageGrade) => $this->memberQuery($viewer, $boundary)->where('age_grade_id', $ageGrade->id)->count())
            ->all();

        return $this->payload($ageGrades->pluck('name')->all(), $data, 'Members', 'age');
    }

    public function religion(?User $viewer = null, ?array $boundary = null): array
    {
        $religions = SafeDatabase::hasTable('religions') ? Religion::query()->orderBy('id')->get() : collect();

        $data = $religions
            ->map(fn (Religion $religion) => $this->memberQuery($viewer, $boundary)->where('religion_id', $religion->id)->count())
            ->all();

        return $this->payload($religions->pluck('name')->all(), $data, 'Members', 'religion');
    }

    public function voter(?User $viewer = null, ?array $boundary = null): array
    {
        $data = [
            $this->memberQuery($viewer, $boundary)->whereIn('validVoter', ['Yes', 'yes'])->count(),
            $this->memberQuery($viewer, $boundary)->whereIn('validVoter', ['No', 'no'])->count(),
        ];

        return $this->payload(['Eligible Voters', 'Ineligible Voters'], $data, 'Members', 'voter');
    }

    public function regions(?User $viewer = null): array
    {
        $query = Region::query()->orderBy('name');
        $this->licensedScope->applyToRegionsQuery($query);

        return $this->locationPayload($query->get(), 'region_id', 'region', $viewer, 'geography');
    }

    public function packageGeography(?User $viewer = null): array
    {
        $package = $this->packageScope->packageType();

        return match ($package) {
            PackageGovernanceService::PRESIDENTIAL => $this->states($viewer),
            PackageGovernanceService::GOVERNORSHIP,
            PackageGovernanceService::SENATORIAL,
            PackageGovernanceService::FEDERAL => $this->lgas($viewer),
            PackageGovernanceService::CHAIRMANSHIP, 'lga' => $this->wards($viewer),
            default => $this->lgas($viewer),
        };
    }

    public function states(?User $viewer = null, ?array $boundary = null): array
    {
        $query = State::query()->orderBy('name');
        $this->licensedScope->applyToStatesQuery($query);
        $this->applyLocationBoundary($query, $boundary);

        return $this->locationPayload($query->get(), 'state_id', 'state', $viewer, 'state');
    }

    public function lgas(?User $viewer = null, ?array $boundary = null): array
    {
        $query = LocalGovernmentArea::query()->orderBy('name');
        $this->licensedScope->applyToLgasQuery($query);
        $this->applyLocationBoundary($query, $boundary);

        return $this->locationPayload($query->get(), 'lga_id', 'lga', $viewer, 'geography');
    }

    public function wards(?User $viewer = null, ?array $boundary = null): array
    {
        $query = Ward::query()->orderBy('name');
        $this->licensedScope->applyToWardsQuery($query);
        $this->applyLocationBoundary($query, $boundary);

        return $this->locationPayload($query->get(), 'ward_id', 'ward', $viewer, 'geography');
    }

    public function pollingUnitCoverage(?User $viewer = null, ?array $boundary = null): array
    {
        $query = PollingUnit::query()->orderBy('name');
        $this->licensedScope->applyToPollingUnitsQuery($query);
        $this->applyLocationBoundary($query, $boundary);

        return $this->locationPayload($query->get(), 'polling_unit_id', 'polling_unit', $viewer, 'polling_unit_coverage');
    }

    public function senatorialDistricts(?User $viewer = null, ?array $boundary = null): array
    {
        $query = SenatorialDistrict::query()->orderBy('name');
        $this->licensedScope->applyToSenatorialDistrictsQuery($query);
        $this->applyLocationBoundary($query, $boundary);

        return $this->locationPayload($query->get(), 'senatorial_district_id', 'senatorial_district', $viewer, 'senatorial_distribution');
    }

    public function federalConstituencies(?User $viewer = null, ?array $boundary = null): array
    {
        $query = FederalConstituency::query()->orderBy('name');
        $this->licensedScope->applyToFederalConstituenciesQuery($query);
        $this->applyLocationBoundary($query, $boundary);

        return $this->locationPayload($query->get(), 'federal_constituency_id', 'federal_constituency', $viewer, 'federal_constituency_distribution');
    }

    private function memberQuery(?User $viewer = null, ?array $boundary = null): Builder
    {
        $query = User::query()->where('access_level', '!=', 'superadmin');
        $this->locationScope->applyScope($query, $viewer, 'users', 'users');

        if ($boundary) {
            $this->locationScope->applyBoundaryFilter($query, $boundary['type'], (int) $boundary['id'], 'users', 'users');
        }

        return $query;
    }

    private function locationPayload($locations, string $userColumn, string $boundaryType, ?User $viewer, string $chartKey): array
    {
        $labels = [];
        $data = [];

        foreach ($locations as $location) {
            $labels[] = $location->name;
            $data[] = $this->memberQuery($viewer, ['type' => $boundaryType, 'id' => $location->id])
                ->where($userColumn, $location->id)
                ->count();
        }

        return $this->payload($labels, $data, 'Members', $chartKey);
    }

    private function payload(array $labels, array $data, string $datasetLabel, string $chartKey): array
    {
        $labels = array_values($labels);
        $data = array_map(fn ($value) => (int) $value, array_values($data));
        $total = array_sum($data);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $datasetLabel,
                    'data' => $data,
                    'backgroundColor' => array_slice($this->colors(count($data)), 0, max(count($data), 1)),
                ],
            ],
            'total' => $total,
            'empty' => $total === 0,
            'message' => $this->emptyMessageFor($chartKey),
        ];
    }

    private function colors(int $count): array
    {
        if ($count <= count(self::COLORS)) {
            return self::COLORS;
        }

        return array_merge(self::COLORS, array_fill(0, $count - count(self::COLORS), '#3498db'));
    }

    private function emptyMessageFor(string $chartKey): string
    {
        return match ($chartKey) {
            'gender', 'age', 'religion', 'voter' => 'No member data available yet.',
            'polling_unit_coverage' => 'No polling unit coverage data available yet.',
            default => 'No geographic distribution data available yet.',
        };
    }

    private function applyLocationBoundary(Builder $query, ?array $boundary): void
    {
        if (!$boundary) {
            return;
        }

        $type = $boundary['type'];
        $id = (int) $boundary['id'];

        match ($type) {
            'region' => $query->where('region_id', $id),
            'state' => $query->where('state_id', $id),
            'senatorial_district' => $query->where('senatorial_district_id', $id),
            'federal_constituency' => $query->where('federal_constituency_id', $id),
            'lga' => $query->where('lga_id', $id),
            'ward' => $query->where('ward_id', $id),
            default => null,
        };
    }
}
