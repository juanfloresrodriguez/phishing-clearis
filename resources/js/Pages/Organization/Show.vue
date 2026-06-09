<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ org: Object });

const form = useForm({
    name: props.org.name,
    timezone: props.org.timezone,
    event_retention_days: props.org.event_retention_days,
    anonymize_after_retention: props.org.anonymize_after_retention,
    privacy_notice: props.org.privacy_notice ?? '',
});

const domainForm = useForm({ domain: '' });

const update = () => form.patch(route('organization.update'));
const addDomain = () => domainForm.post(route('organization.domains.add'), {
    onSuccess: () => domainForm.reset(),
});
const verifyDomain = (id) => router.post(route('organization.domains.verify', id));
const removeDomain = (id) => { if (confirm('Remove domain?')) router.delete(route('organization.domains.remove', id)); };
</script>

<template>
    <Head title="Organization" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Organization Settings</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- General settings -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">General</h3>
                    <form @submit.prevent="update" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
                            <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                            <input v-model="form.timezone" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="Europe/Madrid" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Event Retention (days, 0 = forever)</label>
                            <input v-model.number="form.event_retention_days" type="number" min="0" class="w-full border rounded-lg px-3 py-2" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.anonymize_after_retention" id="anon" class="rounded" />
                            <label for="anon" class="text-sm text-gray-700">Anonymize data after retention period</label>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="form.processing"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                Save
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Domains -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Verified Domains</h3>

                    <form @submit.prevent="addDomain" class="flex gap-2 mb-4">
                        <input v-model="domainForm.domain" type="text" placeholder="yourdomain.com"
                            class="flex-1 border rounded-lg px-3 py-2 text-sm" />
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                            Add Domain
                        </button>
                    </form>

                    <div v-if="org.domains.length" class="space-y-2">
                        <div v-for="d in org.domains" :key="d.id"
                            class="flex items-center justify-between p-3 border rounded-lg">
                            <div>
                                <span class="font-medium text-sm">{{ d.domain }}</span>
                                <span v-if="d.is_verified" class="ml-2 text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full">Verified</span>
                                <span v-else class="ml-2 text-xs px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full">Pending</span>
                                <div v-if="!d.is_verified && d.verification_token" class="text-xs text-gray-400 mt-1 font-mono">
                                    Add TXT: {{ d.verification_token }}
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button v-if="!d.is_verified" @click="verifyDomain(d.id)"
                                    class="text-xs text-blue-600 hover:text-blue-800">Verify</button>
                                <button @click="removeDomain(d.id)"
                                    class="text-xs text-red-600 hover:text-red-800">Remove</button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-400 text-sm">No domains added yet.</p>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
