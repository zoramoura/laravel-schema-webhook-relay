<?php

use Monolog\Handler\StreamHandler;
use Monolog\Processor\JsonFormatter;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => env('LOG_PATH', storage_path('logs/laravel.log')),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'tap' => [\App\Logging\JsonFormatterProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => 'php://stderr',
            ],
            'level' => env('LOG_LEVEL', 'debug'),
            'processors' => [JsonFormatter::class],
        ],

    ],

];