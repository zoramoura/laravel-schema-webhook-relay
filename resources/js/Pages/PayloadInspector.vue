<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const event = page.props.event || {};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <button @click="$inertia.get('/dashboard')" class="text-gray-400 hover:text-gray-600">←</button>
                            <span class="font-semibold text-xl tracking-tight">{{ $page.props.appName }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Payload Inspector</h1>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Meta card -->
                <div class="lg:col-span-1 bg-white shadow rounded-lg border p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Event</h2>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">ID</dt>
                            <dd class="font-mono text-gray-900">{{ event.id }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Tenant</dt>
                            <dd class="font-medium text-gray-900">{{ event.tenant?.slug }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Type</dt>
                            <dd class="font-medium text-gray-900">{{ event.event_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd class="font-medium text-gray-900 capitalize">{{ event.status }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">IP Address</dt>
                            <dd class="font-mono text-gray-900">{{ event.ip_address }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Request payload -->
                <div class="lg:col-span-2 bg-white shadow rounded-lg border p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Request Payload</h2>
                    <pre class="text-sm font-mono text-gray-700 bg-gray-50 p-4 rounded overflow-x-auto">{{ JSON.stringify(event.payload, null, 2) }}</pre>
                </div>

                <!-- Headers -->
                <div class="lg:col-span-2 bg-white shadow rounded-lg border p-6 mt-6 lg:mt-0">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Request Headers</h2>
                    <pre class="text-sm font-mono text-gray-700 bg-gray-50 p-4 rounded overflow-x-auto">{{ JSON.stringify(event.headers, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>