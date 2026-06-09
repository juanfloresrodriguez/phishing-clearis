<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ page: Object });
</script>

<template>
    <Head :title="page.name" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">{{ page.name }}</h2>
                <Link :href="route('landing-pages.edit', page.id)"
                    class="px-4 py-2 bg-white border text-gray-700 rounded-lg text-sm hover:bg-gray-50">Edit</Link>
            </div>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="bg-gray-100 px-4 py-2 text-xs text-gray-500 border-b">Preview</div>
                    <iframe :srcdoc="page.html_content" class="w-full h-[600px] bg-white" sandbox />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
