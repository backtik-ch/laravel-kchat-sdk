<?php

return [
    'base_url' => env('KCHAT_BASE_URL'),

    'token' => env('KCHAT_TOKEN'),
    'bot_user_id' => env('KCHAT_BOT_USER_ID'),

    'timeout' => env('KCHAT_TIMEOUT', 10),

    'cache' => [
        'enabled' => env('KCHAT_CACHE_ENABLED', true),
        'ttl' => env('KCHAT_CACHE_TTL', 3600),
    ],
];
