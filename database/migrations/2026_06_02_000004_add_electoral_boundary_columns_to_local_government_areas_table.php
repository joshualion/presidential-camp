<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('local_government_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('local_government_areas', 'senatorial_district_id')) {
                $table->foreignId('senatorial_district_id')
                    ->nullable()
                    ->after('state_id')
                    ->constrained('senatorial_districts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('local_government_areas', 'federal_constituency_id')) {
                $table->foreignId('federal_constituency_id')
                    ->nullable()
                    ->after('senatorial_district_id')
                    ->constrained('federal_constituencies')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('local_government_areas', function (Blueprint $table) {
            if (Schema::hasColumn('local_government_areas', 'federal_constituency_id')) {
                $table->dropConstrainedForeignId('federal_constituency_id');
            }

            if (Schema::hasColumn('local_government_areas', 'senatorial_district_id')) {
                $table->dropConstrainedForeignId('senatorial_district_id');
            }
        });
    }
};
