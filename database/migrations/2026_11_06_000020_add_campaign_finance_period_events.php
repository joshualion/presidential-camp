<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_finance_period_events')) {
            return;
        }

        Schema::create('campaign_finance_period_events', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('account_id')->constrained('campaign_finance_accounts')->restrictOnDelete();
            $table->date('closed_through')->nullable();
            $table->string('action', 16);
            $table->unsignedBigInteger('prior_close_id')->nullable();
            $table->foreignId('statement_id')->nullable()->constrained('campaign_finance_statement_checks')->restrictOnDelete();
            $table->unsignedBigInteger('actor_id');
            $table->string('reason', 500);
            $table->timestamp('created_at');
            $table->index(['account_id', 'id'], 'finance_period_account_id_idx');
        });
    }

    public function down(): void
    {
        // Retain the immutable closing and reopening trail.
    }
};
