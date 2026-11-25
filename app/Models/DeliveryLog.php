<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable record of one outbound delivery attempt (request/response/status).
 */
class DeliveryLog extends Model
{
    protected $fillable = [
        'webhook_event_id',
        'tenant_id',
        'target_url',
        'method',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
        'attempt',
        'succeeded',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'duration_ms' => 'integer',
        'succeeded' => 'boolean',
        'attempt' => 'integer',
    ];

    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}