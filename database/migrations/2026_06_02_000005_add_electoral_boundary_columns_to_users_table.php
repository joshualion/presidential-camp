<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('senatorial_district_id')
                ->nullable()
                ->after('state_id')
                ->constrained('senatorial_districts')
                ->nullOnDelete();

            $table->foreignId('federal_constituency_id')
                ->nullable()
                ->after('senatorial_district_id')
                ->constrained('federal_constituencies')
                ->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY access_level ENUM('superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user') DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY access_level ENUM('superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user') DEFAULT 'user'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('federal_constituency_id');
            $table->dropConstrainedForeignId('senatorial_district_id');
        });
    }
};
