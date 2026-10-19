<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('system_settings', 'direct_sms_enabled')) {
            Schema::table('system_settings', function (Blueprint $t) {
                $t->boolean('direct_sms_enabled')->default(false);
            });
        }
        if (! Schema::hasColumn('system_settings', 'direct_sms_levels')) {
            Schema::table('system_settings', fn (Blueprint $t) => $t->json('direct_sms_levels')->nullable());
        }
        if (! Schema::hasTable('sms_provider_grants')) {
            Schema::create('sms_provider_grants', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
                $t->boolean('enabled')->nullable();
                $t->string('audit_scope', 30)->nullable();
                $t->unsignedBigInteger('audit_scope_id')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('sms_provider_accounts')) {
            Schema::create('sms_provider_accounts', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $t->string('provider', 30);
                $t->string('base_url');
                $t->text('api_key');
                $t->string('sender_id', 11);
                $t->boolean('is_active')->default(true);
                $t->string('connection_status', 30)->nullable();
                $t->timestamps();
                $t->index(['user_id', 'is_active']);
            });
        }
        if (! Schema::hasTable('sms_direct_batches')) {
            Schema::create('sms_direct_batches', function (Blueprint $t) {
                $t->id();
                $t->uuid('uuid')->unique();
                $t->foreignId('account_id')->constrained('sms_provider_accounts')->restrictOnDelete();
                $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $t->string('provider', 30);
                $t->string('sender_id', 11);
                $t->text('message');
                $t->string('source', 30);
                $t->string('status', 30)->default('queued');
                $t->unsignedInteger('recipient_count');
                $t->timestamp('scheduled_at')->nullable();
                $t->timestamp('confirmed_at');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('sms_direct_messages')) {
            Schema::create('sms_direct_messages', function (Blueprint $t) {
                $t->id();
                $t->foreignId('batch_id')->constrained('sms_direct_batches')->restrictOnDelete();
                $t->string('phone', 25);
                $t->string('name')->nullable();
                $t->unsignedBigInteger('recipient_user_id')->nullable();
                foreach (['region_id', 'state_id', 'senatorial_district_id', 'federal_constituency_id', 'lga_id', 'ward_id', 'polling_unit_id'] as $c) {
                    $t->unsignedBigInteger($c)->nullable()->index();
                }$t->string('status', 30)->default('queued');
                $t->string('provider_reference', 191)->nullable()->index();
                $t->string('diagnostic_code', 60)->nullable();
                $t->timestamp('submitted_at')->nullable();
                $t->timestamp('last_synced_at')->nullable();
                $t->timestamps();
                $t->unique(['batch_id', 'phone']);
                $t->index(['status', 'id']);
            });
        }
        if (! Schema::hasTable('sms_direct_suppressions')) {
            Schema::create('sms_direct_suppressions', function (Blueprint $t) {
                $t->id();
                $t->string('owner_key', 40);
                $t->string('phone', 25);
                $t->timestamps();
                $t->unique(['owner_key', 'phone']);
            });
        }
        if (! Schema::hasTable('sms_direct_events')) {
            Schema::create('sms_direct_events', function (Blueprint $t) {
                $t->id();
                $t->foreignId('batch_id')->nullable()->constrained('sms_direct_batches')->restrictOnDelete();
                $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('event', 60);
                $t->json('details')->nullable();
                $t->timestamp('created_at');
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Restore a reviewed backup; SMS credentials and audit history must be preserved.');
    }
};
