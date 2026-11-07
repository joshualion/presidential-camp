<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_policies')) {
            Schema::create('campaign_finance_policies', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('actor_id');
                $table->unsignedBigInteger('previous_id')->unique();
                $table->json('menus');
                $table->json('approval_rules');
                $table->timestamp('created_at');
            });
        }

        if (!Schema::hasTable('campaign_finance_appointments')) {
            Schema::create('campaign_finance_appointments', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('office', 32);
                $table->string('boundary_column', 32);
                $table->unsignedBigInteger('boundary_id');
                $table->timestamp('starts_at');
                $table->timestamp('expires_at')->nullable();
                $table->unsignedBigInteger('appointed_by');
                $table->unsignedBigInteger('revoked_by')->nullable();
                $table->timestamp('revoked_at')->nullable();
            });
        }

        if (!Schema::hasTable('campaign_finance_workflows')) {
            Schema::create('campaign_finance_workflows', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('request_id')->primary()->constrained('campaign_finance_requests')->restrictOnDelete();
                $table->foreignId('policy_id')->constrained('campaign_finance_policies')->restrictOnDelete();
                $table->json('stages');
                $table->unsignedTinyInteger('next_stage')->default(0);
            });
        }

        if (!Schema::hasTable('campaign_finance_decisions')) {
            Schema::create('campaign_finance_decisions', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('request_id')->constrained('campaign_finance_requests')->restrictOnDelete();
                $table->unsignedTinyInteger('stage');
                $table->unsignedBigInteger('actor_id');
                $table->string('decision', 10);
                $table->timestamp('created_at');
                $table->unique(['request_id', 'stage']);
                $table->unique(['request_id', 'actor_id']);
            });
        }
    }

    public function down(): void
    {
        // Retain workflow and approval history.
    }
};
