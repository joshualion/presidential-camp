<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senatorial_districts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized_name', 191)->nullable()->index();
            $table->timestamps();

            $table->unique(['state_id', 'normalized_name'], 'senatorial_districts_state_normalized_unique');
        });

        Schema::create('federal_constituencies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('senatorial_district_id')->nullable()->constrained('senatorial_districts')->nullOnDelete();
            $table->string('name');
            $table->string('normalized_name', 191)->nullable()->index();
            $table->timestamps();

            $table->unique(['state_id', 'normalized_name'], 'federal_constituencies_state_normalized_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federal_constituencies');
        Schema::dropIfExists('senatorial_districts');
    }
};
