<?php

return [
    'enabled' => env('CAMPAIGN_UPDATE_CHECKS_ENABLED', true),
    'repository' => 'joshualion/Campaign-Manager',
    'maximum_package_bytes' => 536870912,
    'maximum_expanded_bytes' => 1610612736,
    'maximum_files' => 50000,
    'timeout' => 20,
];
