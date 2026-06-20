<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('system_settings', 'community_image_max_mb')) {
                $table->unsignedInteger('community_image_max_mb')->nullable()->after('community_attachment_max_mb');
            }

            if (!Schema::hasColumn('system_settings', 'community_video_max_mb')) {
                $table->unsignedInteger('community_video_max_mb')->nullable()->after('community_image_max_mb');
            }
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            if (Schema::hasColumn('system_settings', 'community_video_max_mb')) {
                $table->dropColumn('community_video_max_mb');
            }

            if (Schema::hasColumn('system_settings', 'community_image_max_mb')) {
                $table->dropColumn('community_image_max_mb');
            }
        });
    }
};
