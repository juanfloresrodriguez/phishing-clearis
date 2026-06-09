<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ categories: Array });

const form = useForm({
    name: '',
    subject: '',
    from_name: '',
    html_content: '',
    text_content: '',
    category: 'custom',
    language: 'es',
    has_attachment_simulation: false,
});

const preview = ref(false);

const submit = () => form.post(route('email-templates.store'));

const defaultTemplate = `<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#333}
.btn{display:inline-block;background:#0066cc;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px}</style>
</head><body>
<p>Hola {{first_name}},</p>
<p>Mensaje de simulación de phishing.</p>
<p><a href="{{landing_url}}" class="btn">Acceder ahora</a></p>
<p><small>¿Sospechas que este email es fraudulento? <a href="{{report_url}}">Repórtalo aquí</a></small></p>
{{tracking_pixel}}
</body></html>`;
</script>

<template>
    <Head title="New Email Template" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">New Email Template</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm p-8">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
                                <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select v-model="form.category" class="w-full border rounded-lg px-3 py-2">
                                    <option v-for="c in categories" :key="c" :value="c" class="capitalize">{{ c }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Subject *</label>
                                <input v-model="form.subject" type="text" class="w-full border rounded-lg px-3 py-2" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sender Name</label>
                                <input v-model="form.from_name" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="Override profile name" />
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
                            <div class="flex items-center gap-2 pt-5">
                                <input type="checkbox" v-model="form.has_attachment_simulation" id="attach" class="rounded" />
                                <label for="attach" class="text-sm text-gray-700">Simulate attachment (no real file)</label>
                            </div>
                        </div>
                    </div>

                    <!-- HTML Editor -->
                    <div class="bg-white rounded-xl shadow-sm p-8">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700">HTML Content *</label>
                            <div class="flex gap-2">
                                <button type="button" @click="form.html_content = defaultTemplate"
                                    class="text-xs text-indigo-600 hover:underline">Load starter template</button>
                                <button type="button" @click="preview = !preview"
                                    class="text-xs px-3 py-1 bg-gray-100 rounded hover:bg-gray-200">
                                    {{ preview ? 'Edit' : 'Preview' }}
                                </button>
                            </div>
                        </div>

                        <div class="text-xs text-gray-400 mb-2">
                            Variables: {{first_name}} {{last_name}} {{email}} {{department}} {{campaign_name}} {{landing_url}} {{report_url}} {{tracking_pixel}}
                        </div>

                        <div v-if="!preview">
                            <textarea v-model="form.html_content"
                                class="w-full border rounded-lg px-3 py-2 font-mono text-sm"
                                rows="20" required></textarea>
                        </div>
                        <div v-else class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-100 px-3 py-2 text-xs text-gray-500 flex gap-4 border-b">
                                <span>📧 {{ form.subject || 'No subject' }}</span>
                            </div>
                            <iframe :srcdoc="form.html_content" class="w-full h-96 bg-white" sandbox></iframe>
                        </div>
                        <p v-if="form.errors.html_content" class="text-red-500 text-xs mt-1">{{ form.errors.html_content }}</p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('email-templates.index')" class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : 'Save Template' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
