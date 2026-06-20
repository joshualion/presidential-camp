<?php 

namespace App\Imports;

use App\Models\Ward;
use App\Models\User;
use App\Models\LocalGovernmentArea;
use App\Models\State;
use App\Services\LicensedScopeQueryService;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WardImport implements ToModel, WithHeadingRow
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

        $lga = LocalGovernmentArea::where('name', $row['local_government'])
                                  ->where('state_id', $state->id)
                                  ->first();

        if (!$lga) {
            $this->failedInserts++;
            return null;
        }

        if (!app(LicensedScopeQueryService::class)->allowsPayload(['lga_id' => $lga->id])) {
            $this->failedInserts++;
            return null;
        }

        $existingWard = Ward::where('name', $row['name'])
                            ->where('lga_id', $lga->id)
                            ->first();

        if ($existingWard) {
            $this->duplicates++;
            return null;
        }

        $this->successfulInserts++;
        return new Ward([
            'name' => $row['name'],
            'user_id' => $user_id ?? null,
            'lga_id' => $lga->id,
        ]);
    }
}
