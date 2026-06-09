<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ pages: Object });
const destroy = (id) => { if (confirm('Delete landing page?')) router.delete(route('landing-pages.destroy', id)); };
</script>

<template>
    <Head title="Landing Pages" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Landing Pages</h2>
                <Link :href="route('landing-pages.create')"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + New Landing Page
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="p in pages.data" :key="p.id"
                        class="bg-white rounded-xl shadow-sm overflow-hidden border hover:shadow-md transition-shadow">
                        <div class="bg-gray-100 h-32 overflow-hidden">
                            <iframe :srcdoc="p.html_content" class="w-full h-full pointer-events-none scale-75 origin-top-left" sandbox style="width:133%;height:133%"></iframe>
                        </div>
                        <div class="p-4">
                            <h4 class="font-medium text-gray-800">{{ p.name }}</h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span v-if="p.show_training_after_submit" class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full">Training page</span>
                                <span class="text-xs text-gray-400 uppercase">{{ p.language }}</span>
                            </div>
                            <div class="flex gap-2 mt-3">
                                <Link :href="route('landing-pages.show', p.id)"
                                    class="text-xs text-indigo-600 hover:underline">View</Link>
                                <Link :href="route('landing-pages.edit', p.id)"
                                    class="text-xs text-gray-500 hover:text-gray-700">Edit</Link>
                                <button @click="destroy(p.id)"
                                    class="text-xs text-red-600 hover:text-red-800">Delete</button>
                            </div>
                        </div>
                    </div>
                    <div v-if="!pages.data.length" class="col-span-3 py-12 text-center text-gray-400">
                        No landing pages. <Link :href="route('landing-pages.create')" class="text-indigo-600 hover:underline">Create one →</Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
