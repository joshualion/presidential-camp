<?php 

namespace App\Imports;

use App\Models\Ward;
use App\Models\User;
use App\Models\LocalGovernmentArea;
use App\Models\State;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class WardImport implements ToModel, WithHeadingRow, SkipsOnFailure
{
    use SkipsFailures;

    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
    }

    public function model(array $row)
    {
        // Get User Id
        $profileData = $this->getProfileData();
        $user_id = $profileData->id;

        // Find the state by name
        $state = State::where('name', $row['state'])->first();

        if (!$state) {
            // Log an error for the missing state
            return null; // SkipsFailures will handle this
        }

        // Find the local government based on state and LGA name
        $lga = LocalGovernmentArea::where('name', $row['local_government'])
            ->where('state_id', $state->id)
            ->first();

        if (!$lga) {
            // Log an error for the missing LGA
            return null; // SkipsFailures will handle this
        }

       

        // Check if a polling unit with the same name already exists in the chosen ward
        $existingWard = Ward::where('name', $row['name'])
                                ->where('lga_id', $lga->id)
                                ->first();

        if ($existingWard) {
            // Skip the creation to avoid duplicates
            return null;
        }

        return new Ward([
            'name' => $row['name'],
            'user_id' => $user_id ?? null, // Nullable
            'lga_id' => $lga->id, // Found via state and LGA
        ]);
    }

    public function onFailure(Failure ...$failures)
    {
        // You can log or process failures if needed
        foreach ($failures as $failure) {
            // Add failure messages if needed
        }
    }

    public function getFailures()
    {
        return $this->failures;
    }
}
