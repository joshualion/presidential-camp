<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_provisioning_batches', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->index();
            $table->string('activation_id')->nullable()->index();
            $table->string('provisioning_session_id')->nullable()->index();
            $table->string('session_token', 1024)->nullable();
            $table->string('location_data_version')->nullable();
            $table->string('package_type')->nullable()->index();
            $table->string('release_profile')->nullable()->index();
            $table->json('scope_payload')->nullable();
            $table->string('status')->index();
            $table->string('current_dataset')->nullable()->index();
            $table->unsignedInteger('current_cursor')->default(0);
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('imported_records')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_provisioning_batches');
    }
};
