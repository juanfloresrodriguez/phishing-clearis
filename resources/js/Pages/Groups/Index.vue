<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ groups: Array });

const showCreate = ref(false);

const form = useForm({
    name: '',
    description: '',
    type: 'static',
});

const submit = () => form.post(route('groups.store'), {
    onSuccess: () => { form.reset(); showCreate.value = false; }
});

const destroy = (id) => {
    if (confirm('¿Eliminar grupo?')) router.delete(route('groups.destroy', id));
};
</script>

<template>
    <Head title="Groups" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Groups</h2>
                <button @click="showCreate = true"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + New Group
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Create modal -->
                <div v-if="showCreate" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                        <h3 class="font-semibold text-gray-800 mb-4">New Group</h3>
                        <form @submit.prevent="submit" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2" required />
                                <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <input v-model="form.description" type="text" class="w-full border rounded-lg px-3 py-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select v-model="form.type" class="w-full border rounded-lg px-3 py-2">
                                    <option value="static">Static (manual members)</option>
                                    <option value="dynamic">Dynamic (by department/tags)</option>
                                </select>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="showCreate = false"
                                    class="px-4 py-2 border rounded-lg text-sm">Cancel</button>
                                <button type="submit" :disabled="form.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                                    Create
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Groups list -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Members</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="g in groups" :key="g.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">{{ g.name }}</div>
                                    <div v-if="g.description" class="text-xs text-gray-400">{{ g.description }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2 py-0.5 rounded-full text-xs font-medium',
                                        g.type === 'static' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700']">
                                        {{ g.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-700">{{ g.target_users_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="destroy(g.id)"
                                        class="text-xs text-red-600 hover:text-red-800">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!groups.length">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                    No groups yet. Create one to target users in campaigns.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
