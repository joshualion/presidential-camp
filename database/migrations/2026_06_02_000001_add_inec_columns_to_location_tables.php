<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('states', function (Blueprint $table) {
            if (! Schema::hasColumn('states', 'inec_state_code')) {
                $table->string('inec_state_code', 50)->nullable()->index()->after('name');
            }
        });

        Schema::table('local_government_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('local_government_areas', 'inec_lga_code')) {
                $table->string('inec_lga_code', 50)->nullable()->index()->after('name');
            }
        });

        Schema::table('wards', function (Blueprint $table) {
            if (! Schema::hasColumn('wards', 'inec_ward_code')) {
                $table->string('inec_ward_code', 50)->nullable()->index()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wards', function (Blueprint $table) {
            if (Schema::hasColumn('wards', 'inec_ward_code')) {
                $table->dropIndex(['inec_ward_code']);
                $table->dropColumn('inec_ward_code');
            }
        });

        Schema::table('local_government_areas', function (Blueprint $table) {
            if (Schema::hasColumn('local_government_areas', 'inec_lga_code')) {
                $table->dropIndex(['inec_lga_code']);
                $table->dropColumn('inec_lga_code');
            }
        });

        Schema::table('states', function (Blueprint $table) {
            if (Schema::hasColumn('states', 'inec_state_code')) {
                $table->dropIndex(['inec_state_code']);
                $table->dropColumn('inec_state_code');
            }
        });
    }
};
