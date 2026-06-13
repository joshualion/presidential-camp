<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedBigInteger('actor_id');
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('scope', 20)->default('public'); // public|region|state|lga|ward|pu
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['scope', 'scope_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_id']);
            $table->index(['created_at']);
            $table->index(['read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_notifications');
    }
};
