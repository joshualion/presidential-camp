<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('enterprise_ai_settings')) {
            Schema::create('enterprise_ai_settings', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->unsignedInteger('id')->primary();
                $table->boolean('enabled')->default(false);
                $table->longText('api_key')->nullable();
                $table->string('model', 100)->default('gpt-4.1-mini');
                $table->unsignedInteger('daily_requests')->default(5);
                $table->unsignedInteger('daily_tokens')->default(20000);
                $table->unsignedInteger('installation_daily_requests')->default(25);
                $table->unsignedInteger('installation_daily_tokens')->default(100000);
                $table->unsignedInteger('max_output_tokens')->default(1000);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('enterprise_ai_usage')) {
            Schema::create('enterprise_ai_usage', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->date('usage_date');
                $table->unsignedInteger('requests')->default(0);
                $table->unsignedInteger('reserved_tokens')->default(0);
                $table->timestamps();
                $table->unique(['user_id', 'usage_date']);
            });
        }
        if (!Schema::hasTable('enterprise_ai_audits')) {
            Schema::create('enterprise_ai_audits', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('model', 100);
                $table->string('status', 32);
                $table->string('input_hash', 64);
                $table->unsignedInteger('reserved_tokens');
                $table->unsignedInteger('actual_tokens')->nullable();
                $table->string('output_hash', 64)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Retain settings, cost reservations and audit history on code rollback.
    }
};

