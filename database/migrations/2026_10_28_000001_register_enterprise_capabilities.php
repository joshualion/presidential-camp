<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        // An updater worker may still hold the preceding release's config cache.
        $enterprise = require config_path('enterprise.php');
        foreach ($enterprise['permissions'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['group_name' => 'enterprise']);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep customer role grants; no automatic permission or customer data deletion.
    }
};
