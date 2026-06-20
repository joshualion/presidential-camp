<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key');
            $table->string('portal_license_id')->nullable();
            $table->string('deployment_mode', 40);
            $table->string('package_type', 80)->nullable();
            $table->string('status', 80)->nullable();
            $table->string('app_url')->nullable();
            $table->string('domain')->nullable();
            $table->string('activation_id')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('grace_until')->nullable();
            $table->timestamp('maintenance_expires_at')->nullable();
            $table->timestamp('updates_until')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('local_license_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_license_id')->constrained('local_licenses')->cascadeOnDelete();
            $table->string('scope_type', 80)->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('state_name')->nullable();
            $table->unsignedBigInteger('senatorial_district_id')->nullable();
            $table->string('senatorial_district_name')->nullable();
            $table->unsignedBigInteger('federal_constituency_id')->nullable();
            $table->string('federal_constituency_name')->nullable();
            $table->unsignedBigInteger('lga_id')->nullable();
            $table->string('lga_name')->nullable();
            $table->unsignedBigInteger('state_constituency_id')->nullable();
            $table->string('state_constituency_name')->nullable();
            $table->string('scope_name')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('local_license_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_license_id')->constrained('local_licenses')->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('enabled')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('license_check_logs')) {
            Schema::create('license_check_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('local_license_id')->nullable()->constrained('local_licenses')->nullOnDelete();
                $table->string('license_key')->nullable();
                $table->string('action')->nullable();
                $table->string('status')->nullable();
                $table->text('message')->nullable();
                $table->timestamp('checked_at')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('license_check_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('license_check_logs', 'local_license_id')) {
                    $table->foreignId('local_license_id')->nullable()->after('id')->constrained('local_licenses')->nullOnDelete();
                }
                if (!Schema::hasColumn('license_check_logs', 'license_key')) {
                    $table->string('license_key')->nullable();
                }
                if (!Schema::hasColumn('license_check_logs', 'action')) {
                    $table->string('action')->nullable();
                }
                if (!Schema::hasColumn('license_check_logs', 'status')) {
                    $table->string('status')->nullable();
                }
                if (!Schema::hasColumn('license_check_logs', 'message')) {
                    $table->text('message')->nullable();
                }
                if (!Schema::hasColumn('license_check_logs', 'checked_at')) {
                    $table->timestamp('checked_at')->nullable();
                }
                if (!Schema::hasColumn('license_check_logs', 'raw_payload')) {
                    $table->json('raw_payload')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('license_check_logs');
        Schema::dropIfExists('local_license_modules');
        Schema::dropIfExists('local_license_scopes');
        Schema::dropIfExists('local_licenses');
    }
};
