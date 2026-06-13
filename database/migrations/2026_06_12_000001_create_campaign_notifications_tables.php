<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 100)->index();
            $table->string('title');
            $table->text('message');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('action_url')->nullable();
            $table->string('severity', 20)->default('info')->index();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('senatorial_district_id')->nullable()->constrained('senatorial_districts')->nullOnDelete();
            $table->foreignId('federal_constituency_id')->nullable()->constrained('federal_constituencies')->nullOnDelete();
            $table->foreignId('lga_id')->nullable()->constrained('local_government_areas')->nullOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();
            $table->foreignId('polling_unit_id')->nullable()->constrained('polling_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->dateTime('expires_at')->index();
            $table->timestamps();

            $table->index(['type', 'subject_type', 'subject_id'], 'campaign_notifications_subject_idx');
            $table->index(['state_id', 'expires_at'], 'campaign_notifications_state_expiry_idx');
            $table->index(['polling_unit_id', 'expires_at'], 'campaign_notifications_pu_expiry_idx');
        });

        Schema::create('campaign_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('campaign_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('read_at')->nullable()->index();
            $table->dateTime('dismissed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['notification_id', 'user_id'], 'campaign_notification_recipient_unique');
            $table->index(['user_id', 'read_at', 'dismissed_at'], 'campaign_notification_recipient_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_notification_recipients');
        Schema::dropIfExists('campaign_notifications');
    }
};
