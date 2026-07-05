<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sms_batches', 'sms_messages', 'sms_wallet_reservations', 'sms_allocations'] as $table) {
            if (!Schema::hasTable($table)) throw new \RuntimeException("Required SMS table [{$table}] is missing.");
        }

        $this->column('sms_batches', 'currency', fn (Blueprint $table) => $table->string('currency', 8)->default('NGN'));
        $this->column('sms_batches', 'reversed_amount', fn (Blueprint $table) => $table->decimal('reversed_amount', 18, 6)->default(0));
        $this->column('sms_batches', 'released_amount', fn (Blueprint $table) => $table->decimal('released_amount', 18, 6)->default(0));
        $this->column('sms_batches', 'settlement_status', fn (Blueprint $table) => $table->string('settlement_status', 40)->nullable());
        $this->column('sms_batches', 'messages_acknowledged_at', fn (Blueprint $table) => $table->timestamp('messages_acknowledged_at')->nullable());
        $this->column('sms_messages', 'refund_evidence', fn (Blueprint $table) => $table->string('refund_evidence', 80)->nullable());

        $this->index('sms_allocations', ['status', 'portal_synced_at'], 'sms_allocations_status_portal_synced_at_index');
        $this->index('sms_batches', ['status', 'last_synced_at'], 'sms_batches_status_last_synced_at_index');
        $this->index('sms_batches', ['created_by', 'created_at'], 'sms_batches_created_by_created_at_index');
        $this->index('sms_messages', ['sms_batch_id', 'delivery_status'], 'sms_messages_sms_batch_id_delivery_status_index');
        $this->index('sms_messages', ['sms_batch_id', 'billing_status'], 'sms_messages_sms_batch_id_billing_status_index');

        if (!$this->hasIndex('sms_wallet_reservations', ['local_batch_reference'], true)) {
            $hasDuplicates = DB::table('sms_wallet_reservations')
                ->select('local_batch_reference')
                ->groupBy('local_batch_reference')
                ->havingRaw('COUNT(*) > 1')
                ->exists();
            if ($hasDuplicates) {
                throw new \RuntimeException('Duplicate SMS wallet reservations must be reconciled before the unique constraint can be added.');
            }
            Schema::table('sms_wallet_reservations', fn (Blueprint $table) => $table->unique('local_batch_reference', 'sms_wallet_reservations_local_batch_reference_unique'));
        }
    }

    public function down(): void
    {
        // Forward-only: these fields and indexes may contain financial settlement evidence.
    }

    private function column(string $table, string $column, callable $definition): void
    {
        if (!Schema::hasColumn($table, $column)) {
            Schema::table($table, fn (Blueprint $blueprint) => $definition($blueprint));
        }
    }

    private function index(string $table, array $columns, string $name): void
    {
        if (!$this->hasIndex($table, $columns)) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        }
    }

    private function hasIndex(string $table, array $columns, bool $unique = false): bool
    {
        $expected = array_map('strtolower', $columns);
        foreach (Schema::getIndexes($table) as $index) {
            $actual = array_map('strtolower', (array) ($index['columns'] ?? []));
            if ($actual === $expected && (!$unique || (bool) ($index['unique'] ?? false))) return true;
        }
        return false;
    }
};
