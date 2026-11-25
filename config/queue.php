<?php

return [

    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
        ],

        'webhook-relay' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'webhook-relay',
            'retry_after' => 300,
        ],

    ],

    'failed' => [
        'driver' => 'database',
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];