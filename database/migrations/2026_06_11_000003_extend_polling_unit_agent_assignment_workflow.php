<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('polling_unit_agent_assignments')) {
            Schema::table('polling_unit_agent_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'workflow_stage')) {
                    $table->string('workflow_stage', 50)->nullable()->after('source')->index();
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'requested_by')) {
                    $table->foreignId('requested_by')->nullable()->after('assigned_by')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'nominated_by')) {
                    $table->foreignId('nominated_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'verification_note')) {
                    $table->text('verification_note')->nullable()->after('willingness_statement');
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'identity_verified_by')) {
                    $table->foreignId('identity_verified_by')->nullable()->after('verification_note')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'identity_verified_at')) {
                    $table->timestamp('identity_verified_at')->nullable()->after('identity_verified_by');
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'identity_rejected_by')) {
                    $table->foreignId('identity_rejected_by')->nullable()->after('identity_verified_at')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'identity_rejected_at')) {
                    $table->timestamp('identity_rejected_at')->nullable()->after('identity_rejected_by');
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'identity_rejection_reason')) {
                    $table->text('identity_rejection_reason')->nullable()->after('identity_rejected_at');
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'final_approved_by')) {
                    $table->foreignId('final_approved_by')->nullable()->after('identity_rejection_reason')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'final_approved_at')) {
                    $table->timestamp('final_approved_at')->nullable()->after('final_approved_by');
                }
                if (!Schema::hasColumn('polling_unit_agent_assignments', 'appointment_note')) {
                    $table->text('appointment_note')->nullable()->after('final_approved_at');
                }
            });
        }

        if (!Schema::hasTable('polling_unit_agent_assignment_approvals')) {
            Schema::create('polling_unit_agent_assignment_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assignment_id')->constrained('polling_unit_agent_assignments')->cascadeOnDelete();
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('approval_level', 50)->nullable();
                $table->string('scope_type', 50)->nullable();
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->string('status', 30);
                $table->text('comments')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();

                $table->index(['assignment_id', 'approval_level'], 'puaa_approvals_assignment_level_idx');
                $table->index(['approver_id', 'status'], 'puaa_approvals_approver_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_unit_agent_assignment_approvals');

        if (Schema::hasTable('polling_unit_agent_assignments')) {
            Schema::table('polling_unit_agent_assignments', function (Blueprint $table) {
                foreach ([
                    'workflow_stage',
                    'requested_by',
                    'nominated_by',
                    'verification_note',
                    'identity_verified_by',
                    'identity_verified_at',
                    'identity_rejected_by',
                    'identity_rejected_at',
                    'identity_rejection_reason',
                    'final_approved_by',
                    'final_approved_at',
                    'appointment_note',
                ] as $column) {
                    if (Schema::hasColumn('polling_unit_agent_assignments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
