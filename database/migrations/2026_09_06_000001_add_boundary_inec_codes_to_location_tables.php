<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('senatorial_districts') && ! Schema::hasColumn('senatorial_districts', 'inec_senatorial_district_code')) {
            Schema::table('senatorial_districts', function (Blueprint $table) {
                $table->string('inec_senatorial_district_code', 50)->nullable()->index()->after('name');
            });
        }

        if (Schema::hasTable('federal_constituencies') && ! Schema::hasColumn('federal_constituencies', 'inec_federal_constituency_code')) {
            Schema::table('federal_constituencies', function (Blueprint $table) {
                $table->string('inec_federal_constituency_code', 50)->nullable()->index()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('federal_constituencies') && Schema::hasColumn('federal_constituencies', 'inec_federal_constituency_code')) {
            Schema::table('federal_constituencies', function (Blueprint $table) {
                $table->dropIndex(['inec_federal_constituency_code']);
                $table->dropColumn('inec_federal_constituency_code');
            });
        }

        if (Schema::hasTable('senatorial_districts') && Schema::hasColumn('senatorial_districts', 'inec_senatorial_district_code')) {
            Schema::table('senatorial_districts', function (Blueprint $table) {
                $table->dropIndex(['inec_senatorial_district_code']);
                $table->dropColumn('inec_senatorial_district_code');
            });
        }
    }
};
