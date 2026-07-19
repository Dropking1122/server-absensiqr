<?php

return [
    'online_threshold_minutes'    => (int) env('ONLINE_THRESHOLD_MINUTES', 90),
    'heartbeat_log_retention_days' => (int) env('HEARTBEAT_LOG_RETENTION_DAYS', 90),
    'current_stable_version'      => env('CURRENT_STABLE_VERSION', '1.0.0'),
    'dev_email'                   => env('DEV_EMAIL', 'developer@yourdomain.com'),
    'dev_password'                => env('DEV_PASSWORD', 'rahasia123'),
];
