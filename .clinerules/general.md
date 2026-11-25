Cline Environment, Guidelines, and Requirements: Multi-Tenant Webhook Relay & JSON-Schema Plugin Engine

This document defines the runtime environment, architectural blueprint, engineering standards, and delivery requirements for the Multi-Tenant Webhook Relay & JSON-Schema Plugin Engine. Positioned as one of two primary full-stack anchors in the engineering portfolio (alongside a Node.js/Express and React build), this Laravel 11 monolith-SPA platform is designed to ingest third-party webhooks reliably and allow tenants to customize event routing and payload transformations through declarative JSON manifests without maintaining brittle custom code or bloated plugin runtimes.

1. Environment and Containerized Runtime Stack

The application operates as a unified monolith-SPA built on PHP 8.3+ and Laravel 11, using Inertia.js to bridge server-side controllers directly to a Vue 3 (Composition API) frontend. By coupling Laravel's server-side routing, validation, and authorization policies with Inertia.js, the stack eliminates REST API boilerplate while preserving single-page application responsiveness.

To guarantee a reproducible, one-command local spin-up in under 30 seconds (targeting a 10-second startup with seeded sample data), the entire environment must be orchestrated via docker-compose.yml. The table below summarizes the required runtime components and their designated responsibilities across the system.

Layer	Technology	Role in System
Backend Runtime	PHP 8.3+ & Laravel 11	Core HTTP ingestion, Form Request validation, plugin rule evaluation, and Inertia controllers
Frontend & Bridge	Inertia.js & Vue 3 (Composition API)	Monolith-SPA page transitions, JSON-schema dynamic form builder, and live payload/DLQ inspector
Primary Database	MySQL or PostgreSQL	Persistence for tenant configurations, plugin manifests, delivery logs, and dead-letter records
Queue & Worker Engine	Redis & Laravel Horizon	Asynchronous outbound webhook dispatch, exponential backoff retries, and queue monitoring
Web & App Containers	Nginx & PHP-FPM	Containerized request handling launched automatically alongside DB, Redis, and Horizon via Docker Compose
Testing & CI	Pest / PHPUnit & GitHub Actions	Automated linting, unit/integration verification against real failure modes, and container builds

Before writing any application logic, the local container topology must be verified so that running docker-compose up launches PHP-FPM, Nginx, MySQL or PostgreSQL, Redis, and the Laravel Horizon worker automatically with seeded sample data.

2. Core System Modules and Functional Requirements

The platform is organized into four tightly integrated modules that handle webhook ingestion, declarative transformation, fault-tolerant delivery, and tenant administration. Each module enforces strict boundaries so that untrusted external payloads cannot compromise system stability or tenant isolation.

2.1 Ingestion and Security Gateway

External webhooks enter the system through public, tenant-scoped /ingest/{tenant_slug} endpoints. Because webhook providers frequently retry deliveries on network timeouts or face spoofed requests, the gateway must enforce three security and reliability controls at the boundary before enqueuing any work:

1. HMAC-SHA256 Signature Verification: Validate incoming request signatures against the tenant's configured secret to reject tampered or unauthorized payloads.
2. Replay Protection: Enforce timestamp header checks to block stale or replayed webhook requests outside the accepted window.
3. Idempotency Key Deduplication: Track and deduplicate incoming events using idempotency keys so duplicate deliveries are safely acknowledged without triggering duplicate downstream actions.

2.2 Declarative JSON-Schema Plugin Engine

Rather than executing arbitrary user-supplied code, the platform evaluates user-uploaded declarative JSON plugin manifests. The manifest loader and rule evaluation engine must support four core capabilities:

* Event Filtering: Match incoming webhook events against tenant-defined criteria before running transformations.
* Payload Validation: Validate incoming event payloads against JSON-Schema rules defined inside the manifest.
* Dynamic Field Mapping: Transform and reshape validated payloads through a deterministic transformation pipeline.
* Outbound Target Routing: Route transformed payloads to the specified outbound webhook endpoints.

2.3 Retry Pipeline and Dead-Letter Queue (DLQ)

Outbound HTTP deliveries are inherently unreliable and must never block the ingestion request lifecycle. Laravel Queue and Horizon workers must dispatch outbound requests asynchronously using exponential backoff retry policies. When a delivery exhausts its retry budget, the failure must be persisted in a queryable dead-letter queue (DLQ) table. Administrators must be able to trigger one-click manual or bulk replays directly from the DLQ through Inertia controller actions.

2.4 Dynamic Vue 3 and Inertia Admin Console

The administrative interface provides tenants and operators with visibility and control over plugin workflows without building a separate REST API. Built with Vue 3 (Composition API) and Inertia.js, the console must deliver two primary interfaces:

* JSON-Schema Dynamic Form Builder: Automatically render plugin configuration forms directly from JSON schemas while sharing Laravel server-side validation rules and authorization policies.
* Live Payload and DLQ Inspector: Provide real-time inspection of incoming webhook payloads, headers, outbound responses, and failed deliveries queued in the DLQ.

Across all four modules, implementation must adhere to consistent cross-cutting engineering, security, and observability standards.

3. Engineering, Security, and Observability Standards

All code generated within this repository must adhere to the portfolio's cross-project engineering standards, prioritizing pragmatic minimalism, boundary safety, and operational visibility over heavy third-party abstractions.

* Pragmatic Minimalism: Prefer standard library primitives, lightweight JSON schemas, and native SQL features before introducing external packages or complex abstractions.
* Strict Boundary Validation: Validate all external inputs at system boundaries using Laravel Form Requests for HTTP/Inertia actions and JSON Schema for webhook payloads and plugin manifests.
* Idempotency and Safe Retries: Ensure all state-mutating endpoints and background Horizon jobs tolerate duplicate deliveries safely through idempotency keys and exponential backoff.
* Structured Logging and Health Checks: Emit structured JSON logs across backend services and expose a /health endpoint that actively verifies database and queue connectivity.
* Zero Hardcoded Secrets: Never commit credentials or API keys. Maintain a documented .env.example with safe local defaults and enforce environment validation and secret scanning on startup.

Every standard above must be backed by automated integration verification rather than assumptions or superficial mocks.

4. Testing Strategy and Definition of Done

Testing must prioritize real integration behavior and production failure modes over mocked unit tests that obscure database or queue interactions. The Pest (or PHPUnit) test suite must verify the system against real database behavior and explicit failure scenarios before any milestone is considered complete.

Specifically, the verification suite and Definition of Done require passing coverage across the following scenarios:

1. Security & Deduplication: Rejecting requests with tampered or malformed HMAC-SHA256 signatures and safely deduplicating repeated requests sharing the same idempotency key.
2. Retry & DLQ Lifecycle: Verifying exponential backoff state transitions under simulated network timeouts or failed outbound HTTP deliveries, and confirming successful manual or bulk DLQ replay workflows.
3. One-Command Environment Verification: Confirming docker-compose up starts PHP-FPM, Nginx, MySQL/PostgreSQL, Redis, and Horizon cleanly with seeded sample data.
4. Automated CI Pipeline: Running a fast GitHub Actions workflow covering code linting, the Pest/PHPUnit test suite, and container image builds, surfaced via a passing status badge in README.md.

5. Architectural Trade-Offs and Repository Packaging

To communicate engineering judgment clearly to technical evaluators, the repository must include concise architectural documentation and problem-first presentation materials.

5.1 Architecture Decision Record (DECISIONS.md)

Every repository requires a concise DECISIONS.md documenting three to five key engineering trade-offs. For this Laravel project, two foundational decisions must be explicitly recorded:

Declarative JSON Manifests over Sandboxed JS/Lua Runtimes: Choosing declarative JSON manifests keeps plugin execution deterministic, fast, and immune to arbitrary code execution vulnerabilities while covering 90% of routing and transformation needs.

Inertia.js over Separate API + SPA: Pairing Laravel with Inertia.js accelerates full-stack delivery by sharing server-side validation rules and authorization policies directly with Vue 3 pages.

5.2 Problem-First README.md

Frame README.md around the production failure modes the platform prevents—such as dropped webhooks, unverified ingestion payloads, and brittle custom plugin code—rather than leading with a list of frameworks. Place the GitHub Actions CI status badge at the top of README.md, followed directly below the project summary by visual proof of the working system (such as a crisp screenshot, a vhs or asciinema terminal recording, or an architecture diagram) so evaluators can grasp the system within 10 seconds.

6. Two-Week Sprint Cadence and Scope Freeze Rules

Development is structured as a disciplined two-week sprint (Weeks 3–4 of the master calendar, spanning December 14 to December 27, 2026). To prevent scope creep from jeopardizing tests or documentation, feature scope freezes on Day 4. If schedule pressure arises, secondary UI polish must be cut before cutting tests, error handling, or documentation.

Sprint Window	Calendar Dates	Core Deliverables
Days 1–3	Mon Dec 14 – Wed Dec 16	Laravel 11 + Inertia + Vue 3 setup, Docker Compose (MySQL/PostgreSQL + Redis), core database schema, /ingest/{tenant_slug} endpoints, HMAC-SHA256 verification, and idempotency deduplication
Days 4–6	Thu Dec 17 – Sat Dec 19	Day 4 Scope Freeze; declarative JSON-schema plugin manifest loader, rule evaluation engine, and dynamic payload transformation pipeline
Midpoint Buffer	Sun Dec 20	Catch-up and midpoint architecture verification
Days 7–10	Mon Dec 21 – Thu Dec 24	Laravel Queue/Horizon workers with exponential backoff retry policies, DLQ storage, and Inertia controllers for manual/bulk replay workflows
Days 11–14	Fri Dec 25 – Sun Dec 27	Vue 3 + Inertia UI (JSON-schema dynamic form builder and live payload/DLQ inspector), Pest test suite, DECISIONS.md ADR, README.md polish, and final Docker packaging
