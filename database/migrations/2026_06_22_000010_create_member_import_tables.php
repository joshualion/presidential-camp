<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_import_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename')->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('error_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('member_import_row_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_import_batch_id')->constrained('member_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('identifier')->nullable();
            $table->text('error_message');
            $table->json('row_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_import_row_errors');
        Schema::dropIfExists('member_import_batches');
    }
};
