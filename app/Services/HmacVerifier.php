<?php

namespace App\Services;

use App\Models\Tenant;

/**
 * HMAC-SHA256 signature verification using constant-time comparison.
 */
class HmacVerifier
{
    public function verify(Tenant $tenant, string $rawBody, string $signature): bool
    {
        return $tenant->verifySignature($rawBody, $signature);
    }
}