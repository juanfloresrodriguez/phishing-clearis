<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ templates: Object });
const destroy = (id) => { if (confirm('Delete template?')) router.delete(route('email-templates.destroy', id)); };
</script>

<template>
    <Head title="Email Templates" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Email Templates</h2>
                <Link :href="route('email-templates.create')"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + New Template
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Language</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Version</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="t in templates.data" :key="t.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <Link :href="route('email-templates.show', t.id)"
                                        class="font-medium text-indigo-600 hover:underline">{{ t.name }}</Link>
                                    <div class="text-xs text-gray-400">{{ t.subject }}</div>
                                </td>
                                <td class="px-6 py-4 capitalize text-sm text-gray-600">{{ t.category }}</td>
                                <td class="px-6 py-4 uppercase text-sm text-gray-600">{{ t.language }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">v{{ t.version }}</td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <Link :href="route('email-templates.edit', t.id)" class="text-xs text-gray-500 hover:text-gray-700">Edit</Link>
                                    <button @click="destroy(t.id)" class="text-xs text-red-600 hover:text-red-800">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!templates.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                    No templates. <Link :href="route('email-templates.create')" class="text-indigo-600 hover:underline">Create one →</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
