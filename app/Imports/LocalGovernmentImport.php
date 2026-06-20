<?php 

namespace App\Imports;


use App\Models\User;
use App\Models\LocalGovernmentArea;
use App\Models\State;
use App\Services\LicensedScopeQueryService;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LocalGovernmentImport implements ToModel, WithHeadingRow
{
    public $successfulInserts = 0;
    public $failedInserts = 0;
    public $duplicates = 0;

    private function getProfileData()
    {
        return Auth::user();
    }

    public function model(array $row)
    {
        $profileData = $this->getProfileData();
        $user_id = $profileData->id;

        $state = State::where('name', $row['state'])->first();
        if (!$state) {
            $this->failedInserts++;
            return null;
        }

        if (!app(LicensedScopeQueryService::class)->allowsPayload(['state_id' => $state->id])) {
            $this->failedInserts++;
            return null;
        }

        $existingLocalGovernmentArea = LocalGovernmentArea::where('name', $row['name'])
                            ->where('state_id', $state->id)
                            ->first();

        if ($existingLocalGovernmentArea) {
            $this->duplicates++;
            return null;
        }

        $this->successfulInserts++;
        return new LocalGovernmentArea([
            'name' => $row['name'],
            'user_id' => $user_id ?? null,
            'state_id' => $state->id,
        ]);
    }
}
