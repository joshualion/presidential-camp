<?php

return [
    'deployment_mode' => env('DEPLOYMENT_MODE', 'managed'),
    'installer_enabled' => env('INSTALLER_ENABLED', true),
    'portal_api_base' => rtrim(env('CAMPAIGN_PORTAL_API_BASE', 'https://campaignmanager.ng/api/v2'), '/'),
    'portal_directory_base' => rtrim(env('CAMPAIGN_PORTAL_DIRECTORY_BASE', 'https://campaignmanager.ng/api'), '/'),
    'portal_web_base' => rtrim(env('CAMPAIGN_PORTAL_WEB_BASE', 'https://campaignmanager.ng'), '/'),
    'package_type' => env('CAMPAIGN_PACKAGE_TYPE', 'presidential'),
    'installer_location_chunk_size' => (int) env('INSTALLER_LOCATION_CHUNK_SIZE', 750),
    'installer_location_batch_size' => (int) env('INSTALLER_LOCATION_BATCH_SIZE', 500),
    'modules' => [
        'sms' => (bool) env('CAMPAIGN_MODULE_SMS', false),
    ],
    'community' => [
        'polling' => [
            'notifications' => env('COMMUNITY_NOTIFICATION_POLL_MS', 30000),
            'feed' => env('COMMUNITY_FEED_POLL_MS', 45000),
            'message_thread' => env('COMMUNITY_MESSAGE_THREAD_POLL_MS', 7000),
            'conversation_list' => env('COMMUNITY_CONVERSATION_POLL_MS', 20000),
            'message_badge' => env('COMMUNITY_MESSAGE_BADGE_POLL_MS', 30000),
        ],
        'media' => [
            'image_max_mb' => env('COMMUNITY_IMAGE_MAX_MB', 5),
            'video_max_mb' => env('COMMUNITY_VIDEO_MAX_MB', 20),
        ],
    ],
];
