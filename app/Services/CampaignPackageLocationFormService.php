<?php

namespace App\Services;

use App\Models\FederalConstituency;
use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\Region;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Models\User;
use App\Models\Ward;
use App\Support\SafeDatabase;
use Illuminate\Database\Eloquent\Builder;

class CampaignPackageLocationFormService
{
    public function __construct(
        private PackageScopeService $scopeService,
        private LicensedScopeQueryService $licensedScope,
        private StructuralLocationAccessService $structuralAccess,
        private CampaignPackageUiService $packageUi,
    ) {
    }

    public function context(?User $actor = null, ?User $subject = null, ?string $accessLevel = null): array
    {
        $fixed = $this->fixedValues();
        $labels = $this->fixedLabels($fixed);
        $package = $this->scopeService->current();

        return [
            'active' => $fixed !== [],
            'package' => $package->package_type,
            'scope_type' => $package->scope_type,
            'scope_name' => $this->packageUi->scopeDisplayName(),
            'message' => $this->contextMessage($fixed, $labels),
            'fixed' => $fixed,
            'fixed_labels' => $labels,
            'hidden_inputs' => $this->hiddenInputs($fixed, $subject),
            'visible_fields' => $this->visibleFields($fixed),
            'access_level' => $accessLevel ?: $subject?->access_level ?: $actor?->access_level,
        ];
    }

    public function options(?User $actor = null): array
    {
        return [
            'regions' => $this->scopedOptions(Region::query()->orderBy('name'), $actor, 'regions')->get(),
            'states' => $this->scopedOptions(State::query()->orderBy('name'), $actor, 'states')->get(),
            'senatorialDistricts' => $this->scopedOptions(SenatorialDistrict::query()->orderBy('name'), $actor, 'senatorial_districts')->get(),
            'federalConstituencies' => $this->scopedOptions(FederalConstituency::query()->orderBy('name'), $actor, 'federal_constituencies')->get(),
            'lgas' => $this->scopedOptions(LocalGovernmentArea::query()->orderBy('name'), $actor, 'local_government_areas')->get(),
            'wards' => $this->scopedOptions(Ward::query()->orderBy('name'), $actor, 'wards')->get(),
            'pollingUnits' => $this->scopedOptions(PollingUnit::query()->orderBy('name'), $actor, 'polling_units')->get(),
        ];
    }

    public function mergeFixedPayload(array $payload): array
    {
        foreach ($this->fixedValues() as $field => $value) {
            if ($value !== null && $value !== '') {
                $payload[$field] = $value;
            }
        }

        if (isset($payload['polling_unit_id']) && !isset($payload['pu_id'])) {
            $payload['pu_id'] = $payload['polling_unit_id'];
        }

        return $payload;
    }

    public function validatePayload(array $payload): void
    {
        $this->licensedScope->assertPayloadWithinScope($payload);
    }

    public function fixedValues(): array
    {
        if (!$this->scopeService->hasLocalLicense() || !$this->scopeService->isSelfHosted()) {
            return [];
        }

        $scope = $this->scopeService->current();
        $fixed = [];

        $stateId = $scope->state_id;
        $senatorialDistrictId = $scope->senatorial_district_id;
        $federalConstituencyId = $scope->federal_constituency_id;
        $lgaId = $scope->lga_id;

        $district = null;
        $constituency = null;
        $lga = null;

        if ($scope->scope_type === 'senatorial_district' && $senatorialDistrictId) {
            $district = $this->find(SenatorialDistrict::class, $senatorialDistrictId);
            $senatorialDistrictId = $district?->id;
            $stateId ??= $district?->state_id;
        }

        if ($scope->scope_type === 'federal_constituency' && $federalConstituencyId) {
            $constituency = $this->find(FederalConstituency::class, $federalConstituencyId);
            $federalConstituencyId = $constituency?->id;
            $stateId ??= $constituency?->state_id;
            $senatorialDistrictId ??= $constituency?->senatorial_district_id;
        }

        if ($scope->scope_type === 'lga' && $lgaId) {
            $lga = $this->find(LocalGovernmentArea::class, $lgaId);
            $lgaId = $lga?->id;
            $stateId ??= $lga?->state_id;
            $senatorialDistrictId ??= $lga?->senatorial_district_id;
            $federalConstituencyId ??= $lga?->federal_constituency_id;
        }

        $state = $stateId ? $this->find(State::class, $stateId) : null;
        $stateId = $state?->id;

        if ($stateId) {
            $fixed['state_id'] = (int) $stateId;
            $regionId = $state?->region_id;
            if ($regionId) {
                $fixed['region_id'] = (int) $regionId;
            }
        }

        if ($scope->scope_type === 'senatorial_district' && $senatorialDistrictId) {
            $fixed['senatorial_district_id'] = (int) $senatorialDistrictId;
        }

        if ($scope->scope_type === 'federal_constituency' && $federalConstituencyId) {
            if ($senatorialDistrictId) {
                $fixed['senatorial_district_id'] = (int) $senatorialDistrictId;
            }
            $fixed['federal_constituency_id'] = (int) $federalConstituencyId;
        }

        if ($scope->scope_type === 'lga' && $lgaId) {
            if ($senatorialDistrictId) {
                $fixed['senatorial_district_id'] = (int) $senatorialDistrictId;
            }
            if ($federalConstituencyId) {
                $fixed['federal_constituency_id'] = (int) $federalConstituencyId;
            }
            $fixed['lga_id'] = (int) $lgaId;
        }

        return $fixed;
    }

    private function scopedOptions(Builder $query, ?User $actor, string $subject): Builder
    {
        if ($actor) {
            $this->structuralAccess->applyScope($query, $actor, $subject);

            return $query;
        }

        $this->licensedScope->applyToSubject($query, $subject);

        return $query;
    }

    private function visibleFields(array $fixed): array
    {
        $fields = [
            'region_id',
            'state_id',
            'senatorial_district_id',
            'federal_constituency_id',
            'lga_id',
            'ward_id',
            'polling_unit_id',
            'pu_id',
        ];

        return collect($fields)
            ->mapWithKeys(fn (string $field) => [$field => !array_key_exists($field, $fixed)])
            ->all();
    }

    private function hiddenInputs(array $fixed, ?User $subject): array
    {
        $inputs = $fixed;

        if ($subject) {
            foreach (['ward_id', 'polling_unit_id'] as $field) {
                if (!array_key_exists($field, $inputs) && $subject->{$field}) {
                    $inputs[$field] = $subject->{$field};
                }
            }
        }

        return $inputs;
    }

    private function fixedLabels(array $fixed): array
    {
        $labels = [];

        if (isset($fixed['region_id'])) {
            $labels['region_id'] = Region::query()->whereKey($fixed['region_id'])->value('name');
        }
        if (isset($fixed['state_id'])) {
            $labels['state_id'] = State::query()->whereKey($fixed['state_id'])->value('name');
        }
        if (isset($fixed['senatorial_district_id'])) {
            $labels['senatorial_district_id'] = SenatorialDistrict::query()->whereKey($fixed['senatorial_district_id'])->value('name');
        }
        if (isset($fixed['federal_constituency_id'])) {
            $labels['federal_constituency_id'] = FederalConstituency::query()->whereKey($fixed['federal_constituency_id'])->value('name');
        }
        if (isset($fixed['lga_id'])) {
            $labels['lga_id'] = LocalGovernmentArea::query()->whereKey($fixed['lga_id'])->value('name');
        }

        return array_filter($labels);
    }

    private function contextMessage(array $fixed, array $labels): ?string
    {
        if ($fixed === []) {
            return null;
        }

        $scopeName = $this->packageUi->scopeDisplayName();

        if ($scopeName) {
            return "This installation is licensed for {$scopeName}. Fixed parent geography is applied automatically.";
        }

        return 'Fixed licensed geography is applied automatically for this installation.';
    }

    private function find(string $modelClass, ?int $id): mixed
    {
        if (!$id || !SafeDatabase::hasTable((new $modelClass())->getTable())) {
            return null;
        }

        return $modelClass::query()->find($id);
    }
}
