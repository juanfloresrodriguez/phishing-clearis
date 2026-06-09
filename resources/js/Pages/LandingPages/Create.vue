<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    html_content: '',
    training_html: '',
    redirect_url: '',
    language: 'es',
    show_training_after_submit: true,
});

const tab = ref('landing');
const preview = ref(false);

const defaultLanding = `<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.card{background:#fff;border-radius:8px;padding:40px;width:360px;box-shadow:0 2px 12px rgba(0,0,0,.1)}
h2{text-align:center;margin-bottom:20px;color:#1a1a2e}
input{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;margin-bottom:12px;box-sizing:border-box}
button{width:100%;padding:12px;background:#0066cc;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer}</style>
</head><body><div class="card">
<h2>Iniciar sesión</h2>
<form method="POST">
<input type="email" name="email" placeholder="Email">
<input type="password" name="password" placeholder="Contraseña">
<button type="submit">Acceder</button>
</form></div></body></html>`;

const submit = () => form.post(route('landing-pages.store'));
</script>

<template>
    <Head title="New Landing Page" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">New Landing Page</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                                <select v-model="form.language" class="w-full border rounded-lg px-3 py-2">
                                    <option value="es">Spanish</option>
                                    <option value="en">English</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700 mb-4">
                            <strong>Security note:</strong> Form submissions will only record interaction metadata (field types, timestamps). No passwords or sensitive values are stored.
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.show_training_after_submit" id="train" class="rounded" />
                            <label for="train" class="text-sm text-gray-700">Show training page after form submit</label>
                        </div>
                    </div>

                    <!-- Tabs: landing / training HTML -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="flex border-b">
                            <button type="button" @click="tab = 'landing'"
                                :class="['px-6 py-3 text-sm font-medium', tab === 'landing' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-gray-700']">
                                Landing Page HTML
                            </button>
                            <button type="button" @click="tab = 'training'"
                                :class="['px-6 py-3 text-sm font-medium', tab === 'training' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-gray-700']">
                                Training Page HTML
                            </button>
                        </div>

                        <div class="p-6">
                            <div class="flex justify-between mb-2">
                                <span class="text-xs text-gray-400">{{ tab === 'landing' ? 'The simulated login/phishing page' : 'Shown to user after submitting form' }}</span>
                                <div class="flex gap-2">
                                    <button type="button" v-if="tab === 'landing'" @click="form.html_content = defaultLanding"
                                        class="text-xs text-indigo-600 hover:underline">Load example</button>
                                    <button type="button" @click="preview = !preview"
                                        class="text-xs px-2 py-1 bg-gray-100 rounded">{{ preview ? 'Edit' : 'Preview' }}</button>
                                </div>
                            </div>

                            <template v-if="tab === 'landing'">
                                <textarea v-if="!preview" v-model="form.html_content"
                                    class="w-full border rounded-lg px-3 py-2 font-mono text-sm" rows="18" required />
                                <iframe v-else :srcdoc="form.html_content" class="w-full h-80 border rounded-lg" sandbox />
                            </template>
                            <template v-else>
                                <textarea v-if="!preview" v-model="form.training_html"
                                    class="w-full border rounded-lg px-3 py-2 font-mono text-sm" rows="18"
                                    placeholder="Leave empty to use default training page" />
                                <iframe v-else :srcdoc="form.training_html" class="w-full h-80 border rounded-lg" sandbox />
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('landing-pages.index')" class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : 'Save Landing Page' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
