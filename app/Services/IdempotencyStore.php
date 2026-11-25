<?php

namespace App\Services;

use App\Models\IdempotencyKey;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Support\Carbon;

/**
 * Idempotency key deduplication: atomic insert per (tenant, key); repeats
 * within the TTL are recognised and do not trigger duplicate work.
 */
class IdempotencyStore
{
    public function __construct(
        private int $ttlSeconds = 86400,
    ) {
        //
    }

    public function exists(Tenant $tenant, string $key): bool
    {
        return IdempotencyKey::where('tenant_id', $tenant->id)
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function record(Tenant $tenant, string $key, WebhookEvent $event): void
    {
        IdempotencyKey::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'key' => $key,
            ],
            [
                'webhook_event_id' => $event->id,
                'expires_at' => now()->addSeconds($this->ttlSeconds),
            ]
        );
    }
}