<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ users: Object, departments: Array });

const showImport = ref(false);
const showAdd = ref(false);

const addForm = useForm({
    first_name: '', last_name: '', email: '',
    department: '', job_title: '', office: '', language: 'es',
});

const importForm = useForm({ file: null });

const addUser = () => addForm.post(route('target-users.store'), {
    onSuccess: () => { addForm.reset(); showAdd.value = false; }
});

const importCsv = () => {
    const fd = new FormData();
    fd.append('file', importForm.file);
    router.post(route('target-users.import'), fd, {
        forceFormData: true,
        onSuccess: () => { showImport.value = false; }
    });
};

const toggleExclude = (id) => router.post(route('target-users.exclude', id));
const destroy = (id) => { if (confirm('Delete user?')) router.delete(route('target-users.destroy', id)); };
</script>

<template>
    <Head title="Target Users" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Target Users</h2>
                <div class="flex gap-2">
                    <button @click="showImport = true"
                        class="px-4 py-2 bg-white border text-gray-700 rounded-lg text-sm hover:bg-gray-50">Import CSV</button>
                    <button @click="showAdd = true"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">+ Add User</button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Import modal -->
                <div v-if="showImport" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                        <h3 class="font-semibold text-gray-800 mb-4">Import CSV</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Required columns: <code>email, first_name, last_name</code><br>
                            Optional: <code>department, job_title, office, language</code>
                        </p>
                        <input type="file" accept=".csv,.txt" @change="e => importForm.file = e.target.files[0]"
                            class="w-full border rounded-lg px-3 py-2 text-sm mb-4" />
                        <div class="flex justify-end gap-2">
                            <button @click="showImport = false" class="px-4 py-2 border rounded-lg text-sm">Cancel</button>
                            <button @click="importCsv" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">Import</button>
                        </div>
                    </div>
                </div>

                <!-- Add user modal -->
                <div v-if="showAdd" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg">
                        <h3 class="font-semibold text-gray-800 mb-4">Add User</h3>
                        <form @submit.prevent="addUser" class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">First Name *</label>
                                <input v-model="addForm.first_name" type="text" class="w-full border rounded-lg px-3 py-2 text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Last Name *</label>
                                <input v-model="addForm.last_name" type="text" class="w-full border rounded-lg px-3 py-2 text-sm" required />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">Email *</label>
                                <input v-model="addForm.email" type="email" class="w-full border rounded-lg px-3 py-2 text-sm" required />
                                <p v-if="addForm.errors.email" class="text-red-500 text-xs mt-1">{{ addForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Department</label>
                                <input v-model="addForm.department" type="text" class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Job Title</label>
                                <input v-model="addForm.job_title" type="text" class="w-full border rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <div class="col-span-2 flex justify-end gap-2 mt-2">
                                <button type="button" @click="showAdd = false" class="px-4 py-2 border rounded-lg text-sm">Cancel</button>
                                <button type="submit" :disabled="addForm.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-3 border-b text-sm text-gray-500">
                        {{ users.total }} users
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="u in users.data" :key="u.id" :class="{'opacity-50': u.excluded}">
                                <td class="px-6 py-3 text-sm font-medium text-gray-800">
                                    {{ u.first_name }} {{ u.last_name }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ u.email }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ u.department ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    <span v-if="u.excluded" class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">Excluded</span>
                                    <span v-else-if="u.is_active" class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">Active</span>
                                    <span v-else class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs">Inactive</span>
                                </td>
                                <td class="px-6 py-3 text-right space-x-3">
                                    <button @click="toggleExclude(u.id)"
                                        class="text-xs text-yellow-600 hover:text-yellow-800">
                                        {{ u.excluded ? 'Include' : 'Exclude' }}
                                    </button>
                                    <button @click="destroy(u.id)" class="text-xs text-red-600 hover:text-red-800">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="flex justify-center gap-2">
                    <Link v-for="link in users.links" :key="link.label"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        :class="['px-3 py-1 rounded text-sm', link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50']" />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
