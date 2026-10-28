<?php

return [
    // This is a trust anchor, never an installation bearer token or an AI key.
    'public_key' => env('ENTERPRISE_ENTITLEMENT_PUBLIC_KEY'),
    'features' => [
        'advanced_reports' => 'Advanced Reports and AI Intelligence',
        'campaign_finance' => 'Campaign Finance',
        'campaign_merchandise' => 'Campaign Merchandise and Inventory',
    ],
    'permissions' => [
        'addons.purchase', 'reports.view', 'reports.export', 'reports.ai.generate', 'reports.settings.manage',
        'finance.view', 'finance.record', 'finance.budget.manage', 'finance.disbursement.create',
        'finance.disbursement.approve', 'finance.disbursement.execute', 'finance.reconcile',
        'finance.reports.export', 'finance.accounts.manage', 'finance.donations.manage',
        'merchandise.view', 'merchandise.products.manage', 'merchandise.categories.manage',
        'merchandise.inventory.manage', 'merchandise.orders.view', 'merchandise.orders.fulfil',
        'merchandise.reports.export',
    ],
    'descriptions' => [
        'advanced_reports' => 'Understand campaign activity and geographic coverage through management reports, with optional AI assistance billed separately.',
        'campaign_finance' => 'Manage campaign income, budgets, payment requests and approvals with financial accountability.',
        'campaign_merchandise' => 'Manage campaign materials, store inventory, orders and distribution between campaign offices.',
    ],
];
