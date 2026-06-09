<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ page: Object });

const form = useForm({
    name: props.page.name,
    html_content: props.page.html_content,
    training_html: props.page.training_html ?? '',
    redirect_url: props.page.redirect_url ?? '',
    language: props.page.language,
    show_training_after_submit: props.page.show_training_after_submit,
});

const tab = ref('landing');
const preview = ref(false);
const submit = () => form.put(route('landing-pages.update', props.page.id));
</script>

<template>
    <Head :title="'Edit: ' + page.name" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Edit: {{ page.name }}</h2>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm p-6 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2" required />
                        </div>
                        <div class="flex items-center gap-2 pt-5">
                            <input type="checkbox" v-model="form.show_training_after_submit" class="rounded" />
                            <span class="text-sm text-gray-700">Show training after submit</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="flex border-b">
                            <button type="button" @click="tab = 'landing'" :class="['px-6 py-3 text-sm', tab==='landing'?'border-b-2 border-indigo-600 text-indigo-600':'text-gray-500']">Landing HTML</button>
                            <button type="button" @click="tab = 'training'" :class="['px-6 py-3 text-sm', tab==='training'?'border-b-2 border-indigo-600 text-indigo-600':'text-gray-500']">Training HTML</button>
                        </div>
                        <div class="p-6">
                            <div class="flex justify-end mb-2">
                                <button type="button" @click="preview = !preview" class="text-xs px-2 py-1 bg-gray-100 rounded">{{ preview ? 'Edit' : 'Preview' }}</button>
                            </div>
                            <template v-if="tab === 'landing'">
                                <textarea v-if="!preview" v-model="form.html_content" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" rows="18" required />
                                <iframe v-else :srcdoc="form.html_content" class="w-full h-80 border rounded-lg" sandbox />
                            </template>
                            <template v-else>
                                <textarea v-if="!preview" v-model="form.training_html" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" rows="18" />
                                <iframe v-else :srcdoc="form.training_html" class="w-full h-80 border rounded-lg" sandbox />
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('landing-pages.show', page.id)" class="px-4 py-2 border rounded-lg text-sm text-gray-700">Cancel</a>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
