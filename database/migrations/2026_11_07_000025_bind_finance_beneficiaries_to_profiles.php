<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'finance_bank_revision')) {
            Schema::table('users', fn (Blueprint $table) => $table->uuid('finance_bank_revision')->nullable());
        }

        if (!Schema::hasTable('campaign_finance_profile_beneficiaries')) {
            Schema::create('campaign_finance_profile_beneficiaries', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('beneficiary_id')->primary()
                    ->constrained('campaign_finance_request_recipients', 'id', 'finance_profile_beneficiary_fk');
                $table->unsignedBigInteger('user_id');
                $table->char('bank_fingerprint', 64);
                $table->timestamp('created_at');
            });
        }
    }

    public function down(): void
    {
        // Retain the recipient identity and approved bank snapshot with the finance records.
    }
};
