<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_stat_snapshots', function (Blueprint $table) {
            $table->string('package', 191)->change();
            $table->string('scope_key', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_stat_snapshots', function (Blueprint $table) {
            $table->string('package', 50)->change();
            $table->string('scope_key', 100)->change();
        });
    }
};
