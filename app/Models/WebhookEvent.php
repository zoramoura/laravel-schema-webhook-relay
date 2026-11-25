<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Record of an ingested webhook event (after security checks pass).
 */
class WebhookEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'idempotency_key',
        'event_type',
        'payload',
        'headers',
        'ip_address',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(DeliveryLog::class);
    }
}