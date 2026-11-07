<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('campaign_paystack_beneficiary_links')) {
            return;
        }

        Schema::create('campaign_paystack_beneficiary_links', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('beneficiary_id')->primary()
                ->constrained('campaign_finance_request_recipients', 'id', 'paystack_beneficiary_fk')->restrictOnDelete();
            $table->foreignId('operation_id')->nullable()->unique()
                ->constrained('campaign_paystack_operations', 'id', 'paystack_beneficiary_operation_fk')->restrictOnDelete();
            $table->string('recipient_code', 80);
            $table->char('connection', 64);
            $table->unsignedBigInteger('linked_by');
            $table->timestamp('linked_at');
        });
    }

    public function down(): void
    {
        // Retain approved beneficiary mappings and external transfer references.
    }
};
