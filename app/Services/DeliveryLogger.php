<?php

namespace App\Services;

use App\Models\DeliveryLog;
use App\Models\Tenant;
use App\Models\WebhookEvent;

/**
 * Persists an immutable delivery-log record for one outbound attempt.
 */
class DeliveryLogger
{
    public function log(
        WebhookEvent $event,
        Tenant $tenant,
        string $targetUrl,
        string $method,
        array $requestHeaders,
        array $requestBody,
        ?int $responseStatus,
        array $responseHeaders,
        array $responseBody,
        ?int $durationMs,
        int $attempt,
        bool $succeeded,
    ): DeliveryLog {
        return DeliveryLog::create([
            'webhook_event_id' => $event->id,
            'tenant_id' => $tenant->id,
            'target_url' => $targetUrl,
            'method' => $method,
            'request_headers' => $requestHeaders,
            'request_body' => $requestBody,
            'response_status' => $responseStatus,
            'response_headers' => $responseHeaders,
            'response_body' => $responseBody,
            'duration_ms' => $durationMs,
            'attempt' => $attempt,
            'succeeded' => $succeeded,
        ]);
    }
}