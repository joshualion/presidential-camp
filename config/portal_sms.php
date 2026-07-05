<?php

return [
    // This must end at the portal's versioned core API prefix, not the licensing /api/v2 prefix.
    'base_url' => rtrim(env('CAMPAIGN_PORTAL_SMS_BASE_URL', 'https://campaignmanager.ng/api/core/v1'), '/'),
    'required_path_suffix' => '/api/core/v1',

    'endpoints' => [
        'estimate' => '/sms/estimate',
        'batches.create' => '/sms/batches',
        'batches.index' => '/sms/batches',
        'batches.show' => '/sms/batches/{batch_reference}',
        'batches.messages' => '/sms/batches/{batch_reference}/messages',
        'batches.messages.mark_synced' => '/sms/batches/{batch_reference}/messages/mark-synced',
        'wallet.show' => '/sms/wallet',
        'sender_ids.index' => '/sms/sender-ids',
        'sender_ids.create' => '/sms/sender-ids',
        'topups.initiate' => '/sms/topups/initiate',
        'allocations.index' => '/sms/allocations',
        'allocations.show' => '/sms/allocations/{allocation_reference}',
        'allocations.mark_synced' => '/sms/allocations/{allocation_reference}/mark-synced',
        'credit_requests.create' => '/sms/credit-requests',
        'credit_requests.index' => '/sms/credit-requests',
        'credit_requests.show' => '/sms/credit-requests/{request_reference}',
    ],

    'hmac' => [
        'algorithm' => 'sha256',
        'max_clock_skew_seconds' => 300,
        'headers' => [
            'client_key' => 'X-CM-Client-Key',
            'timestamp' => 'X-CM-Timestamp',
            'signature' => 'X-CM-Signature',
            'idempotency_key' => 'X-CM-Idempotency-Key',
        ],
    ],

    'compose' => [
        // Zero means the core does not impose an audience cap. Provider dispatch chunking is portal-owned.
        'max_batch_recipients' => (int) env('SMS_MAX_BATCH_RECIPIENTS', 0),
        'large_batch_warning' => (int) env('SMS_LARGE_BATCH_WARNING', 5000),
        'message_max_characters' => (int) env('SMS_MESSAGE_MAX_CHARACTERS', 10000),
    ],

    'sync' => [
        // Audited portal validation accepts at most 10,000 message references per acknowledgement.
        'message_ack_chunk_size' => (int) env('SMS_MESSAGE_ACK_CHUNK_SIZE', 10000),
        // Portal message pages are currently capped at 100 rows; 10,000 pages covers its 1M estimate ceiling.
        'max_message_pages' => (int) env('SMS_MAX_MESSAGE_SYNC_PAGES', 10000),
    ],
];
