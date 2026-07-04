<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('announcements', 'uuid')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }

        DB::table('announcements')
            ->whereNull('uuid')
            ->orderBy('id')
            ->each(function ($announcement) {
                DB::table('announcements')->where('id', $announcement->id)->update([
                    'uuid' => (string) Str::uuid(),
                ]);
            });
    }

    public function down(): void
    {
        // Compatibility migration: UUIDs are permanent public identifiers and are not removed.
    }
};
