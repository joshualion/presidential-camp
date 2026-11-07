<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_request_origins')) {
            Schema::create('campaign_finance_request_origins', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('request_id')->primary()->constrained('campaign_finance_requests')->restrictOnDelete();
                $table->string('requesting_level', 24);
                $table->unsignedBigInteger('requesting_location_id')->nullable();
                $table->string('beneficiary_level', 24)->nullable();
                $table->unsignedBigInteger('beneficiary_location_id')->nullable();
                $table->string('payment_plan', 24);
                $table->timestamp('created_at');
            });
        }
        if (!Schema::hasTable('campaign_finance_request_recipients')) {
            Schema::create('campaign_finance_request_recipients', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('request_id')->constrained('campaign_finance_requests')->restrictOnDelete();
                $table->unsignedSmallInteger('position');
                $table->string('recipient_name', 160);
                $table->unsignedBigInteger('amount_minor');
                $table->string('status', 24)->default('proposed');
                $table->timestamp('created_at');
                $table->unique(['request_id', 'position']);
            });
        }
    }

    public function down(): void
    {
        // Preserve submitted requests and beneficiary records for financial audits.
    }
};
