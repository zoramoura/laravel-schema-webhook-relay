# Architecture Decision Record (ADR)

This document records the key engineering trade-offs behind the Multi-Tenant Webhook Relay & JSON-Schema Plugin Engine.

---

## 1. Declarative JSON Manifests over Sandboxed JS/Lua Runtimes

**Decision:** Tenant customization is expressed as declarative JSON plugin manifests (event filters, JSON-schema payload validation, field mapping, outbound targets) evaluated by a deterministic rule engine — not by executing user-supplied JavaScript/Lua.

**Rationale:**
- **Safety:** No arbitrary code execution; untrusted tenants cannot compromise the host or other tenants.
- **Determinism:** Same manifest + same payload always yields the same transformation, making retries and replays idempotent and predictable.
- **Performance:** JSON evaluation is orders of magnitude faster than spinning up a JS/Lua isolate per webhook.
- **Coverage:** Covers ~90% of routing/transformation needs (rename, pick, default, filter, validate, route).

**Trade-off:** Complex logic (loops, conditionals beyond simple filters) is not expressible. Those cases are intentionally punted to a future "escape hatch" (signed, allow-listed transformations) rather than a general sandbox.

---

## 2. Inertia.js over a Separate API + SPA

**Decision:** The admin console is a Laravel monolith-SPA bridged by Inertia.js + Vue 3 (Composition API) instead of a decoupled REST API and separate frontend repo.

**Rationale:**
- **Shared validation & policies:** Laravel Form Request rules and authorization policies are reused directly on the page — no duplicated API schemas.
- **Velocity:** No REST boilerplate, CORS, or separate auth tokens; server-side controllers return Inertia responses rendered client-side.
- **Operational simplicity:** One deployable artifact, one database, one test suite.

**Trade-off:** The frontend is coupled to the Laravel backend. For teams wanting fully independent frontend deploys, a separate SPA would be preferable — but that was not a requirement here.

---

## 3. Asynchronous Queue + Horizon over Synchronous Outbound Delivery

**Decision:** Outbound webhook dispatch happens via Laravel Queue + Horizon workers with exponential backoff, never inline during ingestion.

**Rationale:**
- **Ingestion latency:** Third-party endpoints are slow/unreliable; blocking the ingestion request would cause timeouts and cascading failures.
- **Reliability:** Exponential backoff + retry budget turns transient failures into eventual successes instead of dropped webhooks.
- **Observability:** Horizon provides queue depth, job failure, and retry visibility out of the box.

**Trade-off:** Eventual consistency — an ingested event is not guaranteed delivered at HTTP 202 time. DLQ replay covers the residual failures.

---

## 4. Dead-Letter Queue (DLQ) Table over Discarding Failures

**Decision:** Deliveries that exhaust their retry budget are persisted to a queryable `dead_letter_deliveries` table with one-click / bulk replay via Inertia controllers.

**Rationale:**
- **No dropped webhooks:** Failed deliveries are recoverable rather than lost.
- **Operability:** Operators can inspect, filter, and replay failures without touching code or the database directly.
- **Auditability:** Every failure records request/response, error, attempts, and timestamps.

**Trade-off:** Additional storage and a replay UI to maintain; accepted as a core reliability requirement.

---

## 5. MySQL (default) with PostgreSQL Support via `.env`

**Decision:** `docker-compose.yml` defaults to MySQL 8.0, but `config/database.php` supports PostgreSQL by switching `DB_CONNECTION=pgsql` and swapping the image.

**Rationale:** MySQL is the most common Laravel local default with strong operational tooling; PostgreSQL support is retained for teams with existing Postgres infrastructure (idempotency uses standard `UNIQUE` / `ON CONFLICT`-compatible patterns where practical).
