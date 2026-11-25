<?php

use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Security & Deduplication verification against a real database.
 *
 * Covers:
 *  - Rejecting tampered / malformed HMAC-SHA256 signatures (401)
 *  - Rejecting timestamps outside the replay window (401)
 *  - Deduplicating repeated requests sharing the same idempotency key (202, single enqueue)
 */
it('rejects webhooks with a tampered HMAC signature', function () {
    $tenant = Tenant::create([
        'slug' => 'tamper-tenant',
        'name' => 'Tamper Tenant',
        'hmac_secret' => 'super-secret',
        'is_active' => true,
    ]);

    $body = json_encode(['id' => 'inv_1', 'amount' => 100]);
    $goodSignature = hash_hmac('sha256', $body, $tenant->hmac_secret);
    $tamperedSignature = hash_hmac('sha256', $body, 'wrong-secret');

    $response = postJson("/ingest/{$tenant->slug}", [], [
        'Content-Type' => 'application/json',
        'X-Webhook-Signature' => 'sha256=' . $tamperedSignature,
        'X-Webhook-Timestamp' => now()->timestamp,
        'Idempotency-Key' => 'idem-tamper-1',
    ], $body);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'invalid_signature']);

    // No event should have been persisted.
    expect(WebhookEvent::where('tenant_id', $tenant->id)->count())->toBe(0);
});

it('rejects webhooks with a malformed HMAC signature', function () {
    $tenant = Tenant::create([
        'slug' => 'malformed-tenant',
        'name' => 'Malformed Tenant',
        'hmac_secret' => 'super-secret',
        'is_active' => true,
    ]);

    $body = json_encode(['id' => 'inv_2', 'amount' => 200]);

    $response = postJson("/ingest/{$tenant->slug}", [], [
        'Content-Type' => 'application/json',
        'X-Webhook-Signature' => 'not-hex-at-all!!!',
        'X-Webhook-Timestamp' => now()->timestamp,
        'Idempotency-Key' => 'idem-malformed-1',
    ], $body);

    $response->assertStatus(401);
    expect(WebhookEvent::where('tenant_id', $tenant->id)->count())->toBe(0);
});

it('rejects webhooks with a timestamp outside the replay window', function () {
    $tenant = Tenant::create([
        'slug' => 'replay-tenant',
        'name' => 'Replay Tenant',
        'hmac_secret' => 'super-secret',
        'is_active' => true,
    ]);

    $body = json_encode(['id' => 'inv_3', 'amount' => 300]);
    $signature = hash_hmac('sha256', $body, $tenant->hmac_secret);

    // Timestamp 10 minutes in the past (beyond default 300s window).
    $staleTimestamp = now()->subMinutes(10)->timestamp;

    $response = postJson("/ingest/{$tenant->slug}", [], [
        'Content-Type' => 'application/json',
        'X-Webhook-Signature' => 'sha256=' . $signature,
        'X-Webhook-Timestamp' => (string) $staleTimestamp,
        'Idempotency-Key' => 'idem-replay-1',
    ], $body);

    $response->assertStatus(401);
    $response->assertJson(['error' => 'replay_detected']);
    expect(WebhookEvent::where('tenant_id', $tenant->id)->count())->toBe(0);
});

it('accepts a valid webhook and persists the event', function () {
    $tenant = Tenant::create([
        'slug' => 'valid-tenant',
        'name' => 'Valid Tenant',
        'hmac_secret' => 'super-secret',
        'is_active' => true,
    ]);

    $body = json_encode(['id' => 'inv_4', 'amount' => 400, 'currency' => 'usd']);
    $signature = hash_hmac('sha256', $body, $tenant->hmac_secret);

    $response = postJson("/ingest/{$tenant->slug}", [], [
        'Content-Type' => 'application/json',
        'X-Webhook-Signature' => 'sha256=' . $signature,
        'X-Webhook-Timestamp' => (string) now()->timestamp,
        'Idempotency-Key' => 'idem-valid-1',
    ], $body);

    $response->assertStatus(202);
    $response->assertJson(['status' => 'accepted']);

    expect(WebhookEvent::where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('deduplicates repeated requests sharing the same idempotency key', function () {
    $tenant = Tenant::create([
        'slug' => 'idem-tenant',
        'name' => 'Idem Tenant',
        'hmac_secret' => 'super-secret',
        'is_active' => true,
    ]);

    $body = json_encode(['id' => 'inv_5', 'amount' => 500]);
    $signature = hash_hmac('sha256', $body, $tenant->hmac_secret);
    $headers = [
        'Content-Type' => 'application/json',
        'X-Webhook-Signature' => 'sha256=' . $signature,
        'X-Webhook-Timestamp' => (string) now()->timestamp,
        'Idempotency-Key' => 'idem-valid-2',
    ];

    // First delivery -> accepted and enqueued.
    $first = postJson("/ingest/{$tenant->slug}", [], $headers, $body);
    $first->assertStatus(202);

    // Second delivery with same idempotency key -> deduplicated (202 duplicate).
    $second = postJson("/ingest/{$tenant->slug}", [], $headers, $body);
    $second->assertStatus(202);
    $second->assertJson(['status' => 'duplicate']);

    // Only a single event should persist.
    expect(WebhookEvent::where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('rejects webhooks for an inactive or unknown tenant', function () {
    // Unknown tenant.
    $body = json_encode(['id' => 'inv_6']);
    $signature = hash_hmac('sha256', $body, 'anything');

    $response = postJson('/ingest/does-not-exist', [], [
        'Content-Type' => 'application/json',
        'X-Webhook-Signature' => 'sha256=' . $signature,
        'X-Webhook-Timestamp' => (string) now()->timestamp,
        'Idempotency-Key' => 'idem-unknown-1',
    ], $body);

    $response->assertStatus(404);
});
