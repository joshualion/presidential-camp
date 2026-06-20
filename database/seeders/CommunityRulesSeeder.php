<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunityRulesSeeder extends Seeder
{
    public function run(): void
    {
        $settings = DB::table('system_settings')->first();
        if (!$settings) {
            return;
        }

        $values = [
            'community_post_max_length' => 750,
            'community_comment_max_length' => 750,
            'community_daily_post_limit' => 50,
            'community_daily_comment_limit' => 200,
            'community_attachment_max_mb' => 5,
            'community_allow_images' => 1,
            'community_allow_videos' => 0,
            'community_guidelines' => '
                <h3>Community Guidelines</h3>
                <ul>
                    <li>Be respectful: no harassment, hate speech, or personal attacks.</li>
                    <li>Stay on topic: keep posts and comments relevant to the community and its goals.</li>
                    <li>No misinformation: avoid sharing unverified or intentionally misleading content.</li>
                    <li>Protect privacy: do not post personal or sensitive information about yourself or others.</li>
                    <li>No spam: avoid repeated promotions, mass tagging, or irrelevant links.</li>
                    <li>Follow media rules: share only appropriate images/videos and respect copyright.</li>
                    <li>Report issues: flag abusive content to admins instead of engaging.</li>
                </ul>
                ',
        ];

        if (Schema::hasColumn('system_settings', 'community_image_max_mb')) {
            $values['community_image_max_mb'] = 5;
        }

        if (Schema::hasColumn('system_settings', 'community_video_max_mb')) {
            $values['community_video_max_mb'] = 20;
        }

        DB::table('system_settings')
            ->where('id', $settings->id)
            ->update($values);
    }
}
