<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ template: Object });

const testForm = useForm({ to_email: '' });
const sendTest = () => testForm.post(route('email-templates.send-test', props.template.id), {
    onSuccess: () => testForm.reset(),
});
</script>

<template>
    <Head :title="template.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ template.name }}</h2>
                    <p class="text-sm text-gray-400">{{ template.subject }} · v{{ template.version }} · {{ template.category }}</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('email-templates.edit', template.id)"
                        class="px-4 py-2 bg-white border text-gray-700 rounded-lg text-sm hover:bg-gray-50">Edit</Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Send test -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Send Test Email</h3>
                    <form @submit.prevent="sendTest" class="flex gap-3">
                        <input v-model="testForm.to_email" type="email" placeholder="admin@yourcompany.com"
                            class="flex-1 border rounded-lg px-3 py-2 text-sm" required />
                        <button type="submit" :disabled="testForm.processing"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                            Send Test
                        </button>
                    </form>
                    <p v-if="testForm.errors.to_email" class="text-red-500 text-xs mt-1">{{ testForm.errors.to_email }}</p>
                </div>

                <!-- Preview -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Subject:</span> {{ template.subject }}
                        </div>
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                    </div>
                    <iframe :srcdoc="template.html_content" class="w-full h-[600px] bg-white" sandbox></iframe>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
