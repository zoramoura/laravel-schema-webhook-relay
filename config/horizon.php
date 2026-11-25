<?php

return [

    'path' => env('HORIZON_PATH', '/horizon'),

    'middleware' => [
        'web',
    ],

    'domains' => [
        'production' => [
            'redis' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 0,
            ],
            'remember_for' => 5,
        ],
        'local' => [
            'redis' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD', null),
                'port' => env('REDIS_PORT', 6379),
                'database' => 0,
            ],
            'remember_for' => 5,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['webhook-relay', 'default'],
                'balance' => 'simple',
                'processes' => 3,
                'tries' => 3,
            ],
        ],
        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['webhook-relay', 'default'],
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
            ],
        ],
    ],

    'factories' => [
        // Exponential backoff for the webhook-relay queue: base * 2^(attempt-1).
        \App\Jobs\ProcessOutboundWebhook::class => [
            'maxExceptions' => env('WEBHOOK_OUTBOUND_MAX_ATTEMPTS', 5),
            'backoff' => [5, 15, 45, 120, 300],
        ],
    ],

];