<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        Schema::table('system_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('system_settings', 'installation_mode')) {
                $table->string('installation_mode', 40)->default('community')->after('package');
            }

            if (! Schema::hasColumn('system_settings', 'campaign_scope_type')) {
                $table->string('campaign_scope_type', 80)->nullable()->after('installation_mode');
            }

            if (! Schema::hasColumn('system_settings', 'campaign_scope_name')) {
                $table->string('campaign_scope_name')->nullable()->after('campaign_scope_type');
            }

            if (! Schema::hasColumn('system_settings', 'campaign_state_id')) {
                $table->unsignedBigInteger('campaign_state_id')->nullable()->after('campaign_scope_name');
            }

            if (! Schema::hasColumn('system_settings', 'campaign_senatorial_district_id')) {
                $table->unsignedBigInteger('campaign_senatorial_district_id')->nullable()->after('campaign_state_id');
            }

            if (! Schema::hasColumn('system_settings', 'campaign_federal_constituency_id')) {
                $table->unsignedBigInteger('campaign_federal_constituency_id')->nullable()->after('campaign_senatorial_district_id');
            }

            if (! Schema::hasColumn('system_settings', 'campaign_lga_id')) {
                $table->unsignedBigInteger('campaign_lga_id')->nullable()->after('campaign_federal_constituency_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        Schema::table('system_settings', function (Blueprint $table) {
            foreach ([
                'campaign_lga_id',
                'campaign_federal_constituency_id',
                'campaign_senatorial_district_id',
                'campaign_state_id',
                'campaign_scope_name',
                'campaign_scope_type',
                'installation_mode',
            ] as $column) {
                if (Schema::hasColumn('system_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
