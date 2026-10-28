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
    'supported_sources' => ['1.0.8', '1.0.9', '1.1.0', '1.1.1', '1.1.2', '1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1'],
    // Names are added only after reviewing their up() implementation and compatibility.
    'approved_migrations' => [
        '2026_10_28_000001_register_enterprise_capabilities' => '4a71e508d7befb9fcf7a39351079753609e0cd4f5dd4350b9fcce9587b4c0128',
        '2026_10_25_000001_add_managed_campaign_capabilities' => '477066dfed6bd774e5f52e82216981212abba501d41b6b0992a34801c64e8b55',
        '2026_10_25_000002_add_incident_evidence_metadata' => '54337f8482162a3b96c8638a5549c131816e9efab526db61838463daeb5ab66a',
        '2026_10_20_000001_add_nigeria_democratic_congress' => '06036a50125f8670adb74fed01913a89516f62769bc8e222d7cab26a04bac594',
        '2026_10_19_000002_create_direct_sms_tables' => '0909ab8a804ce0fedb195c0adb0091f42e9f1ca155727b18eb6775771e9e1a8a',
        '2026_09_06_000001_add_boundary_inec_codes_to_location_tables' => '579b7ba008504af708258ab255c15739b06b2d84c2f69c5b2458a6a2f1176eac',
    ],
    'created_tables' => ['campaign_permission_restrictions', 'campaign_default_roles', 'sms_provider_grants', 'sms_provider_accounts', 'sms_direct_batches', 'sms_direct_messages', 'sms_direct_suppressions', 'sms_direct_events'],
];

