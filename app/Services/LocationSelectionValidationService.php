<?php

namespace App\Services;

use App\Models\LocalGovernmentArea;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Validation\ValidationException;

class LocationSelectionValidationService
{
    public function __construct(private LicensedScopeQueryService $licensedScope)
    {
    }

    public function validateMemberSelection(array $payload): array
    {
        $state = State::query()->find($payload['state_id'] ?? null);
        $lga = LocalGovernmentArea::query()->find($payload['lga_id'] ?? null);
        $ward = Ward::query()->find($payload['ward_id'] ?? null);
        $pollingUnit = PollingUnit::query()->find($payload['polling_unit_id'] ?? null);
        $errors = [];

        if (!$state || (int) $state->region_id !== (int) ($payload['region_id'] ?? 0)) {
            $errors['state_id'] = 'The selected state does not belong to the selected region.';
        }
        if (!$lga || (int) $lga->state_id !== (int) ($payload['state_id'] ?? 0)) {
            $errors['lga_id'] = 'The selected LGA does not belong to the selected state.';
        }
        if (!$ward || (int) $ward->lga_id !== (int) ($payload['lga_id'] ?? 0)) {
            $errors['ward_id'] = 'The selected ward does not belong to the selected LGA.';
        }
        if (!$pollingUnit || (int) $pollingUnit->ward_id !== (int) ($payload['ward_id'] ?? 0)) {
            $errors['polling_unit_id'] = 'The selected polling unit does not belong to the selected ward.';
        }

        $errors = array_replace($errors, $this->licensedScope->payloadErrors($payload));
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'region_id' => (int) $payload['region_id'],
            'state_id' => (int) $state->id,
            'senatorial_district_id' => $pollingUnit->senatorial_district_id ?: $lga->senatorial_district_id,
            'federal_constituency_id' => $pollingUnit->federal_constituency_id ?: $lga->federal_constituency_id,
            'lga_id' => (int) $lga->id,
            'ward_id' => (int) $ward->id,
            'polling_unit_id' => (int) $pollingUnit->id,
        ];
    }
}
