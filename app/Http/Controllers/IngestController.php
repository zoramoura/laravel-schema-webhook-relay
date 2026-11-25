<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngestWebhookRequest;
use App\Jobs\ProcessOutboundWebhook;
use App\Models\DeadLetterDelivery;
use App\Models\IdempotencyKey;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use App\Services\HmacVerifier;
use App\Services\IdempotencyStore;
use App\Services\ReplayProtector;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Public webhook ingestion gateway.
 *
 * Security controls (enforced before any work is enqueued):
 *  1. HMAC-SHA256 signature verification against the tenant secret.
 *  2. Timestamp replay protection (X-Webhook-Timestamp within window).
 *  3. Idempotency key deduplication.
 *
 * On success the event is persisted and the outbound dispatch job is enqueued.
 */
class IngestController extends Controller
{
    public function __construct(
        private HmacVerifier $hmacVerifier,
        private ReplayProtector $replayProtector,
        private IdempotencyStore $idempotencyStore,
    ) {
        //
    }

    public function store(IngestWebhookRequest $request, string $tenant_slug): JsonResponse
    {
        $tenant = Tenant::where('slug', $tenant_slug)
            ->where('is_active', true)
            ->first();

        if (! $tenant) {
            return response()->json([
                'error' => 'tenant_not_found_or_inactive',
                'message' => "Tenant '{$tenant_slug}' is not found or inactive.",
            ], 404);
        }

        $rawBody = $request->getContent();
        $signature = $request->header('X-Webhook-Signature', '');
        $timestamp = $request->header('X-Webhook-Timestamp', '');
        $idempotencyKey = $request->header('Idempotency-Key', '');
        $eventType = $request->header('X-Webhook-Event', '');

        // 1. HMAC-SHA256 signature verification.
        if (! $this->hmacVerifier->verify($tenant, $rawBody, $signature)) {
            return response()->json([
                'error' => 'invalid_signature',
                'message' => 'HMAC-SHA256 signature verification failed.',
            ], 401);
        }

        // 2. Replay protection.
        if (! $this->replayProtector->isValid($timestamp)) {
            return response()->json([
                'error' => 'replay_detected',
                'message' => 'Timestamp outside accepted replay window.',
            ], 401);
        }

        // 3. Idempotency deduplication.
        if (! empty($idempotencyKey) && $this->idempotencyStore->exists($tenant, $idempotencyKey)) {
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Event already processed for this idempotency key.',
            ], 202);
        }

        // Persist the inbound event.
        $event = WebhookEvent::create([
            'tenant_id' => $tenant->id,
            'idempotency_key' => $idempotencyKey ?: null,
            'event_type' => $eventType,
            'payload' => $request->validated('payload') ?? json_decode($rawBody, true) ?? [],
            'headers' => $request->headers->all(),
            'ip_address' => $request->ip(),
            'status' => 'received',
        ]);

        // Record idempotency key so repeats are deduplicated.
        if (! empty($idempotencyKey)) {
            $this->idempotencyStore->record($tenant, $idempotencyKey, $event);
        }

        // Enqueue outbound dispatch (async; never blocks ingestion).
        ProcessOutboundWebhook::dispatch($event)
            ->onQueue('webhook-relay');

        return response()->json([
            'status' => 'accepted',
            'event_id' => $event->id,
            'message' => 'Webhook accepted and queued for delivery.',
        ], 202);
    }
}