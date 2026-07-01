<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_notification_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('access_level', 50)->unique();
            $table->boolean('enabled')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('submission_token')->unique();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->string('title');
            $table->longText('body');
            $table->string('cta_label')->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->string('recipient_group', 50);
            $table->json('selected_access_levels')->nullable();
            $table->json('selected_roles')->nullable();
            $table->json('audience_filters')->nullable();
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['sender_id', 'created_at']);
        });

        Schema::create('email_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('email_notification_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('status', 20)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_notification_recipients');
        Schema::dropIfExists('email_notification_campaigns');
        Schema::dropIfExists('email_notification_permissions');
    }
};
