<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('polling_unit_agent_assignments')) {
            Schema::create('polling_unit_agent_assignments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('polling_unit_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('pending');
                $table->string('source', 50)->default('self_request');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('revoked_at')->nullable();
                $table->text('revoke_reason')->nullable();
                $table->text('notes')->nullable();
                $table->string('identity_verification_status', 30)->default('pending');
                $table->string('identity_type')->nullable();
                $table->string('identity_number')->nullable();
                $table->string('identity_document')->nullable();
                $table->string('passport_photo')->nullable();
                $table->string('current_address')->nullable();
                $table->boolean('availability_confirmed')->default(false);
                $table->text('willingness_statement')->nullable();
                $table->timestamps();

                $table->index(['status', 'polling_unit_id'], 'puaa_status_polling_unit_idx');
                $table->index(['user_id', 'status'], 'puaa_user_status_idx');
                $table->index(['polling_unit_id', 'user_id', 'status'], 'puaa_polling_user_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_unit_agent_assignments');
    }
};
