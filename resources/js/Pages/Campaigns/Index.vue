<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ campaigns: Object });

const statusColor = (s) => ({
    draft: 'bg-gray-100 text-gray-600',
    scheduled: 'bg-blue-100 text-blue-700',
    running: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
    finished: 'bg-purple-100 text-purple-700',
    cancelled: 'bg-red-100 text-red-700',
    dry_run: 'bg-orange-100 text-orange-700',
}[s] ?? 'bg-gray-100 text-gray-600');

const launch = (id) => router.post(route('campaigns.launch', id));
const pause = (id) => router.post(route('campaigns.pause', id));
const cancel = (id) => { if (confirm('Cancel campaign?')) router.post(route('campaigns.cancel', id)); };
</script>

<template>
    <Head title="Campaigns" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Campaigns</h2>
                <Link :href="route('campaigns.create')"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + New Campaign
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sent/Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Open %</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Click %</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Submit %</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="c in campaigns.data" :key="c.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <Link :href="route('campaigns.show', c.id)"
                                        class="font-medium text-indigo-600 hover:underline">{{ c.name }}</Link>
                                    <div class="text-xs text-gray-400">{{ c.email_template?.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusColor(c.status)]">
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ c.stats.sent }}/{{ c.stats.total }}</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ c.stats.open_rate }}%</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ c.stats.click_rate }}%</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ c.stats.submit_rate }}%</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <Link :href="route('campaigns.show', c.id)"
                                        class="text-xs text-gray-500 hover:text-gray-700">View</Link>
                                    <button v-if="c.status === 'draft' || c.status === 'scheduled' || c.status === 'paused'"
                                        @click="launch(c.id)"
                                        class="text-xs text-green-600 hover:text-green-800">Launch</button>
                                    <button v-if="c.status === 'running'"
                                        @click="pause(c.id)"
                                        class="text-xs text-yellow-600 hover:text-yellow-800">Pause</button>
                                    <button v-if="['running','scheduled','paused'].includes(c.status)"
                                        @click="cancel(c.id)"
                                        class="text-xs text-red-600 hover:text-red-800">Cancel</button>
                                    <a :href="route('campaigns.export', c.id)"
                                        class="text-xs text-indigo-600 hover:text-indigo-800">Export CSV</a>
                                </td>
                            </tr>
                            <tr v-if="!campaigns.data.length">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    No campaigns yet. <Link :href="route('campaigns.create')" class="text-indigo-600 hover:underline">Create one →</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="campaigns.last_page > 1" class="mt-4 flex justify-center gap-2">
                    <Link v-for="link in campaigns.links" :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['px-3 py-1 rounded text-sm', link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50']" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
