<script setup>
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const page = usePage();
const stats = ref(page.props.stats || {});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <span class="font-semibold text-xl tracking-tight">{{ $page.props.appName }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="/dashboard" class="text-sm font-medium text-gray-900">Dashboard</a>
                        <a href="/dlq" class="text-sm font-medium text-gray-900">Dead-Letter Queue</a>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">Webhook Relay</h1>
                <p class="mt-2 text-gray-600">
                    Multi-tenant webhook ingestion with declarative JSON-schema plugin transforms and a
                    retry-backed dead-letter queue.
                </p>
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Stat cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500">Ingested Events</dt>
                            <dd class="text-2xl font-semibold text-gray-900">{{ stats.events || 0 }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500">Deliveries</dt>
                            <dd class="text-2xl font-semibold text-gray-900">{{ stats.deliveries || 0 }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500">Failed (DLQ)</dt>
                            <dd class="text-2xl font-semibold text-gray-900 text-orange-600">{{ stats.dlq || 0 }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500">Tenants</dt>
                            <dd class="text-2xl font-semibold text-gray-900">{{ stats.tenants || 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg border">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-medium text-gray-900">Quick Start</h2>
                    <p class="mt-1 text-sm text-gray-600">POST a webhook to your tenant endpoint:</p>
                </div>
                <div class="px-6 py-4">
<pre class="text-sm text-gray-700 bg-gray-100 p-4 rounded overflow-x-auto">curl -X POST http://localhost:8000/ingest/acme \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Signature: sha256=&lt;hmac&gt;" \
  -H "X-Webhook-Timestamp: &lt;unix_ts&gt;" \
  -H "Idempotency-Key: &lt;unique_key&gt;" \
  -d '{"id":"inv_123","amount":1999,"currency":"usd","status":"paid"}'</pre>
                </div>
            </div>
        </main>
    </div>
</template>