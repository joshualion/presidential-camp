<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_stat_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('package', 50);
            $table->string('access_level', 50);
            $table->string('scope_type', 50);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('scope_key', 100);
            $table->json('payload');
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamps();

            $table->unique(['package', 'access_level', 'scope_key'], 'dashboard_stat_snapshot_unique');
            $table->index(['scope_type', 'scope_id'], 'dashboard_stat_snapshot_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_stat_snapshots');
    }
};
