<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('polling_unit_agent_assignments')) {
            return;
        }

        Schema::table('polling_unit_agent_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('polling_unit_agent_assignments', 'assignment_type')) {
                $table->string('assignment_type', 50)->default('registered_polling_unit')->after('source')->index();
            }
            if (!Schema::hasColumn('polling_unit_agent_assignments', 'registered_polling_unit_id')) {
                $table->unsignedBigInteger('registered_polling_unit_id')->nullable()->after('polling_unit_id');
                $table->foreign('registered_polling_unit_id', 'puaa_registered_pu_fk')->references('id')->on('polling_units')->nullOnDelete();
            }
            if (!Schema::hasColumn('polling_unit_agent_assignments', 'override_reason')) {
                $table->text('override_reason')->nullable()->after('appointment_note');
            }
            if (!Schema::hasColumn('polling_unit_agent_assignments', 'override_authorized_by')) {
                $table->unsignedBigInteger('override_authorized_by')->nullable()->after('override_reason');
                $table->foreign('override_authorized_by', 'puaa_override_by_fk')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('polling_unit_agent_assignments', 'override_authorized_at')) {
                $table->timestamp('override_authorized_at')->nullable()->after('override_authorized_by');
            }
            if (!Schema::hasColumn('polling_unit_agent_assignments', 'voter_evidence_document')) {
                $table->string('voter_evidence_document')->nullable()->after('identity_document');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('polling_unit_agent_assignments')) {
            return;
        }

        Schema::table('polling_unit_agent_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('polling_unit_agent_assignments', 'registered_polling_unit_id')) {
                $table->dropForeign('puaa_registered_pu_fk');
            }
            if (Schema::hasColumn('polling_unit_agent_assignments', 'override_authorized_by')) {
                $table->dropForeign('puaa_override_by_fk');
            }

            foreach ([
                'assignment_type',
                'registered_polling_unit_id',
                'override_reason',
                'override_authorized_by',
                'override_authorized_at',
                'voter_evidence_document',
            ] as $column) {
                if (Schema::hasColumn('polling_unit_agent_assignments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
