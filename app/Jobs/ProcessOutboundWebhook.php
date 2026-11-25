<?php

namespace App\Jobs;

use App\Models\DeadLetterDelivery;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use App\Plugins\Manifest;
use App\Plugins\ManifestLoader;
use App\Plugins\OutboundRouter;
use App\Plugins\RuleEngine;
use App\Plugins\TransformationPipeline;
use App\Services\DeliveryLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asynchronous outbound webhook dispatch with exponential backoff.
 *
 * When retries are exhausted the delivery is persisted to the dead-letter
 * queue (DeadLetterDelivery) so operators can replay it from the admin UI.
 */
class ProcessOutboundWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WebhookEvent $event,
        public int $attempt = 1,
    ) {
        //
    }

    public function handle(
        ManifestLoader $manifestLoader,
        RuleEngine $ruleEngine,
        TransformationPipeline $pipeline,
        OutboundRouter $router,
        DeliveryLogger $logger,
    ): void {
        $tenant = $this->event->tenant;

        if (! $tenant || ! $tenant->is_active) {
            Log::warning('Skipping outbound dispatch: tenant inactive or missing', [
                'event_id' => $this->event->id,
                'tenant_id' => $tenant?->id,
            ]);
            return;
        }

        // Load + evaluate the declarative plugin manifest.
        $manifest = $manifestLoader->loadForTenant($tenant);
        if (! $manifest || ! $manifest->isActive) {
            return; // No active manifest = nothing to route.
        }

        // Event filtering.
        if (! $ruleEngine->matchesEventFilter($manifest, $this->event->event_type)) {
            return;
        }

        // Payload validation against JSON-schema.
        $validated = $ruleEngine->validatePayload($manifest, $this->event->payload);

        // Deterministic field mapping.
        $mapped = $pipeline->transform($manifest, $validated);

        // Route to each outbound target.
        foreach ($router->targets($manifest) as $target) {
            $this->deliverToTarget(
                $logger, $tenant, $target, $mapped,
            );
        }
    }

    private function deliverToTarget(
        DeliveryLogger $logger,
        Tenant $tenant,
        array $target,
        array $body,
    ): void {
        $url = $target['url'] ?? '';
        $method = strtoupper($target['method'] ?? 'POST');
        $headers = $target['headers'] ?? [];

        if ($url === '') {
            return;
        }

        $start = hrtime(true);

        try {
            $response = Http::withHeaders(array_merge(
                ['Content-Type' => 'application/json'],
                $headers,
            ))->timeout(config('services.webhook.outbound.timeout_seconds', 10))
              ->{$method === 'GET' ? 'get' : 'post'}($url, $body);

            $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

            $logger->log(
                event: $this->event,
                tenant: $tenant,
                targetUrl: $url,
                method: $method,
                requestHeaders: $headers,
                requestBody: $body,
                responseStatus: $response->status(),
                responseHeaders: $response->headers(),
                responseBody: $response->json() ?? [],
                durationMs: $durationMs,
                attempt: $this->attempt,
                succeeded: $response->successful(),
            );
        } catch (ConnectionException | RequestException $e) {
            $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);
            $response = $e->response;

            $logger->log(
                event: $this->event,
                tenant: $tenant,
                targetUrl: $url,
                method: $method,
                requestHeaders: $headers,
                requestBody: $body,
                responseStatus: $response?->status(),
                responseHeaders: $response?->headers() ?? [],
                responseBody: $response ? ($response->json() ?? []) : [],
                durationMs: $durationMs,
                attempt: $this->attempt,
                succeeded: false,
            );

            // Requeue with exponential backoff if budget remains.
            $maxAttempts = config('services.webhook.outbound.max_attempts', 5);
            if ($this->attempt < $maxAttempts) {
                $this->requeueWithBackoff($this->attempt + 1);
            } else {
                $this->recordDeadLetter($tenant, $url, $method, $headers, $body, $e);
            }

            throw $e; // Let the worker mark the job as failed/attempted.
        }
    }

    private function requeueWithBackoff(int $nextAttempt): void
    {
        $baseDelay = config('services.webhook.outbound.base_delay_seconds', 5);
        // Exponential backoff: base * 2^(attempt-1).
        $delay = (int) ($baseDelay * pow(2, $nextAttempt - 2));

        self::dispatch($this->event, $nextAttempt)
            ->onQueue('webhook-relay')
            ->delay(now()->addSeconds($delay));
    }

    private function recordDeadLetter(
        Tenant $tenant,
        string $url,
        string $method,
        array $headers,
        array $body,
        \Throwable $e,
    ): void {
        DeadLetterDelivery::create([
            'webhook_event_id' => $this->event->id,
            'tenant_id' => $tenant->id,
            'target_url' => $url,
            'method' => $method,
            'request_headers' => $headers,
            'request_body' => $body,
            'last_error' => $e->getMessage() ?: get_class($e),
            'attempts' => $this->attempt,
            'failed_at' => now(),
        ]);

        Log::error('Delivery exhausted retry budget; moved to DLQ', [
            'event_id' => $this->event->id,
            'tenant_id' => $tenant->id,
            'target_url' => $url,
            'attempts' => $this->attempt,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Exponential backoff strategy used by the queue worker.
     * @return array<int>
     */
    public function backoff(): array
    {
        $base = config('services.webhook.outbound.base_delay_seconds', 5);
        return [
            $base,
            $base * 2,
            $base * 4,
            $base * 8,
            $base * 16,
        ];
    }
}
