<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        DB::table('roles')->insert([

            ['id' => 1, 'name' => 'Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Vice Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'National Coordinator', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'name' => 'National Secretary', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'name' => 'National Financial Secretary', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 6, 'name' => 'National Treasurer', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 7, 'name' => 'National Women Leader', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 8, 'name' => 'National Youth Leader', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9, 'name' => 'National ICT Director', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => $now, 'updated_at' => $now],

            ['id' => 10, 'name' => 'Regional Coordinator', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 11, 'name' => 'Regional Secretary', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 12, 'name' => 'Regional Financial Secretary', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 13, 'name' => 'Regional Treasurer', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 14, 'name' => 'Regional Women Leader', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 15, 'name' => 'Regional Youth Leader', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 16, 'name' => 'Regional ICT Director', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => $now, 'updated_at' => $now],

            ['id' => 17, 'name' => 'State Coordinator', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 18, 'name' => 'State Secretary', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 19, 'name' => 'State Financial Secretary', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 20, 'name' => 'State Women Leader', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 21, 'name' => 'State Youth Leader', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 22, 'name' => 'State ICT Director', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => $now, 'updated_at' => $now],


            ['id' => 23, 'name' => 'Senatorial District Coordinator', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 24, 'name' => 'Senatorial District Secretary', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 25, 'name' => 'Senatorial District Financial Secretary', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 26, 'name' => 'Senatorial District Treasurer', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 27, 'name' => 'Senatorial District Women Leader', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 28, 'name' => 'Senatorial District Youth Leader', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 29, 'name' => 'Senatorial ICT Director', 'guard_name' => 'web', 'group_name' => 'senatorialadmin', 'created_at' => $now, 'updated_at' => $now],


            ['id' => 30, 'name' => 'Federal Constituency Coordinator', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 31, 'name' => 'Federal Constituency Secretary', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 32, 'name' => 'Federal Constituency Financial Secretary', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 33, 'name' => 'Federal Constituency Treasurer', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 34, 'name' => 'Federal Constituency Women Leader', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 35, 'name' => 'Federal Constituency Youth Leader', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 36, 'name' => 'Federal Constituency ICT Director', 'guard_name' => 'web', 'group_name' => 'federaladmin', 'created_at' => $now, 'updated_at' => $now],

            ['id' => 37, 'name' => 'LGA Coordinator', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],

            ['id' => 38, 'name' => 'LGA Secretary', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 39, 'name' => 'LGA Financial Secretary', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 40, 'name' => 'LGA Treasurer', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 41, 'name' => 'LGA Women Leader', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 42, 'name' => 'LGA Youth Leader', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 43, 'name' => 'LGA ICT Director', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 44, 'name' => 'Ward Coordinator', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],

            ['id' => 47, 'name' => 'Ward Treasurer', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 48, 'name' => 'Ward Women Leader', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 49, 'name' => 'Ward Youth Leader', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 50, 'name' => 'Ward ICT Director', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 51, 'name' => 'PU Agent', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 52, 'name' => 'PU Voter Mobilizer', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 53, 'name' => 'PU ICT Director', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => $now, 'updated_at' => $now],

            ['id' => 54, 'name' => 'Member', 'guard_name' => 'web', 'group_name' => 'user', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
