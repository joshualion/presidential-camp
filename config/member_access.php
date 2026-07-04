<?php

return [
    'capabilities' => [
        'dashboard.view' => [
            'label' => 'Member dashboard',
            'status' => 'available',
        ],
        'profile.manage' => [
            'label' => 'Profile management',
            'status' => 'available',
        ],
        'account.security' => [
            'label' => 'Password and account security',
            'status' => 'available',
        ],
        'notifications.view' => [
            'label' => 'Campaign notifications',
            'status' => 'available',
        ],
        'messaging.use' => [
            'label' => 'Internal communication',
            'status' => 'available',
            'requires_location' => 'polling_unit_id',
        ],
        'community.use' => [
            'label' => 'Community forum',
            'status' => 'available',
            'module' => 'community',
            'runtime_gate' => 'community_enabled',
            'requires_location' => 'polling_unit_id',
        ],
        'agents.participate' => [
            'label' => 'Polling-unit agent application and status',
            'status' => 'available',
            'module' => 'agents',
            'requires_location' => 'polling_unit_id',
        ],
        'announcements.dashboard' => [
            'label' => 'Dashboard announcements',
            'status' => 'available',
        ],
        'voting_block.view' => [
            'label' => 'Voting bloc',
            'status' => 'available',
            'requires_location' => 'polling_unit_id',
            'runtime_gate' => 'public_registration_enabled',
        ],
        'referrals.view' => [
            'label' => 'Referral list',
            'status' => 'available',
            'requires_location' => 'polling_unit_id',
            'runtime_gate' => 'public_registration_enabled',
        ],
        'polling_unit.details' => [
            'label' => 'Polling-unit details',
            'status' => 'planned',
            'requires_location' => 'polling_unit_id',
        ],
        'announcements.board' => [
            'label' => 'Notice board',
            'status' => 'available',
            'requires_location' => 'polling_unit_id',
        ],
        'elections.reports' => [
            'label' => 'Member election reports',
            'status' => 'available',
            'module' => 'elections',
            'requires_location' => 'polling_unit_id',
        ],
        'elections.submit' => [
            'label' => 'Assigned-agent election submission',
            'status' => 'available',
            'module' => 'elections',
            'requires_location' => 'polling_unit_id',
            'runtime_gate' => 'approved_agent_assignment',
        ],
    ],

    'referrals' => [
        'max_depth' => 25,
        'max_members' => 10000,
    ],
];
