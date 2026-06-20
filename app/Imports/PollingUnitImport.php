<?php 

namespace App\Imports;

use App\Models\PollingUnit;
use App\Models\User;
use App\Models\Ward;
use App\Models\LocalGovernmentArea;
use App\Models\State;
use App\Services\LicensedScopeQueryService;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class PollingUnitImport implements ToModel, WithHeadingRow, SkipsOnFailure
{
    use SkipsFailures;

    public $successfulInserts = 0; // Track successful inserts
    public $failedInserts = 0; // Track failed inserts
    public $duplicates = 0; // Track duplicates

    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
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

        $ward = Ward::where('name', $row['ward_name'])
            ->where('lga_id', $lga->id)
            ->first();
        if (!$ward) {
            $this->failedInserts++;
            return null;
        }

        if (!app(LicensedScopeQueryService::class)->allowsPayload(['ward_id' => $ward->id])) {
            $this->failedInserts++;
            return null;
        }


        $existingPollingUnit = PollingUnit::where('name', $row['name'])
                                ->where('ward_id', $ward->id)
                                ->first();
        if ($existingPollingUnit) {
            $this->duplicates++; // Increment duplicates counter
            return null;
        }

        $this->successfulInserts++;
        return new PollingUnit([
            'name' => $row['name'],
            'remarks' => $row['remarks'] ?? null,
            'user_id' => $user_id ?? null,
            'ward_id' => $ward->id,
        ]);
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failedInserts++;
        }
    }
}
