<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('location_provisioning_runs')) {
            return;
        }

        Schema::create('location_provisioning_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_license_id')->nullable()->constrained('local_licenses')->nullOnDelete();
            $table->string('deployment_mode')->nullable()->index();
            $table->string('package_type')->nullable()->index();
            $table->string('scope_type')->nullable()->index();
            $table->string('scope_name')->nullable();
            $table->string('status')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('summary')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_provisioning_runs');
    }
};
