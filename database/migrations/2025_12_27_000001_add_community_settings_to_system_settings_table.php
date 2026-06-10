<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->unsignedInteger('community_post_max_length')->nullable()->after('tos');
            $table->unsignedInteger('community_comment_max_length')->nullable()->after('community_post_max_length');
            $table->unsignedInteger('community_daily_post_limit')->nullable()->after('community_comment_max_length');
            $table->unsignedInteger('community_daily_comment_limit')->nullable()->after('community_daily_post_limit');
            $table->unsignedInteger('community_attachment_max_mb')->nullable()->after('community_daily_comment_limit');
            $table->boolean('community_allow_images')->nullable()->after('community_attachment_max_mb');
            $table->boolean('community_allow_videos')->nullable()->after('community_allow_images');
            $table->text('community_guidelines')->nullable()->after('community_allow_videos');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn([
                'community_post_max_length',
                'community_comment_max_length',
                'community_daily_post_limit',
                'community_daily_comment_limit',
                'community_attachment_max_mb',
                'community_allow_images',
                'community_allow_videos',
                'community_guidelines',
            ]);
        });
    }
};
