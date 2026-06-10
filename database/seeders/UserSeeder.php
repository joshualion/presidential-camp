<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\LocalGovernmentArea;
use App\Models\Ward;
use App\Models\PollingUnit;
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $lgas = LocalGovernmentArea::all();
        $wards = Ward::all();
        $pus = PollingUnit::all();

        // 1. Create PU-level members (1 main + 5 buffer)
        foreach ($pus as $pu) {
            $ward = $wards->where('id', $pu->ward_id)->first();
            $lga = $lgas->where('id', $ward->lga_id ?? $pu->lga_id)->first();

            for ($i = 0; $i < 2; $i++) {
                $user = User::factory()->create([
                    'lga_id' => $lga?->id,
                    'ward_id' => $ward?->id,
                    'polling_unit_id' => $pu->id,
                    'access_level' => 'user',
                ]);

                $user->assignRole('member'); // 👈 Spatie magic
            }
        }

        // 2. Create 1 Ward Mobilizer per Ward
        foreach ($wards as $ward) {
            $lga = $lgas->where('id', $ward->lga_id)->first();
            $wardAdmin = User::factory()->create([
                'lga_id' => $lga?->id,
                'ward_id' => $ward->id,
                'access_level' => 'wardadmin',
            ]);
            $wardAdmin->assignRole('Ward Coordinator'); // 👈 Spatie magic
        }

        // 3. Create 1 LGA Coordinator per LGA
        foreach ($lgas as $lga) {
            $lgaAdmin = User::factory()->create([
                'lga_id' => $lga->id,
                'access_level' => 'lgaadmin',
                'ward_id' => null,
                'polling_unit_id' => null,
            ]);
            $lgaAdmin->assignRole('LGA Coordinator'); // 👈 Spatie magic
        }

        $total = User::count();
        echo "🎉 Successfully seeded $total members!\n";
    }
}
