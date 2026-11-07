<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_statement_imports')) {
            Schema::create('campaign_finance_statement_imports', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('check_id')->primary()->constrained('campaign_finance_statement_checks');
                $table->date('starts_on');
                $table->bigInteger('opening_minor');
                $table->char('sha256', 64);
                $table->json('lines');
            });
        }

        if (!Schema::hasTable('campaign_finance_statement_resolutions')) {
            Schema::create('campaign_finance_statement_resolutions', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('check_id')->primary()->constrained('campaign_finance_statement_checks');
                $table->foreignId('matched_check_id')->constrained('campaign_finance_statement_checks');
                $table->unsignedBigInteger('actor_id');
                $table->string('reason', 500);
                $table->timestamp('created_at');
            });
        }

        if (!Schema::hasTable('campaign_paystack_settlement_matches')) {
            Schema::create('campaign_paystack_settlement_matches', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('check_id')->constrained('campaign_finance_statement_imports', 'check_id');
                $table->unsignedBigInteger('provider_integration');
                $table->unsignedBigInteger('provider_settlement');
                $table->date('settled_on');
                $table->unsignedInteger('line_number');
                $table->unsignedBigInteger('gross_minor');
                $table->unsignedBigInteger('fee_minor');
                $table->unsignedBigInteger('net_minor');
                $table->unsignedBigInteger('actor_id');
                $table->timestamp('created_at');
                $table->unique(['provider_integration', 'provider_settlement'], 'finance_paystack_settlement_once');
                $table->unique(['check_id', 'line_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_paystack_settlement_matches');
        Schema::dropIfExists('campaign_finance_statement_resolutions');
        Schema::dropIfExists('campaign_finance_statement_imports');
    }
};
