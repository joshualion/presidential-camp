<?php

return [
    // Only the application owner can expose the existing hosted SMS integration.
    'portal_visible' => false, // Portal credit resale is outside v1.2.5.
    'batch_size' => 50,
    'requests_per_minute' => 60,
    'maximum_recipients' => 50000,
    'termii_hosts' => ['api.ng.termii.com', 'api.termii.com', 'v3.api.termii.com'],
];
