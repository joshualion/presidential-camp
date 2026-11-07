<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_journal')) {
            Schema::create('campaign_finance_journal', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('entry_id')->primary()->constrained('campaign_finance_entries')->restrictOnDelete();
                // One positive amount applies to both sides of the posting.
                $table->string('debit_code', 32);
                $table->string('credit_code', 32);
                $table->unsignedBigInteger('amount_minor');
            });
        }

        if (!Schema::hasTable('campaign_finance_ledger_checks')) {
            Schema::create('campaign_finance_ledger_checks', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('account_id')->primary()->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->unsignedBigInteger('historical_entries');
                $table->unsignedBigInteger('historical_balance_minor');
                $table->timestamp('checked_at');
            });
        }
    }

    public function down(): void
    {
        // Retain accounting records. Recovery uses the updater's verified backup.
    }
};
