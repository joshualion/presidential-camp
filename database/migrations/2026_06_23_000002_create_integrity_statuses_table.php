<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrity_statuses', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('manifest_present')->default(false);
            $table->boolean('manifest_signature_valid')->nullable();
            $table->boolean('protected_files_valid')->nullable();
            $table->boolean('tampered')->default(false);
            $table->json('tamper_details')->nullable();
            $table->string('app_version')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrity_statuses');
    }
};
