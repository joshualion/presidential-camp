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
        Schema::create('funding_requests', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->text('description')->nullable();
            $table->decimal('amount_requested', 12, 2);
            $table->decimal('amount_approved', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        
            // Relationships
            $table->foreignId('user_id')->constrained('users')->onDelete('set null'); // Originator & receiver
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
            $table->foreignId('lga_id')->nullable()->constrained('local_government_areas')->onDelete('set null');
            $table->foreignId('ward_id')->nullable()->constrained('wards')->onDelete('set null');
            $table->foreignId('pu_id')->nullable()->constrained('polling_units')->onDelete('set null');
        
           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_requests');
    }
};
