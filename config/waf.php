<?php

return [
    'enabled' => env('WAF_ENABLED', true),
    
    'rate_limit' => [
        'max_attempts' => env('RATE_LIMIT', 60),
        'decay_minutes' => 1,
    ],
    
    'sql_injection' => [
        'enabled' => true,
        'patterns' => config_path('waf/rules/sql_patterns.json'),
    ],
    
    'xss' => [
        'enabled' => true,
        'patterns' => config_path('waf/rules/xss_patterns.json'),
    ],
    
    'brute_force' => [
        'enabled' => true,
        'max_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
        'block_duration' => 900, // 15 minutes
    ],
    
    'logging' => [
        'enabled' => true,
        'level' => 'warning',
        'storage_days' => 30,
    ],
];