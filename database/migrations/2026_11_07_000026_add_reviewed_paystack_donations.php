<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_paystack_donation_reviews')) {
            return;
        }

        Schema::create('campaign_paystack_donation_reviews', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('match_id')->primary()
                ->constrained('campaign_paystack_settlement_matches')->restrictOnDelete();
            $table->foreignId('check_id')->unique()
                ->constrained('campaign_finance_statement_checks')->restrictOnDelete();
            $table->string('provider_transaction_id', 20)->unique();
            $table->string('reference', 100)->unique();
            $table->char('connection', 64);
            $table->char('donor_digest', 64)->index();
            $table->text('evidence');
            $table->unsignedBigInteger('reviewed_total_minor');
            $table->unsignedBigInteger('prepared_by');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->foreignId('income_entry_id')->nullable()->unique()
                ->constrained('campaign_finance_entries')->restrictOnDelete();
            $table->foreignId('fee_entry_id')->nullable()->unique()
                ->constrained('campaign_finance_entries')->restrictOnDelete();
            $table->timestamp('prepared_at');
            $table->timestamp('posted_at')->nullable();
        });
    }

    public function down(): void
    {
        // Keep evidence and immutable references to posted campaign funds.
    }
};
