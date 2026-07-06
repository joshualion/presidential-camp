<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->boolean('portal_sms_enabled')->default(false);
            $table->string('portal_sms_base_url')->nullable();
            $table->string('portal_sms_client_key')->nullable();
            $table->text('portal_sms_secret')->nullable();
            $table->string('portal_sms_default_route', 20)->default('regular');
            $table->boolean('portal_sms_system_route_enabled')->default(false);
            $table->boolean('portal_sms_sender_id_request_enabled')->default(false);
            $table->boolean('portal_sms_sending_enabled')->default(false);
            $table->boolean('portal_sms_topup_enabled')->default(false);
            $table->boolean('portal_sms_credit_request_enabled')->default(false);
            $table->string('portal_sms_last_connection_status')->nullable();
            $table->timestamp('portal_sms_last_synced_at')->nullable();
        });

        Schema::create('sms_wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('wallet_reference')->unique();
            $table->string('owner_key')->unique();
            $table->string('owner_type', 30);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('access_level')->nullable();
            $table->decimal('balance', 18, 6)->default(0);
            $table->decimal('reserved_balance', 18, 6)->default(0);
            $table->string('currency', 8)->default('NGN');
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_reference')->unique();
            $table->foreignId('sms_wallet_id')->constrained('sms_wallets')->restrictOnDelete();
            $table->string('type', 50);
            $table->string('direction', 10);
            $table->decimal('amount', 18, 6);
            $table->decimal('balance_before', 18, 6);
            $table->decimal('balance_after', 18, 6);
            $table->decimal('reserved_before', 18, 6)->default(0);
            $table->decimal('reserved_after', 18, 6)->default(0);
            $table->string('external_reference')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('related_type', 191)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('sms_wallet_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('reservation_reference')->unique();
            $table->foreignId('sms_wallet_id')->constrained('sms_wallets')->restrictOnDelete();
            $table->uuid('local_batch_reference')->unique();
            $table->decimal('amount', 18, 6);
            $table->decimal('debited_amount', 18, 6)->default(0);
            $table->decimal('released_amount', 18, 6)->default(0);
            $table->string('status', 30)->default('active');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_topups', function (Blueprint $table) {
            $table->id();
            $table->uuid('local_reference')->unique();
            $table->string('portal_reference')->nullable()->unique();
            $table->string('payment_reference')->nullable()->index();
            $table->foreignId('sms_wallet_id')->constrained('sms_wallets')->restrictOnDelete();
            $table->string('funding_type', 40);
            $table->decimal('amount', 18, 6);
            $table->string('currency', 8)->default('NGN');
            $table->string('status', 30)->default('pending');
            $table->text('payment_url')->nullable();
            $table->json('payment_instructions')->nullable();
            $table->string('idempotency_key')->unique();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('portal_reference')->unique();
            $table->string('allocation_type', 40);
            $table->foreignId('sms_wallet_id')->nullable()->constrained('sms_wallets')->restrictOnDelete();
            $table->string('core_user_reference')->nullable();
            $table->string('core_wallet_reference')->nullable();
            $table->decimal('amount', 18, 6);
            $table->string('currency', 8)->default('NGN');
            $table->string('status', 30);
            $table->json('portal_payload')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->timestamp('portal_synced_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'portal_synced_at']);
        });

        Schema::create('sms_credit_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('local_reference')->unique();
            $table->string('portal_reference')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->foreignId('sms_wallet_id')->constrained('sms_wallets')->restrictOnDelete();
            $table->decimal('amount', 18, 6);
            $table->string('currency', 8)->default('NGN');
            $table->text('reason');
            $table->string('urgency', 20)->default('normal');
            $table->string('status', 30)->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'last_synced_at']);
            $table->index(['created_by', 'created_at']);
        });

        Schema::create('sms_sender_ids', function (Blueprint $table) {
            $table->id();
            $table->string('portal_reference')->nullable()->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('sender_id', 20)->unique();
            $table->string('status', 30)->default('pending');
            $table->text('purpose')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('portal_payload')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('local_batch_reference')->unique();
            $table->string('portal_batch_reference')->nullable()->unique();
            $table->foreignId('sms_wallet_id')->constrained('sms_wallets')->restrictOnDelete();
            $table->uuid('selected_wallet_reference');
            $table->string('wallet_owner_type', 30);
            $table->foreignId('wallet_owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('currency', 8)->default('NGN');
            $table->string('route', 20);
            $table->string('sender_id', 20);
            $table->text('message_body');
            $table->string('source_context')->nullable();
            $table->json('target_scope_metadata')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('valid_recipient_count')->default(0);
            $table->unsignedInteger('invalid_recipient_count')->default(0);
            $table->unsignedInteger('duplicate_recipient_count')->default(0);
            $table->unsignedInteger('total_units')->default(0);
            $table->decimal('estimated_amount', 18, 6)->default(0);
            $table->decimal('reserved_amount', 18, 6)->default(0);
            $table->decimal('debited_amount', 18, 6)->default(0);
            $table->decimal('refunded_amount', 18, 6)->default(0);
            $table->decimal('reversed_amount', 18, 6)->default(0);
            $table->decimal('released_amount', 18, 6)->default(0);
            $table->json('delivery_summary')->nullable();
            $table->unsignedInteger('billing_review_count')->default(0);
            $table->string('settlement_status', 40)->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('portal_status', 30)->nullable();
            $table->string('idempotency_key')->unique();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('messages_acknowledged_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_batch_id')->constrained('sms_batches')->restrictOnDelete();
            $table->uuid('local_message_reference')->unique();
            $table->string('portal_message_reference')->nullable()->unique();
            $table->string('recipient_phone', 40);
            $table->string('recipient_name')->nullable();
            $table->string('local_reference')->nullable();
            $table->string('delivery_status', 30)->default('pending');
            $table->string('portal_status', 30)->nullable();
            $table->string('billing_status', 30)->nullable();
            $table->decimal('client_charge_amount', 18, 6)->nullable();
            $table->boolean('refund_eligible')->nullable();
            $table->boolean('refunded')->default(false);
            $table->text('refund_reason')->nullable();
            $table->string('refund_evidence', 80)->nullable();
            $table->boolean('billing_review_required')->default(false);
            $table->unsignedInteger('units')->default(1);
            $table->decimal('estimated_amount', 18, 6)->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->index(['sms_batch_id', 'delivery_status']);
            $table->index(['sms_batch_id', 'billing_status']);
        });

        Schema::create('sms_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_message_id')->constrained('sms_messages')->cascadeOnDelete();
            $table->string('portal_event_reference')->nullable()->unique();
            $table->string('event_type', 40);
            $table->string('delivery_status', 30)->nullable();
            $table->string('billing_status', 30)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 80)->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type', 191)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        if (Schema::hasTable('permissions')) {
            foreach (['sms.view', 'sms.compose', 'sms.send', 'sms.sender_id.request', 'sms.reports', 'sms.settings', 'sms.wallet.view', 'sms.wallet.topup', 'sms.wallet.transfer', 'sms.credit.request', 'sms.organization_wallet.view', 'sms.organization_wallet.use'] as $permission) {
                DB::table('permissions')->insertOrIgnore(['name' => $permission, 'guard_name' => 'web', 'group_name' => 'superadmin', 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // Legacy provider columns are intentionally left untouched. Runtime code no longer reads
        // them, and removing production credentials belongs in a separate, reversible cleanup.
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) DB::table('permissions')->whereIn('name', ['sms.view', 'sms.compose', 'sms.send', 'sms.sender_id.request', 'sms.reports', 'sms.settings', 'sms.wallet.view', 'sms.wallet.topup', 'sms.wallet.transfer', 'sms.credit.request', 'sms.organization_wallet.view', 'sms.organization_wallet.use'])->delete();

        Schema::dropIfExists('sms_audit_logs');
        Schema::dropIfExists('sms_status_events');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_batches');
        Schema::dropIfExists('sms_sender_ids');
        Schema::dropIfExists('sms_credit_requests');
        Schema::dropIfExists('sms_allocations');
        Schema::dropIfExists('sms_topups');
        Schema::dropIfExists('sms_wallet_reservations');
        Schema::dropIfExists('sms_wallet_transactions');
        Schema::dropIfExists('sms_wallets');

        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'portal_sms_enabled', 'portal_sms_base_url', 'portal_sms_client_key', 'portal_sms_secret',
                'portal_sms_default_route', 'portal_sms_system_route_enabled', 'portal_sms_sender_id_request_enabled',
                'portal_sms_sending_enabled', 'portal_sms_topup_enabled', 'portal_sms_credit_request_enabled',
                'portal_sms_last_connection_status', 'portal_sms_last_synced_at',
            ]);
        });
    }
};
