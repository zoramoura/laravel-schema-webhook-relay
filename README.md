![CI Status](https://github.com/spz/laravel-schema-webhook-relay/actions/workflows/ci.yml/badge.svg)

# Multi-Tenant Webhook Relay & JSON-Schema Plugin Engine

Reliable webhook ingestion with tenant-scoped HMAC verification, replay protection, idempotency, declarative JSON-schema transforms, exponential-backoff delivery, and a queryable dead-letter queue — all in a Laravel 11 + Inertia.js + Vue 3 monolith-SPA.

## The problem it solves

Third-party webhooks are unreliable and insecure by default:

- **Dropped webhooks** — a transient outage silences events forever.
- **Unverified ingestion** — spoofed or tampered payloads trigger downstream actions.
- **Duplicate deliveries** — webhook providers retry, causing double charges or double emails.
- **Brittle plugin code** — custom per-tail logic in sandboxed runtimes is a security and maintenance liability.

This platform prevents those failure modes at the boundary, before any work is enqueued.

## System overview

```text
       Webhook Provider
            |
      HMAC + Replay + Idempotency
            |
       /ingest/{tenant_slug}
            |
     WebhookEvent (persisted)
            |
      Queue (Redis + Horizon)
            |
   Outbound Job  ──► exponential backoff ──► DLQ ← replay
   (manifest: filter → validate → map → route)
```

## One-command spin-up

```bash
git clone https://github.com/spz/laravel-schema-webhook-relay.git
cd laravel-schema-webhook-relay
cp .env.example .env
docker-compose up --build
```

Then:

- App: `http://localhost:8000`
- Health: `http://localhost:8000/health`
- Horizon: `http://localhost:8000/horizon`
- Admin (DLQ inspector): `http://localhost:8000/dlq`

`docker-compose up` starts PHP-FPM, Nginx, MySQL, Redis, the queue worker, and Horizon, then runs migrations + seeders automatically.

## Security controls (ingestion gateway)

Every `POST /ingest/{tenant_slug}` enforces, in order:

1. **HMAC-SHA256 signature verification** against the tenant's secret (`hash_equals`, constant-time).
2. **Replay protection** — `X-Webhook-Timestamp` must be within the configured window.
3. **Idempotency key deduplication** — repeated `Idempotency-Key` values are acknowledged without duplicate work.

## Declarative plugin engine

Tenants upload JSON manifests (no code execution):

```json
{
  "name": "acme-invoice-routing",
  "event_filter": { "type": "invoice.payment_succeeded" },
  "payload_schema": { "type": "object", "required": ["id","amount"] },
  "field_mapping": { "invoice_id": "$.id", "amount_cents": "$.amount" },
  "outbound_targets": [{ "url": "https://...", "method": "POST" }]
}
```

Pipeline: **event filter → JSON-schema validation → field mapping → outbound routing**.

## Retry & Dead-Letter Queue

Outbound deliveries run asynchronously with exponential backoff (`base × 2^(attempt−1)`). Exhausted deliveries are persisted to `dead_letter_deliveries` and replayable one-by-one or in bulk from the admin UI.

## Testing

```bash
composer install
vendor/bin/pest
```

The suite verifies real database behavior: HMAC tampering (401), malformed signatures (401), replay-window rejection (401), idempotency deduplication, DLQ persistence, and manual/bulk replay. No mocks hide infrastructure interactions.

## CI

GitHub Actions runs linting, the Pest suite, and a container build on every push. See `.github/workflows/ci.yml`.

## Architecture decisions

See [`DECISIONS.md`](DECISIONS.md) for the recorded trade-offs, including declarative JSON manifests over sandboxed JS/Lua runtimes and Inertia.js over a separate API + SPA.

## Security

Never commit real credentials. `.env.example` documents only safe local defaults. All secrets (`APP_KEY`, `DB_PASSWORD`, tenant HMAC secrets) must be injected at runtime.
