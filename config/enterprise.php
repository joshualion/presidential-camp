<?php

return [
    // This is a trust anchor, never an installation bearer token or an AI key.
    'public_key' => (trim((string) env('ENTERPRISE_ENTITLEMENT_PUBLIC_KEY')) ?: (is_file(__DIR__.'/enterprise-public.pem') ? file_get_contents(__DIR__.'/enterprise-public.pem') : null)),
    // Only reviewed public PEM values shipped in signed updates may be added here.
    'previous_public_keys' => [],
    // Deprecated rollout flag; signed or migration-bound access is now required.
    'sms_enforcement_enabled' => (bool) env('CAMPAIGN_SMS_ADDON_ENFORCEMENT', false),
    'features' => [
        'advanced_reports' => 'Advanced Reports and AI Intelligence',
        'campaign_finance' => 'Campaign Finance',
        'campaign_merchandise' => 'Campaign Merchandise and Inventory',
        'sms_messaging' => 'SMS Messaging',
    ],
    'permissions' => [
        'addons.purchase', 'reports.view', 'reports.export', 'reports.ai.generate', 'reports.settings.manage',
        'finance.view', 'finance.record', 'finance.budget.manage', 'finance.disbursement.create',
        'finance.disbursement.approve', 'finance.disbursement.execute', 'finance.reconcile',
        'finance.reports.export', 'finance.accounts.manage', 'finance.donations.manage', 'finance.documents.view', 'finance.entries.reverse',
        'merchandise.view', 'merchandise.products.manage', 'merchandise.categories.manage',
        'merchandise.inventory.manage', 'merchandise.orders.view', 'merchandise.orders.fulfil',
        'merchandise.reports.export',
    ],
    'descriptions' => [
        'advanced_reports' => 'Understand campaign activity and geographic coverage through management reports, with optional AI assistance billed separately.',
        'campaign_finance' => 'Manage campaign income, budgets, payment requests and approvals with financial accountability.',
        'campaign_merchandise' => 'Manage campaign materials, store inventory, orders and distribution between campaign offices.',
        'sms_messaging' => 'Unlock the SMS module for your installation to use your own configured BulkSMSNigeria or Termii provider. Provider delivery charges are paid separately to your chosen provider; Portal SMS wallet payments are not required.',
    ],
];

