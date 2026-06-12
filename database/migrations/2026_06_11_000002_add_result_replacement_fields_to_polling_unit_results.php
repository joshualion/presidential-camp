<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            if (!Schema::hasColumn('polling_unit_results', 'result_status')) {
                $table->string('result_status', 30)->default('submitted')->after('dispute_reason');
            }

            if (!Schema::hasColumn('polling_unit_results', 'voided_by')) {
                $table->foreignId('voided_by')->nullable()->after('result_status')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('polling_unit_results', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('voided_by');
            }

            if (!Schema::hasColumn('polling_unit_results', 'void_reason')) {
                $table->text('void_reason')->nullable()->after('voided_at');
            }

            if (!Schema::hasColumn('polling_unit_results', 'replaced_by_result_id')) {
                $table->foreignId('replaced_by_result_id')->nullable()->after('void_reason')->constrained('polling_unit_results')->nullOnDelete();
            }

            $table->index(['election_id', 'polling_unit_id', 'result_status'], 'pur_active_result_guard_idx');
        });
    }

    public function down(): void
    {
        Schema::table('polling_unit_results', function (Blueprint $table) {
            $table->dropIndex('pur_active_result_guard_idx');

            foreach (['replaced_by_result_id', 'void_reason', 'voided_at', 'voided_by', 'result_status'] as $column) {
                if (Schema::hasColumn('polling_unit_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
