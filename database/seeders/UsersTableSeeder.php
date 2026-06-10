<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $access_levels = [
            'superadmin' => 'Presidential Candidate',
            'nationaladmin' => 'National ICT Director',
            'regionaladmin' => 'Regional ICT Director',
            'stateadmin' => 'State ICT Director',
            'senatorialadmin' => 'Senatorial ICT Director',
            'federaladmin' => 'Federal Constituency ICT Director',
            'lgaadmin' =>  'LGA ICT Director',
            'wardadmin' => 'Ward ICT Director',
            'puadmin' => 'PU ICT Director',
            'user' => 'Member',
        ];

        foreach ($access_levels as $access_level => $role_name) {
            $user =User::create([
                'uuid' => Str::uuid(),
                'username' => $access_level . '_user',
                'email' => $access_level . '@example.com',
                'password' => Hash::make('password'), // Using Laravel's password hashing
                'firstname' => ucfirst($access_level),
                'lastname' => 'User',
                'phone' => null,
                'vin' => null,
                'photo' => null,
                'address' => null,
                'occupation' => null,
                'qualification' => null,
                'validVoter' => 'no',
                'gender' => 'male',
                'status' => 'active',
                'access_level' => $access_level,
                'email_verified_at' => now(),
                'country_id' => 1,
                'region_id' => 1,
                'state_id' => 1,
                'lga_id' => null,
                'ward_id' => null,
                'polling_unit_id' => null,
                'religion_id' => rand(1, 5),
                'age_grade_id' => 1,
                'requires_update'=> true,
                'remember_token' => Str::random(10),
            ]);

            // Assign the role to the user
            $user->assignRole($role_name);
        }
    }
}
