<?php

return [

    'webhook' => [

        // Tenant-scoped ingestion signing secret is stored per-tenant in the DB.
        // This is only a fallback/global default if a tenant has no secret.
        'default_secret' => env('WEBHOOK_DEFAULT_SECRET', 'dev-only-insecure-secret-change-me'),

        // Replay window in seconds for the X-Webhook-Timestamp header.
        'replay_window_seconds' => (int) env('WEBHOOK_REPLAY_WINDOW_SECONDS', 300),

        // Outbound delivery defaults.
        'outbound' => [
            'base_delay_seconds' => (int) env('WEBHOOK_OUTBOUND_BASE_DELAY_SECONDS', 5),
            'max_attempts' => (int) env('WEBHOOK_OUTBOUND_MAX_ATTEMPTS', 5),
            'timeout_seconds' => (int) env('WEBHOOK_OUTBOUND_TIMEOUT_SECONDS', 10),
        ],

    ],

];