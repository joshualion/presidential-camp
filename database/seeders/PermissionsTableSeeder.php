<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $permissions = [
            // National Admin Permissions
            ['name' => 'national_Create', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'national_Read', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'national_Update', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'national_Delete', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],

            // Regional Admin Permissions
            ['name' => 'regional_Create', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'regional_Read', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'regional_Update', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'regional_Delete', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],

            // State Admin Permissions
            ['name' => 'state_Create', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'state_Read', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'state_Update', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'state_Delete', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],

            // Senatorial District Admin Permissions
            ['name' => 'senatorial_Create', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'senatorial_Read', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'senatorial_Update', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'senatorial_Delete', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],

            // Federal Constituency Admin Permissions
            ['name' => 'federal_Create', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'federal_Read', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'federal_Update', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'federal_Delete', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],

            // LGA Admin Permissions
            ['name' => 'lga_Create', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'lga_Read', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'lga_Update', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'lga_Delete', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],

            // Ward Admin Permissions
            ['name' => 'ward_Create', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ward_Read', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ward_Update', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ward_Delete', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],

            // Polling Unit Admin Permissions
            ['name' => 'pu_Create', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'pu_Read', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'pu_Update', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'pu_Delete', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => $now, 'updated_at' => $now],

            // User Permissions
            ['name' => 'user_CRUD', 'guard_name' => 'web', 'group_name' => 'user', 'created_at' => $now, 'updated_at' => $now],

            // Superadmin Permissions
            ['name' => 'super_Create', 'guard_name' => 'web', 'group_name' => 'superadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'super_Read', 'guard_name' => 'web', 'group_name' => 'superadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'super_Update', 'guard_name' => 'web', 'group_name' => 'superadmin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'super_Delete', 'guard_name' => 'web', 'group_name' => 'superadmin', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('permissions')->insert($permissions);
    }
}
