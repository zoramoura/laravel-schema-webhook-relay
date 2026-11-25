<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const deliveries = ref(page.props.deliveries?.data || []);

const replay = async (id) => {
    if (!confirm('Replay this DLQ delivery?')) return;
    await window.axios.post(`/dlq/${id}/replay`);
    window.location.reload();
};

const bulkReplay = async () => {
    const ids = deliveries.value.filter(d => d.selected).map(d => d.id);
    if (!ids.length || !confirm(`Replay ${ids.length} delivery(ies)?`)) return;
    await window.axios.post('/dlq/bulk-replay', { ids });
    window.location.reload();
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <button @click="$inertia.get('/dashboard')" class="text-gray-400 hover:text-gray-600">
                                ←
                            </button>
                            <span class="font-semibold text-xl tracking-tight">{{ $page.props.appName }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="/dlq" class="text-sm font-medium text-gray-900">Dead-Letter Queue</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dead-Letter Queue</h1>
                    <p class="text-gray-600">Deliveries that exhausted their retry budget. Replay to attempt again.</p>
                </div>
                <button
                    @click="bulkReplay"
                    :disabled="!deliveries.value.some(d => d.selected)"
                    class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 disabled:opacity-50"
                >
                    Replay Selected
                </button>
            </div>

            <div class="bg-white shadow rounded-lg border overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 w-4">
                                <input type="checkbox" class="rounded" @change="deliveries.value.forEach(d => d.selected = $event.target.checked)" />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Target</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Attempts</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Failed At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Last Error</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="delivery in deliveries" :key="delivery.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="rounded" v-model="delivery.selected" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ delivery.target_url }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ delivery.tenant?.slug }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ delivery.attempts }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ delivery.failed_at }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 truncate max-w-xs">{{ delivery.last_error }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="replay(delivery.id)" class="text-orange-600 hover:text-orange-900">Replay</button>
                            </td>
                        </tr>
                        <tr v-if="deliveries.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No dead-letter deliveries. 🎉
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="px-6 py-3 bg-gray-50 border-t">
                    <span class="text-sm text-gray-600">{{ deliveries.length }} delivery(ies) listed.</span>
                </div>
            </div>
        </div>
    </div>
</template>