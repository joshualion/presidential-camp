<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_finance_manual_expenses')) {
            return;
        }

        Schema::create('campaign_finance_manual_expenses', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('entry_id')->primary()->constrained('campaign_finance_entries')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('campaign_finance_accounts')->restrictOnDelete();
            $table->string('payee', 160);
            $table->date('paid_on');
            $table->string('reference', 120);
            $table->char('reference_key', 64);
            $table->string('evidence_reference', 300);
            $table->unique(['account_id', 'reference_key'], 'finance_manual_account_ref_unique');
        });
    }

    public function down(): void
    {
        // Preserve evidence linked to posted finance records.
    }
};
