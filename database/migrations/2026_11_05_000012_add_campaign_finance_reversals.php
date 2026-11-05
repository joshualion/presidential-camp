<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_reversals')) {
            Schema::create('campaign_finance_reversals', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('entry_id')->primary()->constrained('campaign_finance_entries')->restrictOnDelete();
                $table->foreignId('reversal_id')->unique()->constrained('campaign_finance_entries')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Retain the link to the original accounting entry.
    }
};
