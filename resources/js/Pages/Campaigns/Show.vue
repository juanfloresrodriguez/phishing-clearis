<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    campaign: Object,
    stats: Object,
    eventTimeline: Array,
    departmentStats: Array,
});

const statusColor = (s) => ({
    draft: 'bg-gray-100 text-gray-600',
    scheduled: 'bg-blue-100 text-blue-700',
    running: 'bg-green-100 text-green-700',
    paused: 'bg-yellow-100 text-yellow-700',
    finished: 'bg-purple-100 text-purple-700',
    cancelled: 'bg-red-100 text-red-700',
    dry_run: 'bg-orange-100 text-orange-700',
}[s] ?? 'bg-gray-100 text-gray-600');

const launch = () => router.post(route('campaigns.launch', props.campaign.id));
const pause  = () => router.post(route('campaigns.pause', props.campaign.id));
const cancel = () => { if (confirm('Cancel?')) router.post(route('campaigns.cancel', props.campaign.id)); };
const dryRun = () => router.post(route('campaigns.dry-run', props.campaign.id));

const riskColor = (rate) => rate > 30 ? 'text-red-600' : rate > 15 ? 'text-yellow-600' : 'text-green-600';

const deptRows = computed(() => {
    const map = {};
    props.departmentStats.forEach(r => {
        if (!map[r.department]) map[r.department] = { department: r.department, opened: 0, clicked: 0, submitted: 0 };
        if (r.event_type === 'email_opened') map[r.department].opened = r.cnt;
        if (r.event_type === 'link_clicked') map[r.department].clicked = r.cnt;
        if (r.event_type === 'form_submitted') map[r.department].submitted = r.cnt;
    });
    return Object.values(map);
});
</script>

<template>
    <Head :title="campaign.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-semibold text-gray-800">{{ campaign.name }}</h2>
                        <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusColor(campaign.status)]">
                            {{ campaign.status }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-400 mt-1">{{ campaign.email_template?.name }} · {{ campaign.sending_profile?.from_email }}</p>
                </div>
                <div class="flex gap-2">
                    <button v-if="['draft','scheduled','paused'].includes(campaign.status)"
                        @click="launch" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">Launch</button>
                    <button v-if="campaign.status === 'running'"
                        @click="pause" class="px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm hover:bg-yellow-600">Pause</button>
                    <button v-if="campaign.status === 'draft'"
                        @click="dryRun" class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600">Dry Run</button>
                    <button v-if="['running','scheduled','paused'].includes(campaign.status)"
                        @click="cancel" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Cancel</button>
                    <a :href="route('campaigns.export', campaign.id)"
                        class="px-4 py-2 bg-white border text-gray-700 rounded-lg text-sm hover:bg-gray-50">Export CSV</a>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Key metrics -->
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-gray-700">{{ stats.total }}</div>
                        <div class="text-xs text-gray-400 mt-1">Total</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ stats.sent }}</div>
                        <div class="text-xs text-gray-400 mt-1">Sent</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.opened }}</div>
                        <div class="text-xs text-gray-400 mt-1">Opened ({{ stats.open_rate }}%)</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ stats.clicked }}</div>
                        <div class="text-xs text-gray-400 mt-1">Clicked ({{ stats.click_rate }}%)</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                        <div :class="['text-2xl font-bold', riskColor(stats.submit_rate)]">{{ stats.form_submitted }}</div>
                        <div class="text-xs text-gray-400 mt-1">Submitted ({{ stats.submit_rate }}%)</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.reported }}</div>
                        <div class="text-xs text-gray-400 mt-1">Reported ✓</div>
                    </div>
                </div>

                <!-- Event timeline chart (simple table) -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Event Timeline</h3>
                    <div v-if="eventTimeline.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500 border-b">
                                    <th class="pb-2 pr-4">Date</th>
                                    <th class="pb-2 pr-4">Event</th>
                                    <th class="pb-2 text-right">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="e in eventTimeline" :key="e.date+e.event_type" class="border-b last:border-0">
                                    <td class="py-1 pr-4 text-gray-500">{{ e.date }}</td>
                                    <td class="py-1 pr-4 capitalize">{{ e.event_type.replace(/_/g,' ') }}</td>
                                    <td class="py-1 text-right font-medium">{{ e.cnt }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-gray-400 text-sm">No events recorded yet.</p>
                </div>

                <!-- Department risk table -->
                <div v-if="deptRows.length" class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Department Risk</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 border-b">
                                <th class="pb-2 pr-4">Department</th>
                                <th class="pb-2 pr-4 text-right">Opened</th>
                                <th class="pb-2 pr-4 text-right">Clicked</th>
                                <th class="pb-2 text-right">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="d in deptRows" :key="d.department" class="border-b last:border-0">
                                <td class="py-2 pr-4">{{ d.department || '(no dept)' }}</td>
                                <td class="py-2 pr-4 text-right text-blue-600">{{ d.opened }}</td>
                                <td class="py-2 pr-4 text-right text-orange-600">{{ d.clicked }}</td>
                                <td class="py-2 text-right font-bold text-red-600">{{ d.submitted }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Campaign details -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Configuration</h3>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-400">Start</dt><dd>{{ campaign.scheduled_start_at ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400">End</dt><dd>{{ campaign.scheduled_end_at ?? '—' }}</dd></div>
                        <div><dt class="text-gray-400">Window</dt><dd>{{ campaign.send_window_start }} – {{ campaign.send_window_end }}</dd></div>
                        <div><dt class="text-gray-400">Rate limit</dt><dd>{{ campaign.rate_limit_per_minute }} emails/min</dd></div>
                        <div><dt class="text-gray-400">Work hours</dt><dd>{{ campaign.respect_work_hours ? 'Yes' : 'No' }}</dd></div>
                        <div><dt class="text-gray-400">Skip weekends</dt><dd>{{ campaign.respect_work_days ? 'Yes' : 'No' }}</dd></div>
                    </dl>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
