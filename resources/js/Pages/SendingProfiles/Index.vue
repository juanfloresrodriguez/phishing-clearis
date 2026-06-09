<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ profiles: Array });

const showCreate = ref(false);

const form = useForm({
    name: '',
    from_name: '',
    from_email: '',
    reply_to: '',
    mailer: 'smtp',
    smtp_host: '',
    smtp_port: 587,
    smtp_username: '',
    smtp_password: '',
    smtp_encryption: 'tls',
});

const submit = () => form.post(route('sending-profiles.store'), {
    onSuccess: () => { form.reset(); showCreate.value = false; }
});

const checkDns = (id) => router.post(route('sending-profiles.check-dns', id));
const verify   = (id) => router.post(route('sending-profiles.verify', id));
const destroy  = (id) => { if (confirm('¿Eliminar perfil?')) router.delete(route('sending-profiles.destroy', id)); };
</script>

<template>
    <Head title="Sending Profiles" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Sending Profiles</h2>
                <button @click="showCreate = true"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + New Profile
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Aviso DNS -->
                <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 text-sm">
                    <strong>Requisito:</strong> El dominio del remitente debe estar verificado en
                    <a :href="route('organization.show')" class="underline">Organization → Domains</a>
                    con "Allow sending" activado antes de crear un perfil.
                </div>

                <!-- Modal crear perfil -->
                <div v-if="showCreate" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
                    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-xl max-h-screen overflow-y-auto">
                        <h3 class="font-semibold text-gray-800 mb-4">New Sending Profile</h3>
                        <form @submit.prevent="submit" class="space-y-3">

                            <div class="grid grid-cols-2 gap-3">
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500 mb-1">Profile Name *</label>
                                    <input v-model="form.name" type="text" placeholder="SMTP Google Workspace"
                                        class="w-full border rounded-lg px-3 py-2 text-sm" required />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">From Name *</label>
                                    <input v-model="form.from_name" type="text" placeholder="IT Security Team"
                                        class="w-full border rounded-lg px-3 py-2 text-sm" required />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">From Email *</label>
                                    <input v-model="form.from_email" type="email" placeholder="security@tuempresa.com"
                                        class="w-full border rounded-lg px-3 py-2 text-sm" required />
                                    <p v-if="form.errors.from_email" class="text-red-500 text-xs mt-1">{{ form.errors.from_email }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">SMTP Host *</label>
                                    <input v-model="form.smtp_host" type="text" placeholder="smtp.gmail.com"
                                        class="w-full border rounded-lg px-3 py-2 text-sm" required />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Port</label>
                                    <input v-model.number="form.smtp_port" type="number"
                                        class="w-full border rounded-lg px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Username</label>
                                    <input v-model="form.smtp_username" type="text"
                                        class="w-full border rounded-lg px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Password</label>
                                    <input v-model="form.smtp_password" type="password"
                                        placeholder="App password entre comillas si tiene espacios"
                                        class="w-full border rounded-lg px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Encryption</label>
                                    <select v-model="form.smtp_encryption" class="w-full border rounded-lg px-3 py-2 text-sm">
                                        <option value="tls">TLS (587)</option>
                                        <option value="ssl">SSL (465)</option>
                                        <option value="none">None</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">Mailer type</label>
                                    <select v-model="form.mailer" class="w-full border rounded-lg px-3 py-2 text-sm">
                                        <option value="smtp">SMTP</option>
                                        <option value="google_relay">Google Workspace Relay</option>
                                    </select>
                                </div>
                            </div>

                            <p v-if="form.errors.from_email" class="text-red-500 text-xs">{{ form.errors.from_email }}</p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="showCreate = false"
                                    class="px-4 py-2 border rounded-lg text-sm">Cancel</button>
                                <button type="submit" :disabled="form.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                                    Save Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabla de perfiles -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">SPF</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">DKIM</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">DMARC</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="p in profiles" :key="p.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-800">{{ p.name }}</div>
                                    <div class="text-xs text-gray-400">{{ p.smtp_host }}:{{ p.smtp_port }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div>{{ p.from_name }}</div>
                                    <div class="text-xs text-gray-400">{{ p.from_email }}</div>
                                </td>
                                <td class="px-6 py-4 text-center text-lg">{{ p.spf_ok ? '✅' : '❌' }}</td>
                                <td class="px-6 py-4 text-center text-lg">{{ p.dkim_ok ? '✅' : '❌' }}</td>
                                <td class="px-6 py-4 text-center text-lg">{{ p.dmarc_ok ? '✅' : '❌' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="['px-2 py-0.5 rounded-full text-xs font-medium',
                                        p.is_verified ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700']">
                                        {{ p.is_verified ? 'Verified' : 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="checkDns(p.id)"
                                        class="text-xs text-blue-600 hover:text-blue-800">Check DNS</button>
                                    <button v-if="!p.is_verified" @click="verify(p.id)"
                                        class="text-xs text-green-600 hover:text-green-800">Verify</button>
                                    <button @click="destroy(p.id)"
                                        class="text-xs text-red-600 hover:text-red-800">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!profiles.length">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    No sending profiles. Add one to start sending campaigns.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
