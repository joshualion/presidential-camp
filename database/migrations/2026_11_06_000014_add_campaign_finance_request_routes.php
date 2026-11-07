<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_finance_request_routes')) {
            return;
        }

        Schema::create('campaign_finance_request_routes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('request_id')->primary()->constrained('campaign_finance_requests')->restrictOnDelete();
            $table->string('purpose', 16);
            $table->foreignId('destination_id')->nullable()->constrained('campaign_finance_accounts')->restrictOnDelete();
            $table->string('recipient', 160)->nullable();
            $table->uuid('settlement_key')->nullable()->unique();
            $table->unsignedBigInteger('settled_by')->nullable();
            $table->unsignedBigInteger('spent_minor')->nullable();
            $table->string('settlement_evidence', 300)->nullable();
            $table->timestamp('settled_at')->nullable();
        });
    }

    public function down(): void
    {
        // Keep the approved routing and settlement evidence.
    }
};
