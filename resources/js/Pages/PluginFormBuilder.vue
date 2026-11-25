<script setup>
import { ref } from 'vue';

const schema = ref({
    type: 'object',
    required: ['id', 'amount'],
    properties: {
        id: { type: 'string', title: 'Invoice ID' },
        amount: { type: 'number', title: 'Amount (cents)' },
        currency: { type: 'string', title: 'Currency' },
        status: { type: 'string', title: 'Status' },
    },
});

const formData = ref({});
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

        <div class="max-w-2xl mx-auto py-6 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">JSON-Schema Plugin Form Builder</h1>
            <p class="text-gray-600 mb-6">
                Plugin configuration forms are rendered declaratively from a JSON schema — no custom code required.
            </p>

            <div class="bg-white shadow rounded-lg border p-6">
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div v-for="(field, key) in schema.properties" :key="key" class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            {{ field.title || key }}
                            <span v-if="schema.required?.includes(key)" class="text-red-500 ml-1">*</span>
                        </label>
                        <input
                            v-model="formData[key]"
                            :type="field.type === 'number' ? 'number' : 'text'"
                            :placeholder="`Enter ${field.title || key}`"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                        <p class="text-xs text-gray-500">{{ field.type }} field</p>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Validate &amp; Map</button>
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Reset</button>
                    </div>
                </form>

                <div v-if="Object.keys(formData).length" class="mt-6 bg-white shadow rounded-lg border p-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-3">Mapped Output</h2>
                    <pre class="text-sm font-mono text-gray-700 bg-gray-50 p-4 rounded overflow-x-auto">{{ JSON.stringify(formData, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>