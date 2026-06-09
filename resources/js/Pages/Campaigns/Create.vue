<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    templates: Array,
    landingPages: Array,
    sendingProfiles: Array,
    groups: Array,
});

const form = useForm({
    name: '',
    subject: '',
    description: '',
    email_template_id: '',
    landing_page_id: '',
    sending_profile_id: '',
    group_ids: [],
    scheduled_start_at: '',
    scheduled_end_at: '',
    send_window_start: '09:00',
    send_window_end: '17:00',
    respect_work_hours: true,
    respect_work_days: true,
    rate_limit_per_minute: 10,
    language: 'es',
});

const submit = () => form.post(route('campaigns.store'));
</script>

<template>
    <Head title="New Campaign" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">New Campaign</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm p-8 space-y-6">

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name *</label>
                            <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Subject *</label>
                            <input v-model="form.subject" type="text" class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                            <p class="text-xs text-gray-400 mt-1">Supports: {{first_name}}, {{last_name}}, {{email}}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Template *</label>
                            <select v-model="form.email_template_id" class="w-full border rounded-lg px-3 py-2" required>
                                <option value="">Select template…</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Landing Page</label>
                            <select v-model="form.landing_page_id" class="w-full border rounded-lg px-3 py-2">
                                <option value="">None</option>
                                <option v-for="p in landingPages" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sending Profile *</label>
                            <select v-model="form.sending_profile_id" class="w-full border rounded-lg px-3 py-2" required>
                                <option value="">Select profile…</option>
                                <option v-for="p in sendingProfiles" :key="p.id" :value="p.id">{{ p.name }} ({{ p.from_email }})</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                            <select v-model="form.language" class="w-full border rounded-lg px-3 py-2">
                                <option value="es">Spanish</option>
                                <option value="en">English</option>
                                <option value="fr">French</option>
                                <option value="de">German</option>
                            </select>
                        </div>
                    </div>

                    <!-- Groups -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Groups *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label v-for="g in groups" :key="g.id" class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" :value="g.id" v-model="form.group_ids" class="rounded" />
                                <span class="text-sm">{{ g.name }} <span class="text-xs text-gray-400">({{ g.target_users_count }})</span></span>
                            </label>
                        </div>
                        <p v-if="form.errors.group_ids" class="text-red-500 text-xs mt-1">{{ form.errors.group_ids }}</p>
                    </div>

                    <!-- Scheduling -->
                    <fieldset class="border rounded-lg p-4">
                        <legend class="text-sm font-medium text-gray-700 px-2">Send Window</legend>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Start</label>
                                <input v-model="form.scheduled_start_at" type="datetime-local" class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">End</label>
                                <input v-model="form.scheduled_end_at" type="datetime-local" class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Daily window start</label>
                                <input v-model="form.send_window_start" type="time" class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Daily window end</label>
                                <input v-model="form.send_window_end" type="time" class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div class="flex gap-6 mt-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" v-model="form.respect_work_hours" class="rounded" />
                                Respect work hours
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" v-model="form.respect_work_days" class="rounded" />
                                Skip weekends
                            </label>
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs text-gray-500 mb-1">Rate limit (emails/minute)</label>
                            <input v-model.number="form.rate_limit_per_minute" type="number" min="1" max="500"
                                class="w-32 border rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </fieldset>

                    <div class="flex justify-end gap-3">
                        <a :href="route('campaigns.index')" class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : 'Create Campaign' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
