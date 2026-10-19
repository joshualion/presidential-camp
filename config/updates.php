<?php

return [
    'enabled' => env('CAMPAIGN_UPDATE_CHECKS_ENABLED', true),
    'repository' => 'joshualion/Campaign-Manager',
    'maximum_package_bytes' => 536870912,
    'maximum_expanded_bytes' => 1610612736,
    'maximum_files' => 50000,
    'timeout' => 20,
    'php_cli' => env('CAMPAIGN_UPDATE_PHP_CLI'),
    'mysql_dump' => env('CAMPAIGN_UPDATE_MYSQL_DUMP'),
    'mysql_client' => env('CAMPAIGN_UPDATE_MYSQL_CLIENT'),
    // Explicit compatibility review; an unknown source version fails closed.
    'supported_sources' => ['1.0.8', '1.0.9', '1.1.0', '1.1.1'],
    // Names are added only after reviewing their up() implementation and compatibility.
    'approved_migrations' => [
        '2026_10_19_000002_create_direct_sms_tables' => '0909ab8a804ce0fedb195c0adb0091f42e9f1ca155727b18eb6775771e9e1a8a',
        '2026_09_06_000001_add_boundary_inec_codes_to_location_tables' => '579b7ba008504af708258ab255c15739b06b2d84c2f69c5b2458a6a2f1176eac',
    ],
    'created_tables' => ['sms_provider_grants', 'sms_provider_accounts', 'sms_direct_batches', 'sms_direct_messages', 'sms_direct_suppressions', 'sms_direct_events'],
];

