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
        // Update the election_incidents table
        Schema::create('election_incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('election_id');
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('lga_id');
            $table->unsignedBigInteger('ward_id');
            $table->unsignedBigInteger('polling_unit_id');
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->foreign('election_id')->references('id')->on('elections')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('lga_id')->references('id')->on('local_government_areas')->onDelete('cascade');
            $table->foreign('ward_id')->references('id')->on('wards')->onDelete('cascade');
            $table->foreign('polling_unit_id')->references('id')->on('polling_units')->onDelete('cascade');
        });

        // Create the picture_evidences table
        Schema::create('picture_evidences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_incident_id');
            $table->string('file_path'); // Path to the picture file
            $table->timestamps();

            $table->foreign('election_incident_id')
                  ->references('id')
                  ->on('election_incidents')
                  ->onDelete('cascade');
        });

        // Create the video_evidences table
        Schema::create('video_evidences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_incident_id');
            $table->string('file_path'); // Path to the video file
            $table->timestamps();

            $table->foreign('election_incident_id')
                  ->references('id')
                  ->on('election_incidents')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_evidences');
        Schema::dropIfExists('picture_evidences');
        Schema::dropIfExists('election_incidents');
    }
};
