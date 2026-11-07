<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('campaign_finance_governance')) {
            Schema::create('campaign_finance_governance', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->unsignedBigInteger('previous_id')->unique();
                $table->unsignedBigInteger('actor_id');
                $table->string('mode', 24)->default('central');
                $table->json('grants');
                $table->timestamp('created_at');
            });
        }
    }

    public function down(): void
    {
        // Access policy history is retained with finance approval records.
    }
};
