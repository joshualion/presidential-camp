<?php

return [
    'access_level_labels' => [
        'superadmin' => 'Super Admin Access',
        'nationaladmin' => 'National Admin Access',
        'regionaladmin' => 'Regional Admin Access',
        'stateadmin' => 'State Admin Access',
        'senatorialadmin' => 'Senatorial District Admin Access',
        'federaladmin' => 'Federal Constituency Admin Access',
        'lgaadmin' => 'LGA Admin Access',
        'wardadmin' => 'Ward Admin Access',
        'puadmin' => 'Polling Unit Admin Access',
        'user' => 'Member Access',
    ],

    'packages' => [
        'presidential' => [
            'access_levels' => ['superadmin', 'nationaladmin', 'regionaladmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'roles' => [
                'superadmin' => ['Super Admin'],
                'nationaladmin' => ['Presidential Candidate', 'Vice Presidential Candidate', 'National Campaign Director', 'National ICT Director', 'National Coordinator'],
                'regionaladmin' => ['Regional Coordinator', 'Regional ICT Director'],
                'stateadmin' => ['State Coordinator', 'State ICT Director'],
                'senatorialadmin' => ['Senatorial Coordinator', 'Senatorial District Coordinator', 'Senatorial ICT Director'],
                'federaladmin' => ['Federal Constituency Coordinator', 'Federal Constituency ICT Director'],
                'lgaadmin' => ['LGA Coordinator', 'LGA ICT Director'],
                'wardadmin' => ['Ward Coordinator', 'Ward ICT Director'],
                'puadmin' => ['Polling Unit Agent', 'PU Agent', 'PU ICT Director'],
                'user' => ['Member'],
            ],
        ],

        'governorship' => [
            'access_levels' => ['superadmin', 'stateadmin', 'senatorialadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'roles' => [
                'superadmin' => ['Super Admin'],
                'stateadmin' => ['Governorship Candidate', 'Deputy Governorship Candidate', 'State Campaign Director', 'State ICT Director'],
                'senatorialadmin' => ['Senatorial District Coordinator', 'Senatorial ICT Director'],
                'federaladmin' => ['Federal Constituency Coordinator', 'Federal Constituency ICT Director'],
                'lgaadmin' => ['LGA Coordinator', 'LGA ICT Director'],
                'wardadmin' => ['Ward Coordinator', 'Ward ICT Director'],
                'puadmin' => ['Polling Unit Agent', 'PU Agent', 'PU ICT Director'],
                'user' => ['Member'],
            ],
        ],

        'senatorial' => [
            'access_levels' => ['superadmin', 'senatorialadmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'roles' => [
                'superadmin' => ['Super Admin'],
                'senatorialadmin' => ['Senatorial Candidate', 'Senatorial Campaign Director', 'Senatorial ICT Director'],
                'lgaadmin' => ['LGA Coordinator', 'LGA ICT Director'],
                'wardadmin' => ['Ward Coordinator', 'Ward ICT Director'],
                'puadmin' => ['Polling Unit Agent', 'PU Agent', 'PU ICT Director'],
                'user' => ['Member'],
            ],
        ],

        'federal_constituency' => [
            'access_levels' => ['superadmin', 'federaladmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'roles' => [
                'superadmin' => ['Super Admin'],
                'federaladmin' => ['Federal Constituency Candidate', 'Federal Constituency Campaign Director', 'Federal Constituency ICT Director'],
                'lgaadmin' => ['LGA Coordinator', 'LGA ICT Director'],
                'wardadmin' => ['Ward Coordinator', 'Ward ICT Director'],
                'puadmin' => ['Polling Unit Agent', 'PU Agent', 'PU ICT Director'],
                'user' => ['Member'],
            ],
        ],

        'chairmanship' => [
            'access_levels' => ['superadmin', 'lgaadmin', 'wardadmin', 'puadmin', 'user'],
            'roles' => [
                'superadmin' => ['Super Admin'],
                'lgaadmin' => ['Chairmanship Candidate', 'Deputy Chairmanship Candidate', 'LGA Campaign Director', 'LGA ICT Director'],
                'wardadmin' => ['Ward Coordinator', 'Ward ICT Director'],
                'puadmin' => ['Polling Unit Agent', 'PU Agent', 'PU ICT Director'],
                'user' => ['Member'],
            ],
        ],

        'state_constituency' => [
            'access_levels' => ['superadmin', 'wardadmin', 'puadmin', 'user'],
            'roles' => [
                'superadmin' => ['Super Admin'],
                'wardadmin' => ['State Constituency Candidate', 'State Constituency Campaign Director', 'Ward Coordinator', 'Ward ICT Director'],
                'puadmin' => ['Polling Unit Agent', 'PU Agent', 'PU ICT Director'],
                'user' => ['Member'],
            ],
        ],
    ],
];
