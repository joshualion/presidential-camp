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
    'supported_sources' => ['1.0.8', '1.0.9', '1.1.0', '1.1.1', '1.1.2', '1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8', '1.1.9', '1.2.0', '1.2.1', '1.2.2', '1.2.3', '1.2.4', '1.2.5'],
    // Names are added only after reviewing their up() implementation and compatibility.
    'approved_migrations' => [
        '2026_11_06_000014_add_campaign_finance_request_routes' => '2a84afbaff970b7088acfb77b4885ec23aa5d404c4b348d6b19f2fb420a43552',
        '2026_11_06_000013_add_campaign_finance_manual_expenses' => '53ee594d57b039b8b5eaaa94601352916313d1954168defde3f3bb6ad7ac947b',
        '2026_11_05_000012_add_campaign_finance_reversals' => 'def6b4cb037deaf0ca1a6a5de44b4ec88d89162bec6b3c997ef2bcf41051798e',
        '2026_11_05_000010_add_campaign_finance_journal' => 'ab737a5aba0f27c598d067641826d6d736698d34d923facabd61c6f73679545a',
        '2026_11_05_000011_add_campaign_finance_policies' => 'd0443e3b9fef25456473343b29c4532a852d4c59edc3906972c03575e38ac4c7',
        '2026_11_04_000010_create_campaign_finance_tables' => '26e4786966fe069d0b513e909d81a8a6d60766a3a232e6194d2204bbe5ef38f2',
        '2026_11_04_125003_create_campaign_inventory' => '4a7847dc3c006578e90b11ac944c692f6c92f351c5cbe01f88f42f977b6a284a',
        '2026_11_04_125004_capture_legacy_sms_access' => 'aea680253c2c65813ccb741718052f435ed5fa978bbbeb947f20dd7483a5ca21',
        '2026_11_04_125004_create_enterprise_ai_tables' => 'bf6c2a49bdfc64cf981d185c5d563dac8485b2e6c48b62539c023a2d0b93fc33',
        '2026_10_28_000001_register_enterprise_capabilities' => '4a71e508d7befb9fcf7a39351079753609e0cd4f5dd4350b9fcce9587b4c0128',
        '2026_10_25_000001_add_managed_campaign_capabilities' => '477066dfed6bd774e5f52e82216981212abba501d41b6b0992a34801c64e8b55',
        '2026_10_25_000002_add_incident_evidence_metadata' => '54337f8482162a3b96c8638a5549c131816e9efab526db61838463daeb5ab66a',
        '2026_10_20_000001_add_nigeria_democratic_congress' => '06036a50125f8670adb74fed01913a89516f62769bc8e222d7cab26a04bac594',
        '2026_10_19_000002_create_direct_sms_tables' => '0909ab8a804ce0fedb195c0adb0091f42e9f1ca155727b18eb6775771e9e1a8a',
        '2026_09_06_000001_add_boundary_inec_codes_to_location_tables' => '579b7ba008504af708258ab255c15739b06b2d84c2f69c5b2458a6a2f1176eac',
    ],
    'created_tables' => ['campaign_finance_request_routes', 'campaign_finance_manual_expenses', 'campaign_finance_reversals', 'campaign_finance_journal', 'campaign_finance_ledger_checks', 'campaign_finance_policies', 'campaign_finance_appointments', 'campaign_finance_workflows', 'campaign_finance_decisions', 'campaign_finance_accounts', 'campaign_finance_budgets', 'campaign_finance_requests', 'campaign_finance_entries', 'campaign_finance_audits', 'campaign_finance_documents', 'campaign_material_categories', 'campaign_materials', 'campaign_material_stocks', 'campaign_material_requests', 'campaign_material_request_events', 'campaign_material_movements', 'campaign_enterprise_legacy_access', 'enterprise_ai_settings', 'enterprise_ai_usage', 'enterprise_ai_audits', 'campaign_permission_restrictions', 'campaign_default_roles', 'sms_provider_grants', 'sms_provider_accounts', 'sms_direct_batches', 'sms_direct_messages', 'sms_direct_suppressions', 'sms_direct_events'],
];

