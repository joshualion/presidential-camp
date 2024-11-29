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

        Schema::create('polling_unit_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('election_id');
            $table->unsignedBigInteger('polling_unit_id');
            $table->string('result_sheet')->nullable();
            $table->timestamps();
        
            $table->foreign('election_id')->references('id')->on('elections')->onDelete('cascade');
            $table->foreign('polling_unit_id')->references('id')->on('polling_units')->onDelete('cascade');
        });
       
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('election_id');
            $table->unsignedBigInteger('party_id');
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('lga_id');
            $table->unsignedBigInteger('ward_id');
            $table->unsignedBigInteger('polling_unit_id');
            $table->unsignedBigInteger('polling_unit_result_id')->nullable(); // Link to polling_unit_results
            $table->integer('quantity');
            $table->timestamps();
        
            $table->foreign('election_id')->references('id')->on('elections')->onDelete('cascade');
            $table->foreign('party_id')->references('id')->on('political_parties')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('lga_id')->references('id')->on('local_government_areas')->onDelete('cascade');
            $table->foreign('ward_id')->references('id')->on('wards')->onDelete('cascade');
            $table->foreign('polling_unit_id')->references('id')->on('polling_units')->onDelete('cascade');
            $table->foreign('polling_unit_result_id')->references('id')->on('polling_unit_results')->onDelete('cascade');
        });
        
       
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
