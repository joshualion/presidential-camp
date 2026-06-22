<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('location_provisioning_runs')) {
            return;
        }

        Schema::table('location_provisioning_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('location_provisioning_runs', 'current_stage')) {
                $table->string('current_stage')->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('location_provisioning_runs', 'processed_rows')) {
                $table->unsignedInteger('processed_rows')->default(0)->after('current_stage');
            }
            if (!Schema::hasColumn('location_provisioning_runs', 'total_rows')) {
                $table->unsignedInteger('total_rows')->nullable()->after('processed_rows');
            }
            if (!Schema::hasColumn('location_provisioning_runs', 'created_count')) {
                $table->unsignedInteger('created_count')->default(0)->after('total_rows');
            }
            if (!Schema::hasColumn('location_provisioning_runs', 'updated_count')) {
                $table->unsignedInteger('updated_count')->default(0)->after('created_count');
            }
            if (!Schema::hasColumn('location_provisioning_runs', 'skipped_count')) {
                $table->unsignedInteger('skipped_count')->default(0)->after('updated_count');
            }
            if (!Schema::hasColumn('location_provisioning_runs', 'metadata')) {
                $table->json('metadata')->nullable()->after('warnings');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('location_provisioning_runs')) {
            return;
        }

        Schema::table('location_provisioning_runs', function (Blueprint $table) {
            foreach (['metadata', 'skipped_count', 'updated_count', 'created_count', 'total_rows', 'processed_rows', 'current_stage'] as $column) {
                if (Schema::hasColumn('location_provisioning_runs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
