<?php

namespace App\Services;

class CampaignPackageUiService
{
    public function __construct(private PackageScopeService $scopeService)
    {
    }

    public function dashboardTitle(): string
    {
        return $this->label('dashboard_title', 'National Campaign Dashboard');
    }

    public function statisticsTitle(): string
    {
        return $this->label('statistics_title', 'National Statistics');
    }

    public function electionStatisticsTitle(): string
    {
        return $this->label('election_statistics_title', 'Election Statistics');
    }

    public function electionStatisticsTitleForScope(?string $scopeType): string
    {
        return match ($scopeType) {
            LocationScopeService::REGION => 'Regional Election Statistics',
            LocationScopeService::STATE => 'State Election Statistics',
            LocationScopeService::SENATORIAL => 'Senatorial District Election Statistics',
            LocationScopeService::FEDERAL => 'Federal Constituency Election Statistics',
            LocationScopeService::LGA => 'LGA Election Statistics',
            LocationScopeService::WARD => 'Ward Election Statistics',
            LocationScopeService::POLLING_UNIT => 'Polling Unit Election Statistics',
            default => $this->electionStatisticsTitle(),
        };
    }

    public function geographyDistributionTitle(): string
    {
        return $this->label('geography_distribution_title', 'Members Distribution by Geopolitical Region');
    }

    public function primaryGeographyLabel(): string
    {
        return $this->label('primary_geography_label', 'Regions');
    }

    public function secondaryGeographyLabels(): array
    {
        return $this->arrayLabel('secondary_geography_labels', ['States', 'LGAs', 'Wards', 'Polling Units']);
    }

    public function dashboardScopeTitle(): string
    {
        return $this->scopeDisplayName() ?: $this->label('dashboard_scope_title', 'National Campaign');
    }

    public function locationHierarchyLabels(): array
    {
        return $this->arrayLabel('location_hierarchy_labels', ['Regions', 'States', 'LGAs', 'Wards', 'Polling Units']);
    }

    public function memberNavigationLevels(): array
    {
        return match ($this->scopeService->packageType()) {
            PackageGovernanceService::PRESIDENTIAL => ['regions', 'states', 'lgas', 'wards', 'polling_units'],
            PackageGovernanceService::GOVERNORSHIP,
            PackageGovernanceService::SENATORIAL,
            PackageGovernanceService::FEDERAL => ['lgas', 'wards', 'polling_units'],
            PackageGovernanceService::CHAIRMANSHIP, 'lga' => ['wards', 'polling_units'],
            default => ['lgas', 'wards', 'polling_units'],
        };
    }

    public function supportsMemberNavigationLevel(string $level): bool
    {
        return in_array($level, $this->memberNavigationLevels(), true);
    }

    public function emptyStateMessage(string $context): string
    {
        $context = trim($context) ?: 'data';

        return "No {$context} is available yet.";
    }

    public function packageDisplayName(): string
    {
        return $this->label('package_display_name', 'Presidential Campaign');
    }

    public function scopeDisplayName(): ?string
    {
        $scope = $this->scopeService->current();

        return $scope->scope_name
            ?: $scope->state_name
            ?: $scope->senatorial_district_name
            ?: $scope->federal_constituency_name
            ?: $scope->lga_name;
    }

    public function chartTitle(string $chartKey): string
    {
        return data_get($this->packageLabels(), "charts.{$chartKey}")
            ?: str($chartKey)->replace(['_', '-'], ' ')->title()->toString();
    }

    public function cardGroupTitle(string $groupKey): string
    {
        return config("campaign_ui.card_groups.{$groupKey}")
            ?: str($groupKey)->replace(['_', '-'], ' ')->title()->toString();
    }

    public function breadcrumbLabel(string $level): string
    {
        return config("campaign_ui.breadcrumbs.{$level}")
            ?: str($level)->replace(['_', '-'], ' ')->title()->toString();
    }

    public function supportsChart(string $chartKey): bool
    {
        return in_array($chartKey, $this->visibleChartKeys(), true);
    }

    public function visibleChartKeys(): array
    {
        return $this->arrayLabel('visible_charts', ['gender', 'age', 'geography', 'voter', 'religion', 'state']);
    }

    public function labels(): array
    {
        return [
            'dashboard_title' => $this->dashboardTitle(),
            'statistics_title' => $this->statisticsTitle(),
            'election_statistics_title' => $this->electionStatisticsTitle(),
            'geography_distribution_title' => $this->geographyDistributionTitle(),
            'primary_geography_label' => $this->primaryGeographyLabel(),
            'secondary_geography_labels' => $this->secondaryGeographyLabels(),
            'location_hierarchy_labels' => $this->locationHierarchyLabels(),
            'member_navigation_levels' => $this->memberNavigationLevels(),
            'package_display_name' => $this->packageDisplayName(),
            'scope_display_name' => $this->scopeDisplayName(),
            'visible_chart_keys' => $this->visibleChartKeys(),
        ];
    }

    private function label(string $key, string $fallback): string
    {
        $value = $this->packageLabels()[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private function arrayLabel(string $key, array $fallback): array
    {
        $value = $this->packageLabels()[$key] ?? null;

        return is_array($value) ? array_values($value) : $fallback;
    }

    private function packageLabels(): array
    {
        $package = $this->scopeService->packageType() ?: PackageGovernanceService::PRESIDENTIAL;
        $package = $package === 'lga' ? PackageGovernanceService::CHAIRMANSHIP : $package;

        return config("campaign_ui.packages.{$package}")
            ?: config('campaign_ui.packages.presidential', []);
    }
}
