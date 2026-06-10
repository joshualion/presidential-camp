<?php

namespace Database\Seeders;

use App\Models\AgeGrade;
use App\Models\Country;
use App\Models\FederalConstituency;
use App\Models\Religion;
use App\Models\SenatorialDistrict;
use App\Models\SupportGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ElectoralBoundaryDemoUsersSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $supportGroup = SupportGroup::query()->first();

        if (!$supportGroup) {
            $this->command?->warn('No support group exists. Demo users were not created because onboarding requires support-group membership.');
            return;
        }

        $senatorialDistrict = SenatorialDistrict::query()
            ->with('state')
            ->whereNotNull('state_id')
            ->orderBy('id')
            ->first();

        $federalConstituency = FederalConstituency::query()
            ->with(['state', 'senatorialDistrict'])
            ->whereNotNull('state_id')
            ->orderBy('id')
            ->first();

        if (!$senatorialDistrict || !$senatorialDistrict->state) {
            $this->command?->warn('No senatorial district with a valid state exists. Senatorial demo user was not created.');
        } else {
            $this->createDemoUser(
                email: 'senateadmin@example.com',
                username: 'demo_senatorial_admin',
                firstname: 'Senatorial',
                phone: '08000000001',
                accessLevel: 'senatorialadmin',
                stateId: $senatorialDistrict->state_id,
                regionId: $senatorialDistrict->state->region_id,
                senatorialDistrictId: $senatorialDistrict->id,
                federalConstituencyId: null,
                supportGroupId: $supportGroup->id
            );

            $this->command?->info("Created/updated senatorial demo user for {$senatorialDistrict->name}.");
        }

        if (!$federalConstituency || !$federalConstituency->state) {
            $this->command?->warn('No federal constituency with a valid state exists. Federal demo user was not created.');
        } else {
            $this->createDemoUser(
                email: 'fcadmin@example.com',
                username: 'demo_federal_admin',
                firstname: 'Federal',
                phone: '08000000002',
                accessLevel: 'federaladmin',
                stateId: $federalConstituency->state_id,
                regionId: $federalConstituency->state->region_id,
                senatorialDistrictId: $federalConstituency->senatorial_district_id,
                federalConstituencyId: $federalConstituency->id,
                supportGroupId: $supportGroup->id
            );

            $this->command?->info("Created/updated federal demo user for {$federalConstituency->name}.");
        }

        $this->command?->info('Demo password: '.self::PASSWORD);
    }

    private function createDemoUser(
        string $email,
        string $username,
        string $firstname,
        string $phone,
        string $accessLevel,
        ?int $stateId,
        ?int $regionId,
        ?int $senatorialDistrictId,
        ?int $federalConstituencyId,
        int $supportGroupId
    ): void {
        $countryId = Country::query()->where('name', 'Nigeria')->value('id')
            ?? Country::query()->value('id');
        $religionId = Religion::query()->value('id');
        $ageGradeId = AgeGrade::query()->value('id');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'uuid' => (string) Str::uuid(),
                'username' => $username,
                'password' => Hash::make(self::PASSWORD),
                'firstname' => $firstname,
                'lastname' => 'Demo Admin',
                'phone' => $phone,
                'validVoter' => 'no',
                'status' => 'active',
                'access_level' => $accessLevel,
                'email_verified_at' => now(),
                'country_id' => $countryId,
                'region_id' => $regionId,
                'state_id' => $stateId,
                'senatorial_district_id' => $senatorialDistrictId,
                'federal_constituency_id' => $federalConstituencyId,
                'lga_id' => null,
                'ward_id' => null,
                'polling_unit_id' => null,
                'religion_id' => $religionId,
                'age_grade_id' => $ageGradeId,
                'bank' => 'Demo Bank',
                'bank_account_number' => $accessLevel === 'senatorialadmin' ? '0000000001' : '0000000002',
                'requires_update' => false,
                'remember_token' => Str::random(10),
            ]
        );

        $user->supportGroups()->syncWithoutDetaching([$supportGroupId]);
    }
}
