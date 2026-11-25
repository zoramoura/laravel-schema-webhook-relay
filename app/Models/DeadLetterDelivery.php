<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dead-letter record: an outbound delivery that exhausted its retry budget.
 * Queryable; supports one-click and bulk replay via the DLQ controller.
 */
class DeadLetterDelivery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'webhook_event_id',
        'tenant_id',
        'target_url',
        'method',
        'request_headers',
        'request_body',
        'last_error',
        'attempts',
        'failed_at',
        'replayed_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'attempts' => 'integer',
        'failed_at' => 'datetime',
        'replayed_at' => 'datetime',
    ];

    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function markReplayed(): void
    {
        $this->update(['replayed_at' => now()]);
    }
}