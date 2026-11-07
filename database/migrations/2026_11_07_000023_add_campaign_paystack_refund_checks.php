<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_paystack_refund_checks')) {
            return;
        }

        Schema::create('campaign_paystack_refund_checks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('operation_id')->constrained('campaign_paystack_operations', 'id', 'paystack_refund_operation_fk')->restrictOnDelete();
            $table->unsignedBigInteger('provider_refund_id');
            $table->unsignedBigInteger('amount_minor');
            $table->string('status', 24);
            $table->unsignedBigInteger('checked_by');
            $table->timestamp('checked_at');
            $table->unique(['operation_id', 'provider_refund_id'], 'paystack_refund_operation_ref_unique');
        });
    }

    public function down(): void
    {
        // Keep provider refund reconciliation records.
    }
};
