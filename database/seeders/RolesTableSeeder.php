<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RolesTableSeeder extends Seeder
{
    public function run()
    {

        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'name' => 'Vice Presidential Candidate', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 3, 'name' => 'National Campaign Coordinator', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 4, 'name' => 'National ICT/Media Director', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 5, 'name' => 'National Secretary', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 6, 'name' => 'National Financial Secretary', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 7, 'name' => 'National Treasurer', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 8, 'name' => 'National Women Leader', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 9, 'name' => 'National Youth Leader', 'guard_name' => 'web', 'group_name' => 'nationaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 10, 'name' => 'Regional Coordinator', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 11, 'name' => 'Regional Secretary', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 12, 'name' => 'Regional Financial Secretary', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 13, 'name' => 'Regional Treasurer', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 14, 'name' => 'Regional Liaison Officer', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 15, 'name' => 'Regional ICT/Media Director', 'guard_name' => 'web', 'group_name' => 'regionaladmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 16, 'name' => 'State Coordinator', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 17, 'name' => 'State Secretary', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 18, 'name' => 'State Financial Secretary', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 19, 'name' => 'State Treasurer', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 20, 'name' => 'State Women Leader', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 21, 'name' => 'State Youth Leader', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 22, 'name' => 'State ICT/Media Director', 'guard_name' => 'web', 'group_name' => 'stateadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 23, 'name' => 'LGA Coordinator', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 24, 'name' => 'LGA Secretary', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 25, 'name' => 'LGA Financial Secretary', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 26, 'name' => 'LGA Treasurer', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 27, 'name' => 'LGA Women Leader', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 28, 'name' => 'LGA Youth Leader', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 29, 'name' => 'LGA ICT/Media Director', 'guard_name' => 'web', 'group_name' => 'lgaadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 30, 'name' => 'Ward Coordinator', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 31, 'name' => 'Ward Secretary', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 32, 'name' => 'Ward Financial Secretary', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 33, 'name' => 'Ward Treasurer', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 34, 'name' => 'Ward Women Leader', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 35, 'name' => 'Ward Youth Leader', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 36, 'name' => 'Ward ICT/Media Director', 'guard_name' => 'web', 'group_name' => 'wardadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 37, 'name' => 'PU Team Leader', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 38, 'name' => 'PU Voter Mobilizer', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 39, 'name' => 'PU ICT/Media Director', 'guard_name' => 'web', 'group_name' => 'puadmin', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 40, 'name' => 'Member', 'guard_name' => 'web', 'group_name' => 'user', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);
    }
}
