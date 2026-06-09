<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    recentCampaigns: Array,
    eventsByType: Object,
});

const statusColor = (status) => ({
    draft: 'bg-gray-100 text-gray-600',
    scheduled: 'bg-blue-100 text-blue-700',
    running: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
    finished: 'bg-purple-100 text-purple-700',
    cancelled: 'bg-red-100 text-red-700',
    dry_run: 'bg-orange-100 text-orange-700',
}[status] ?? 'bg-gray-100 text-gray-600');
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Stats grid -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div v-for="(val, key) in {
                        'Total Campaigns': stats.total_campaigns,
                        'Active': stats.active_campaigns,
                        'Target Users': stats.total_targets,
                        'Total Events': stats.total_events,
                    }" :key="key"
                        class="bg-white rounded-xl shadow-sm p-6 text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ val }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ key }}</div>
                    </div>
                </div>

                <!-- Events summary -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Events (last 30 days)</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div v-for="(cnt, type) in eventsByType" :key="type"
                            class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="font-bold text-gray-700 text-xl">{{ cnt }}</div>
                            <div class="text-xs text-gray-500 capitalize">{{ type.replace(/_/g,' ') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Recent campaigns -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Campaigns</h3>
                        <Link :href="route('campaigns.index')"
                            class="text-sm text-indigo-600 hover:underline">View all →</Link>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sent</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Open %</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Click %</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Submit %</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="c in recentCampaigns" :key="c.id">
                                <td class="px-6 py-4">
                                    <Link :href="route('campaigns.show', c.id)"
                                        class="font-medium text-indigo-600 hover:underline">{{ c.name }}</Link>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusColor(c.status)]">
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">{{ c.stats.sent }}/{{ c.stats.total }}</td>
                                <td class="px-6 py-4 text-right text-sm">{{ c.stats.open_rate }}%</td>
                                <td class="px-6 py-4 text-right text-sm">{{ c.stats.click_rate }}%</td>
                                <td class="px-6 py-4 text-right text-sm">{{ c.stats.submit_rate }}%</td>
                            </tr>
                            <tr v-if="!recentCampaigns.length">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No campaigns yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Quick actions -->
                <div class="flex gap-3 flex-wrap">
                    <Link :href="route('campaigns.create')"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                        + New Campaign
                    </Link>
                    <Link :href="route('email-templates.create')"
                        class="px-4 py-2 bg-white border text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        + Email Template
                    </Link>
                    <Link :href="route('landing-pages.create')"
                        class="px-4 py-2 bg-white border text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        + Landing Page
                    </Link>
                    <Link :href="route('target-users.index')"
                        class="px-4 py-2 bg-white border text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        Manage Users
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
