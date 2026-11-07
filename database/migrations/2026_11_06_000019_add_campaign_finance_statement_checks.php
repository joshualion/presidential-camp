<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_statement_checks')) {
            Schema::create('campaign_finance_statement_checks', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('account_id')->index();
                $table->date('statement_date');
                $table->string('evidence_reference', 300);
                $table->unsignedBigInteger('ledger_entry_id')->nullable();
                $table->bigInteger('ledger_balance_minor');
                $table->bigInteger('statement_balance_minor');
                $table->bigInteger('variance_minor');
                $table->unsignedBigInteger('prepared_by');
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->string('review_outcome', 24)->default('pending');
                $table->string('review_notes', 500)->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->unique(['account_id', 'statement_date'], 'finance_statement_account_date_unique');
            });
        }
    }

    public function down(): void
    {
        // Preserve accounting evidence and independent review decisions.
    }
};
