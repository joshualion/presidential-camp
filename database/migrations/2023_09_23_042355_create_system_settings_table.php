<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('package', ['presidential', 'governorship', 'senatorial', 'federal_constituency', 'chairmanship', 'state', 'lga', 'ward', 'pu'])->default('presidential');
            $table->string('domain', 255)->nullable();
            $table->string('api_token', 64)->nullable();
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('login_page_background')->nullable();
            $table->string('dark_theme_color')->nullable();
            $table->string('light_theme_color')->nullable();
            $table->string('system_name')->nullable();
            $table->string('campaign_slogan')->nullable();
            $table->string('system_email')->nullable();
            $table->string('system_currency')->nullable();
            $table->decimal('exchange_rate', 10, 2)->nullable();
            $table->string('company_address')->nullable();
            $table->string('system_country')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('activation_code')->nullable();
            $table->boolean('frontend_community')->default(false);
            $table->boolean('frontend_registration')->default(false);
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('copyright')->nullable();
            $table->text('disclaimer')->nullable();
            $table->text('tos')->nullable();
            $table->text('privacy_policy')->nullable();
             // New Columns for Cloud Storage
            $table->boolean('enable_local_storage')->default(true);
            $table->boolean('enable_s3_storage')->default(false);
            $table->string('s3_key')->nullable();
            $table->string('s3_secret')->nullable();
            $table->string('s3_region')->nullable();
            $table->string('s3_bucket')->nullable();
            $table->string('s3_endpoint')->nullable();
             // New Columns for Banking & finance
             $table->string('bank')->nullable();
             $table->string('bank_account_number')->nullable();
             $table->string('bank_account_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
