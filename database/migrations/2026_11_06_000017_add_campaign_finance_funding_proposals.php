<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_funding_proposals')) {
            Schema::create('campaign_finance_funding_proposals', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->uuid('submission_key')->unique();
                $table->unsignedBigInteger('requested_by')->index();
                $table->string('requesting_level', 24);
                $table->unsignedBigInteger('requesting_location_id')->nullable();
                $table->string('purpose', 500);
                $table->unsignedBigInteger('amount_minor');
                $table->json('recipients');
                $table->string('status', 24)->default('submitted');
                $table->unsignedBigInteger('assigned_request_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Keep source requests and approval history for auditability.
    }
};
