<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $groups = "'superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'";

        DB::statement("ALTER TABLE roles MODIFY group_name ENUM({$groups}) DEFAULT 'user'");
        DB::statement("ALTER TABLE permissions MODIFY group_name ENUM({$groups}) DEFAULT 'user'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $groups = "'superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'";

        DB::statement("ALTER TABLE roles MODIFY group_name ENUM({$groups}) DEFAULT 'user'");
        DB::statement("ALTER TABLE permissions MODIFY group_name ENUM({$groups}) DEFAULT 'user'");
    }
};
