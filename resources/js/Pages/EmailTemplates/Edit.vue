<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ template: Object });

const form = useForm({
    name: props.template.name,
    subject: props.template.subject,
    from_name: props.template.from_name ?? '',
    html_content: props.template.html_content,
    text_content: props.template.text_content ?? '',
    category: props.template.category,
    language: props.template.language,
    has_attachment_simulation: props.template.has_attachment_simulation,
});

const preview = ref(false);
const submit = () => form.put(route('email-templates.update', props.template.id));
</script>

<template>
    <Head :title="'Edit: ' + template.name" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Edit Template: {{ template.name }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm p-8">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <input v-model="form.subject" type="text" class="w-full border rounded-lg px-3 py-2" required />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-8">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700">HTML Content</label>
                            <button type="button" @click="preview = !preview"
                                class="text-xs px-3 py-1 bg-gray-100 rounded hover:bg-gray-200">
                                {{ preview ? 'Edit' : 'Preview' }}
                            </button>
                        </div>
                        <textarea v-if="!preview" v-model="form.html_content"
                            class="w-full border rounded-lg px-3 py-2 font-mono text-sm" rows="20" required />
                        <iframe v-else :srcdoc="form.html_content" class="w-full h-96 border rounded-lg bg-white" sandbox />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a :href="route('email-templates.show', template.id)"
                            class="px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
