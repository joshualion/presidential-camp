<?php

namespace Database\Seeders;

use App\Services\CampaignCapabilityService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CampaignCapabilitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (app(CampaignCapabilityService::class)->catalog() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['group_name' => 'user']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
