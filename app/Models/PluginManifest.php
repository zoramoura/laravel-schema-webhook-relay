<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Declarative JSON-schema plugin manifest owned by a tenant.
 * Contains event filters, payload validation schema, field mapping, and
 * outbound targets. Evaluated deterministically — no code execution.
 */
class PluginManifest extends Model
{
    protected $fillable = [
        'tenant_id',
        'manifest',
        'is_active',
    ];

    protected $casts = [
        'manifest' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}