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
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('party_id');
            $table->string('name'); // Name of the election (e.g., "Presidential Election 2024")
            $table->date('year'); // Year of the election
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'ongoing', 'inconclusive', 'concluded', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('party_id')->references('id')->on('political_parties')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
