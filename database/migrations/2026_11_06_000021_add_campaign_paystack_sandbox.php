<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_paystack_settings')) {
            Schema::create('campaign_paystack_settings', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->unsignedTinyInteger('id')->primary();
                $table->string('mode', 4)->default('test');
                foreach (['test_public', 'test_secret', 'live_public', 'live_secret', 'test_webhook', 'live_webhook'] as $field) {
                    $table->text($field)->nullable();
                }
                $table->boolean('collections')->default(false);
                $table->boolean('transfers')->default(false);
                $table->unsignedBigInteger('updated_by');
                $table->timestamp('updated_at');
            });
        }
        if (!Schema::hasTable('campaign_paystack_recipients')) {
            Schema::create('campaign_paystack_recipients', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->foreignId('account_id')->primary()->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->string('recipient_code', 80);
                $table->char('connection', 64);
                $table->unsignedBigInteger('updated_by');
                $table->timestamp('updated_at');
            });
        }
        if (!Schema::hasTable('campaign_paystack_operations')) {
            Schema::create('campaign_paystack_operations', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('account_id')->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->foreignId('destination_id')->nullable()->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->foreignId('request_id')->nullable()->unique()->constrained('campaign_finance_requests')->restrictOnDelete();
                $table->string('recipient_code', 80)->nullable();
                $table->char('connection', 64);
                $table->uuid('reference')->unique();
                $table->string('kind', 12);
                $table->string('mode', 4);
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->unsignedBigInteger('amount_minor');
                $table->string('status', 24);
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('campaign_paystack_setting_events')) {
            Schema::create('campaign_paystack_setting_events', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('actor_id');
                $table->string('mode', 4);
                $table->boolean('collections');
                $table->boolean('transfers');
                $table->json('rotated_fields');
                $table->timestamp('created_at');
            });
        }
        if (!Schema::hasTable('campaign_paystack_webhook_events')) {
            Schema::create('campaign_paystack_webhook_events', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('mode', 4);
                $table->char('digest', 64);
                $table->foreignId('operation_id')->constrained('campaign_paystack_operations')->restrictOnDelete();
                $table->string('event', 32);
                $table->timestamp('created_at');
                $table->unique(['mode', 'digest']);
            });
        }
    }

    public function down(): void
    {
        // Preserve the record of external gateway requests for reconciliation.
    }
};
