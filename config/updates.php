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
    'supported_sources' => ['1.0.8', '1.0.9', '1.1.0', '1.1.1', '1.1.2', '1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1', '1.2.2', '1.2.3', '1.2.4'],
    // Names are added only after reviewing their up() implementation and compatibility.
    'approved_migrations' => [
        '2026_11_04_000010_create_campaign_finance_tables' => 'fac9103b021f7cda66ff597c1c965a385ddfedbaa91c9634ceaeb39c3c29fde8',
        '2026_11_04_125003_create_campaign_inventory' => '287aa5cebfb17b77de788db2dd1929ef70ce3ca33fb1056a126f052e98f52c64',
        '2026_11_04_125004_capture_legacy_sms_access' => '238347070692763f083273e2676396325234a7cc6bf4537b68dd79449f1bf172',
        '2026_11_04_125004_create_enterprise_ai_tables' => '5b5973666b2589c9f732b1d8202b96d9b0eff9a4525fb4908d8a474f05ea514e',
        '2026_10_28_000001_register_enterprise_capabilities' => '4a71e508d7befb9fcf7a39351079753609e0cd4f5dd4350b9fcce9587b4c0128',
        '2026_10_25_000001_add_managed_campaign_capabilities' => '477066dfed6bd774e5f52e82216981212abba501d41b6b0992a34801c64e8b55',
        '2026_10_25_000002_add_incident_evidence_metadata' => '54337f8482162a3b96c8638a5549c131816e9efab526db61838463daeb5ab66a',
        '2026_10_20_000001_add_nigeria_democratic_congress' => '06036a50125f8670adb74fed01913a89516f62769bc8e222d7cab26a04bac594',
        '2026_10_19_000002_create_direct_sms_tables' => '0909ab8a804ce0fedb195c0adb0091f42e9f1ca155727b18eb6775771e9e1a8a',
        '2026_09_06_000001_add_boundary_inec_codes_to_location_tables' => '579b7ba008504af708258ab255c15739b06b2d84c2f69c5b2458a6a2f1176eac',
    ],
    'created_tables' => ['campaign_finance_accounts', 'campaign_finance_budgets', 'campaign_finance_requests', 'campaign_finance_entries', 'campaign_finance_audits', 'campaign_finance_documents', 'campaign_material_categories', 'campaign_materials', 'campaign_material_stocks', 'campaign_material_requests', 'campaign_material_request_events', 'campaign_material_movements', 'campaign_enterprise_legacy_access', 'enterprise_ai_settings', 'enterprise_ai_usage', 'enterprise_ai_audits', 'campaign_permission_restrictions', 'campaign_default_roles', 'sms_provider_grants', 'sms_provider_accounts', 'sms_direct_batches', 'sms_direct_messages', 'sms_direct_suppressions', 'sms_direct_events'],
];

