<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polling_units', function (Blueprint $table) {
            if (! Schema::hasColumn('polling_units', 'inec_pu_code')) {
                $table->string('inec_pu_code', 50)->nullable()->index()->after('ward_id');
            }

            if (! Schema::hasColumn('polling_units', 'inec_full_code')) {
                $table->string('inec_full_code', 100)->nullable()->unique()->after('inec_pu_code');
            }

            if (! Schema::hasColumn('polling_units', 'senatorial_district_id')) {
                $table->foreignId('senatorial_district_id')
                    ->nullable()
                    ->after('inec_full_code')
                    ->constrained('senatorial_districts')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('polling_units', 'federal_constituency_id')) {
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
        Schema::table('polling_units', function (Blueprint $table) {
            if (Schema::hasColumn('polling_units', 'federal_constituency_id')) {
                $table->dropConstrainedForeignId('federal_constituency_id');
            }

            if (Schema::hasColumn('polling_units', 'senatorial_district_id')) {
                $table->dropConstrainedForeignId('senatorial_district_id');
            }

            if (Schema::hasColumn('polling_units', 'inec_full_code')) {
                $table->dropUnique(['inec_full_code']);
                $table->dropColumn('inec_full_code');
            }

            if (Schema::hasColumn('polling_units', 'inec_pu_code')) {
                $table->dropIndex(['inec_pu_code']);
                $table->dropColumn('inec_pu_code');
            }
        });
    }
};
