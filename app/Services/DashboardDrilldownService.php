<?php

namespace App\Services;

use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DashboardDrilldownService
{
    public function __construct(
        private LicensedScopeQueryService $licensedScope,
        private CampaignPackageUiService $packageUi,
    ) {
    }

    public function region(string $uuid): Region
    {
        /** @var Region $region */
        $region = $this->findByUuid(Region::query(), $uuid, 'Region');
        $this->licensedScope->abortIfRegionNotAllowed($region->id);

        return $region;
    }

    public function state(string $uuid): State
    {
        /** @var State $state */
        $state = $this->findByUuid(State::query(), $uuid, 'State');
        $this->licensedScope->abortIfStateNotAllowed($state->id);

        return $state;
    }

    public function senatorialDistrict(string $uuid): SenatorialDistrict
    {
        /** @var SenatorialDistrict $district */
        $district = $this->findByUuid(SenatorialDistrict::query()->with('state'), $uuid, 'Senatorial district');
        $this->licensedScope->abortIfSenatorialDistrictNotAllowed($district->id);

        return $district;
    }

    public function federalConstituency(string $uuid): FederalConstituency
    {
        /** @var FederalConstituency $constituency */
        $constituency = $this->findByUuid(FederalConstituency::query()->with('state'), $uuid, 'Federal constituency');
        $this->licensedScope->abortIfFederalConstituencyNotAllowed($constituency->id);

        return $constituency;
    }

    public function lga(string $uuid): LocalGovernmentArea
    {
        /** @var LocalGovernmentArea $lga */
        $lga = $this->findByUuid(LocalGovernmentArea::query()->with(['state']), $uuid, 'LGA');
        $this->licensedScope->abortIfLgaNotAllowed($lga->id);

        return $lga;
    }

    public function ward(string $uuid): Ward
    {
        /** @var Ward $ward */
        $ward = $this->findByUuid(Ward::query()->with(['lga', 'lga.state']), $uuid, 'Ward');
        $this->licensedScope->abortIfWardNotAllowed($ward->id);

        return $ward;
    }

    public function pollingUnit(string $uuid): PollingUnit
    {
        /** @var PollingUnit $pollingUnit */
        $pollingUnit = $this->findByUuid(PollingUnit::query()->with(['ward', 'ward.lga']), $uuid, 'Polling unit');
        $this->licensedScope->abortIfPollingUnitNotAllowed($pollingUnit->id);

        return $pollingUnit;
    }

    public function title(Model $location, string $type): string
    {
        $name = trim((string) ($location->getAttribute('name') ?: $location->getAttribute('polling_unit_name')));
        $label = match ($type) {
            'region' => $this->packageUi->breadcrumbLabel('region'),
            'state' => $this->packageUi->breadcrumbLabel('state'),
            'senatorial_district' => $this->packageUi->breadcrumbLabel('senatorial_district'),
            'federal_constituency' => $this->packageUi->breadcrumbLabel('federal_constituency'),
            'lga' => $this->packageUi->breadcrumbLabel('lga'),
            'ward' => $this->packageUi->breadcrumbLabel('ward'),
            'polling_unit' => $this->packageUi->breadcrumbLabel('polling_unit'),
            default => 'Campaign',
        };

        return trim(($name ? "{$name} " : '')."{$label} Dashboard");
    }

    public function viewModel(Model $location, string $type, array $extra = []): array
    {
        return array_merge([
            'location' => $location,
            'locationType' => $type,
            'title' => $this->title($location, $type),
            'breadcrumb' => [
                ['label' => $this->packageUi->dashboardTitle(), 'url' => null],
                ['label' => $this->title($location, $type), 'url' => null],
            ],
        ], $extra);
    }

    private function findByUuid(Builder $query, string $uuid, string $label): Model
    {
        $record = $query->where('uuid', $uuid)->first();

        abort_unless($record, 404, "{$label} dashboard record was not found.");

        return $record;
    }
}
