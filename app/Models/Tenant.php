<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A tenant is an isolated webhook consumer. Each tenant owns its HMAC secret,
 * plugin manifest, and delivery history.
 */
class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'hmac_secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(DeliveryLog::class);
    }

    public function deadLetterDeliveries(): HasMany
    {
        return $this->hasMany(DeadLetterDelivery::class);
    }

    public function manifest(): HasOne
    {
        return $this->hasOne(PluginManifest::class);
    }

    /**
     * Verify an HMAC-SHA256 signature against this tenant's secret.
     */
    public function verifySignature(string $rawBody, string $signature): bool
    {
        if (empty($this->hmac_secret) || empty($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $this->hmac_secret);
        $provided = $this->normalizeSignature($signature);

        return hash_equals($expected, $provided);
    }

    private function normalizeSignature(string $signature): string
    {
        // Support both "sha256=..." and raw hex prefixed formats.
        if (str_starts_with($signature, 'sha256=')) {
            return substr($signature, 7);
        }

        return $signature;
    }
}