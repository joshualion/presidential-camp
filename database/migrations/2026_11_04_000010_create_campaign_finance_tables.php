<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_accounts')) {
            Schema::create('campaign_finance_accounts', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('name', 120);
                $table->char('currency', 3)->default('NGN');
                $table->unsignedBigInteger('balance_minor')->default(0);
                $table->unsignedBigInteger('owner_id')->index();
                foreach (['region_id', 'state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id', 'polling_unit_id'] as $column) {
                    $table->unsignedBigInteger($column)->nullable()->index();
                }
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('campaign_finance_budgets')) {
            Schema::create('campaign_finance_budgets', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('account_id')->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->string('name', 120);
                $table->unsignedBigInteger('allocation_minor');
                $table->unsignedBigInteger('utilized_minor')->default(0);
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('campaign_finance_requests')) {
            Schema::create('campaign_finance_requests', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('account_id')->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->foreignId('budget_id')->nullable()->constrained('campaign_finance_budgets')->restrictOnDelete();
                $table->unsignedBigInteger('amount_minor');
                $table->string('description', 500);
                $table->string('status', 16)->default('pending')->index();
                $table->unsignedBigInteger('requested_by');
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('campaign_finance_entries')) {
            Schema::create('campaign_finance_entries', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('account_id')->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->foreignId('budget_id')->nullable()->constrained('campaign_finance_budgets')->restrictOnDelete();
                $table->foreignId('request_id')->nullable()->unique()->constrained('campaign_finance_requests')->restrictOnDelete();
                $table->uuid('idempotency_key')->unique();
                $table->string('kind', 16);
                $table->unsignedBigInteger('amount_minor');
                $table->unsignedBigInteger('balance_after_minor');
                $table->string('description', 500);
                $table->unsignedBigInteger('recorded_by');
                $table->timestamp('created_at');
            });
        }
        if (!Schema::hasTable('campaign_finance_audits')) {
            Schema::create('campaign_finance_audits', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('account_id')->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->unsignedBigInteger('actor_id');
                $table->string('action', 50);
                $table->json('details');
                $table->timestamp('created_at');
            });
        }
        if (!Schema::hasTable('campaign_finance_documents')) {
            Schema::create('campaign_finance_documents', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('account_id')->constrained('campaign_finance_accounts')->restrictOnDelete();
                $table->foreignId('request_id')->nullable()->constrained('campaign_finance_requests')->restrictOnDelete();
                $table->string('path')->unique();
                $table->string('filename', 180);
                $table->string('mime', 80);
                $table->char('sha256', 64);
                $table->unsignedBigInteger('uploaded_by');
                $table->timestamp('created_at');
            });
        }
    }

    public function down(): void
    {
        // Financial history is retained. Restore an operator-verified backup for rollback.
    }
};
