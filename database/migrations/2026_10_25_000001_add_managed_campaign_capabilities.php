<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'roles'] as $table) {
            if (! Schema::hasColumn($table, 'capabilities_managed')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->boolean('capabilities_managed')->default(false));
            }
        }
        if (! Schema::hasTable('campaign_permission_restrictions')) {
            Schema::create('campaign_permission_restrictions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('permission_name', 125);
                $table->unique(['user_id', 'permission_name'], 'campaign_permission_restriction_unique');
            });
        }
        if (! Schema::hasTable('campaign_default_roles')) {
            Schema::create('campaign_default_roles', function (Blueprint $table) {
                $table->string('access_level', 40)->primary();
                $table->unsignedBigInteger('role_id');
            });
        }
        app(\Database\Seeders\CampaignCapabilitySeeder::class)->run();
    }

    public function down(): void
    {
        // Authorization data is retained deliberately. Reversal requires an operator-reviewed data plan.
    }
};
