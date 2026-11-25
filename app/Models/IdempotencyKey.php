<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks idempotency keys per tenant to deduplicate repeated webhook
 * deliveries within the configured TTL window.
 */
class IdempotencyKey extends Model
{
    protected $fillable = [
        'tenant_id',
        'key',
        'webhook_event_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }
}