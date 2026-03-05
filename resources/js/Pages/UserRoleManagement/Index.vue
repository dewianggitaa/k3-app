<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { ref, computed } from 'vue';
import { PERMISSION_GROUPS, getLabel } from '@/Composable/permission';
import { useForm, Head, router, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { watch } from 'vue';
const props = defineProps({
    users: Object,
    roles: Array,
    departments: Array,
    positions: Array,
    rolesWithPermissions: Array,
    filters: Object,
    can: Object,
});

const activeTab = ref(props.filters?.tab || 'users');

// ─── USER MANAGEMENT ──────────────────────────────────────────────────────────
const showUserModal = ref(false);
const isEditingUser = ref(false);
const editingUser = ref(null);
const searchQuery = ref(props.filters?.search || '');

const applyFilters = debounce(() => {
    router.get(route('users.index'), {
        search: searchQuery.value || undefined,
        tab: activeTab.value,
    }, { preserveState: true, replace: true });
}, 350);

watch([searchQuery, activeTab], () => applyFilters());

const userForm = useForm({
    name: '',
    username: '',
    password: '',
    department_id: null,
    position_id: null,
    is_active: true,
    role: '',
});

const openCreateUser = () => {
    isEditingUser.value = false;
    editingUser.value = null;
    userForm.reset();
    userForm.clearErrors();
    userForm.is_active = true;
    showUserModal.value = true;
};

const openEditUser = (user) => {
    isEditingUser.value = true;
    editingUser.value = user;
    userForm.name = user.name;
    userForm.username = user.username;
    userForm.password = '';
    userForm.department_id = user.department_id;
    userForm.position_id = user.position_id;
    userForm.is_active = user.is_active;
    userForm.role = user.roles?.[0] || '';
    userForm.clearErrors();
    showUserModal.value = true;
};

const saveUser = () => {
    if (isEditingUser.value) {
        userForm.put(route('users.update', editingUser.value.id), {
            onSuccess: () => { showUserModal.value = false; },
            preserveScroll: true,
        });
    } else {
        userForm.post(route('users.store'), {
            onSuccess: () => { showUserModal.value = false; },
            preserveScroll: true,
        });
    }
};

const toggleUserStatus = (user) => {
    if (confirm(`${user.is_active ? 'Nonaktifkan' : 'Aktifkan'} user "${user.name}"?`)) {
        router.patch(route('users.toggle-status', user.id), {}, { preserveScroll: true });
    }
};

const deleteUser = (user) => {
    if (confirm(`Hapus user "${user.name}"? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(route('users.destroy', user.id), { preserveScroll: true });
    }
};

// ─── ROLE MANAGEMENT ──────────────────────────────────────────────────────────
const showRoleModal = ref(false);
const isEditingRole = ref(false);
const editingRole = ref(null);
const groups = PERMISSION_GROUPS;

const roleForm = useForm({
    name: '',
    permissions: [],
});

const openCreateRole = () => {
    isEditingRole.value = false;
    editingRole.value = null;
    roleForm.reset();
    roleForm.clearErrors();
    showRoleModal.value = true;
};

const openEditRole = (role) => {
    isEditingRole.value = true;
    editingRole.value = role;
    roleForm.name = role.name;
    roleForm.permissions = (role.permissions || []).map(p => p.name);
    roleForm.clearErrors();
    showRoleModal.value = true;
};

const saveRole = () => {
    if (!roleForm.name) return alert('Nama Role wajib diisi');

    if (isEditingRole.value) {
        roleForm.patch(route('roles.update', editingRole.value.id), {
            onSuccess: () => { showRoleModal.value = false; },
            preserveScroll: true,
        });
    } else {
        roleForm.post(route('roles.store'), {
            onSuccess: () => { showRoleModal.value = false; },
            preserveScroll: true,
        });
    }
};

const deleteRole = (role) => {
    if (confirm(`Hapus role "${role.name}"? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(route('roles.destroy', role.id), { preserveScroll: true });
    }
};

const toggleGroup = (group) => {
    const allSelected = group.permissions.every(code => roleForm.permissions.includes(code));
    if (allSelected) {
        roleForm.permissions = roleForm.permissions.filter(code => !group.permissions.includes(code));
    } else {
        group.permissions.forEach(code => {
            if (!roleForm.permissions.includes(code)) roleForm.permissions.push(code);
        });
    }
};

const isGroupAllSelected = (group) => {
    return group.permissions.every(code => roleForm.permissions.includes(code));
};
</script>

<template>
    <MainLayout>
        <Head title="Kelola Pengguna" />

        <template #header-title>
            <h1 class="text-lg font-bold text-ink dark:text-ink-dark">Kelola Pengguna</h1>
        </template>

        <template #header-nav>
            <div class="flex border-b border-ghost dark:border-gray-700">
                <button
                    @click="activeTab = 'users'"
                    class="px-5 py-2.5 text-xs font-semibold transition-colors relative"
                    :class="activeTab === 'users'
                        ? 'text-primary dark:text-primary-dark'
                        : 'text-ink/50 dark:text-ink-dark/50 hover:text-ink dark:hover:text-ink-dark'"
                >
                    User Management
                    <div v-if="activeTab === 'users'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary dark:bg-primary-dark rounded-full"></div>
                </button>
                <button
                    v-if="can?.manage_roles"
                    @click="activeTab = 'roles'"
                    class="px-5 py-2.5 text-xs font-semibold transition-colors relative"
                    :class="activeTab === 'roles'
                        ? 'text-primary dark:text-primary-dark'
                        : 'text-ink/50 dark:text-ink-dark/50 hover:text-ink dark:hover:text-ink-dark'"
                >
                    Role & Permission
                    <div v-if="activeTab === 'roles'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary dark:bg-primary-dark rounded-full"></div>
                </button>
            </div>
        </template>

        <!-- ─── TAB: USER MANAGEMENT ────────────────────────────────────────── -->
        <div v-if="activeTab === 'users'">
            <div class="flex justify-between items-center mb-4">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari nama, username, atau department..."
                    class="w-72 px-3 py-1.5 text-xs rounded-lg border border-ghost dark:border-gray-600 bg-surface dark:bg-surface-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary dark:focus:ring-primary-dark outline-none transition"
                />
                <button
                    v-if="can?.create_users"
                    @click="openCreateUser"
                    class="flex items-center gap-1.5 bg-primary dark:bg-primary-dark text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:opacity-90 transition shadow-sm"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah User
                </button>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl border border-ghost dark:border-gray-700 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-ghost/50 dark:bg-gray-800 text-ink/60 dark:text-ink-dark/60 uppercase tracking-wider text-[10px]">
                                <th class="px-3 py-2.5 text-left font-semibold w-8">#</th>
                                <th class="px-3 py-2.5 text-left font-semibold">Nama</th>
                                <th class="px-3 py-2.5 text-left font-semibold">Username</th>
                                <th class="px-3 py-2.5 text-left font-semibold">Department</th>
                                <th class="px-3 py-2.5 text-left font-semibold">Jabatan</th>
                                <th class="px-3 py-2.5 text-left font-semibold">Role</th>
                                <th class="px-3 py-2.5 text-center font-semibold">Status</th>
                                <th v-if="can?.edit_users || can?.toggle_user_status || can?.delete_users" class="px-3 py-2.5 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ghost dark:divide-gray-700">
                            <tr v-for="(user, i) in users.data" :key="user.id" class="hover:bg-ghost/30 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-3 py-2 text-ink/40 dark:text-ink-dark/40">{{ (users.current_page - 1) * users.per_page + i + 1 }}</td>
                                <td class="px-3 py-2 font-medium text-ink dark:text-ink-dark">{{ user.name }}</td>
                                <td class="px-3 py-2 text-ink/70 dark:text-ink-dark/70 font-mono">{{ user.username }}</td>
                                <td class="px-3 py-2 text-ink/70 dark:text-ink-dark/70">{{ user.department || '-' }}</td>
                                <td class="px-3 py-2 text-ink/70 dark:text-ink-dark/70">{{ user.position || '-' }}</td>
                                <td class="px-3 py-2">
                                    <span v-for="role in user.roles" :key="role" class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 mr-1">
                                        {{ role }}
                                    </span>
                                    <span v-if="!user.roles?.length" class="text-ink/30 dark:text-ink-dark/30 italic">-</span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span
                                        class="inline-block px-1.5 py-0.5 text-[10px] font-bold rounded-full"
                                        :class="user.is_active ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'"
                                    >
                                        {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td v-if="can?.edit_users || can?.toggle_user_status || can?.delete_users" class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button v-if="can?.edit_users" @click="openEditUser(user)" class="p-1 rounded hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 transition" title="Edit">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button v-if="can?.toggle_user_status" @click="toggleUserStatus(user)" class="p-1 rounded transition" :class="user.is_active ? 'hover:bg-amber-100 dark:hover:bg-amber-900/30 text-amber-600 dark:text-amber-400' : 'hover:bg-emerald-100 dark:hover:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'" :title="user.is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                            <svg v-if="user.is_active" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button v-if="can?.delete_users" @click="deleteUser(user)" class="p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500 dark:text-red-400 transition" title="Hapus">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.data.length">
                                <td colspan="8" class="px-3 py-8 text-center text-ink/40 dark:text-ink-dark/40">Tidak ada data user.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAGINATION -->
            <div v-if="users.links && users.links.length > 3" class="mt-4 flex justify-center">
                <div class="flex gap-1 bg-surface dark:bg-surface-dark p-2 rounded-xl shadow-sm border border-ghost dark:border-gray-700">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, k) in users.links" :key="k"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                        :class="{
                            'bg-primary dark:bg-primary-dark text-white shadow-sm': link.active,
                            'text-ink/60 dark:text-ink-dark/60 hover:bg-ghost/50 dark:hover:bg-gray-700': !link.active && link.url,
                            'opacity-30 cursor-default text-ink/40': !link.url
                        }"
                    />
                </div>
            </div>
        </div>

        <!-- ─── TAB: ROLE & PERMISSION ──────────────────────────────────────── -->
        <div v-if="activeTab === 'roles' && can?.manage_roles">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <p class="text-xs text-ink/50 dark:text-ink-dark/50">Atur role dan hak akses pengguna di sistem.</p>
                </div>
                <button
                    @click="openCreateRole"
                    class="flex items-center gap-1.5 bg-primary dark:bg-primary-dark text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:opacity-90 transition shadow-sm"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Role
                </button>
            </div>

            <div class="bg-surface dark:bg-surface-dark rounded-xl border border-ghost dark:border-gray-700 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-ghost/50 dark:bg-gray-800 text-ink/60 dark:text-ink-dark/60 uppercase tracking-wider text-[10px]">
                                <th class="px-3 py-2.5 text-left font-semibold">Nama Role</th>
                                <th class="px-3 py-2.5 text-left font-semibold">Preview Akses</th>
                                <th class="px-3 py-2.5 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ghost dark:divide-gray-700">
                            <tr v-for="role in rolesWithPermissions" :key="role.id" class="hover:bg-ghost/30 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-3 py-2">
                                    <span class="font-bold text-ink dark:text-ink-dark">{{ role.name }}</span>
                                    <span v-if="role.name === 'Super Admin'" class="ml-1.5 px-1.5 py-0.5 text-[9px] bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full font-semibold">System</span>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1 max-w-lg">
                                        <span
                                            v-for="perm in (role.permissions || [])"
                                            :key="perm.id"
                                            class="text-[10px] text-ink/50 dark:text-ink-dark/50 bg-ghost dark:bg-gray-700 border border-ghost dark:border-gray-600 px-1.5 py-0.5 rounded"
                                        >
                                            {{ getLabel(perm.name) }}
                                        </span>
                                        <span v-if="!role.permissions?.length" class="text-[10px] text-ink/30 dark:text-ink-dark/30 italic">Tidak ada akses</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEditRole(role)" class="px-2 py-1 rounded text-[10px] font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                                            Edit Akses
                                        </button>
                                        <button @click="deleteRole(role)" class="px-2 py-1 rounded text-[10px] font-semibold text-red-500 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!rolesWithPermissions?.length">
                                <td colspan="3" class="px-3 py-8 text-center text-ink/40 dark:text-ink-dark/40">Tidak ada role.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ─── MODAL: USER ─────────────────────────────────────────────────── -->
        <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showUserModal = false"></div>
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col border border-ghost dark:border-gray-700">
                <!-- Header -->
                <div class="px-5 py-3 border-b border-ghost dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-ink dark:text-ink-dark">{{ isEditingUser ? 'Edit User' : 'Tambah User Baru' }}</h3>
                    <button @click="showUserModal = false" class="text-ink/40 dark:text-ink-dark/40 hover:text-ink dark:hover:text-ink-dark transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="p-5 overflow-y-auto space-y-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-ink/60 dark:text-ink-dark/60 mb-1">Nama Lengkap</label>
                        <input v-model="userForm.name" type="text" class="w-full px-3 py-1.5 text-xs rounded-lg border border-ghost dark:border-gray-600 bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none" placeholder="Masukkan nama lengkap" />
                        <p v-if="userForm.errors.name" class="text-red-500 text-[10px] mt-0.5">{{ userForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-ink/60 dark:text-ink-dark/60 mb-1">Username</label>
                        <input v-model="userForm.username" type="text" class="w-full px-3 py-1.5 text-xs rounded-lg border border-ghost dark:border-gray-600 bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none font-mono" placeholder="username" />
                        <p v-if="userForm.errors.username" class="text-red-500 text-[10px] mt-0.5">{{ userForm.errors.username }}</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-ink/60 dark:text-ink-dark/60 mb-1">Password {{ isEditingUser ? '(kosongkan jika tidak diubah)' : '' }}</label>
                        <input v-model="userForm.password" type="password" class="w-full px-3 py-1.5 text-xs rounded-lg border border-ghost dark:border-gray-600 bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none" :placeholder="isEditingUser ? '••••••••' : 'Min. 6 karakter'" />
                        <p v-if="userForm.errors.password" class="text-red-500 text-[10px] mt-0.5">{{ userForm.errors.password }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-ink/60 dark:text-ink-dark/60 mb-1">Department</label>
                            <select v-model="userForm.department_id" class="w-full px-3 py-1.5 text-xs rounded-lg border border-ghost dark:border-gray-600 bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none">
                                <option :value="null">-- Pilih --</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-ink/60 dark:text-ink-dark/60 mb-1">Jabatan</label>
                            <select v-model="userForm.position_id" class="w-full px-3 py-1.5 text-xs rounded-lg border border-ghost dark:border-gray-600 bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none">
                                <option :value="null">-- Pilih --</option>
                                <option v-for="pos in positions" :key="pos.id" :value="pos.id">{{ pos.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-ink/60 dark:text-ink-dark/60 mb-1">Role</label>
                        <select v-model="userForm.role" class="w-full px-3 py-1.5 text-xs rounded-lg border border-ghost dark:border-gray-600 bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none">
                            <option value="">-- Pilih Role --</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_active" v-model="userForm.is_active" class="w-3.5 h-3.5 rounded border-gray-300 text-primary focus:ring-primary" />
                        <label for="is_active" class="text-xs text-ink/70 dark:text-ink-dark/70 cursor-pointer">Akun aktif</label>
                    </div>
                </div>
                <!-- Footer -->
                <div class="px-5 py-3 border-t border-ghost dark:border-gray-700 flex justify-end gap-2">
                    <button @click="showUserModal = false" class="px-3 py-1.5 text-xs font-medium text-ink/60 dark:text-ink-dark/60 bg-ghost dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Batal</button>
                    <button @click="saveUser" :disabled="userForm.processing" class="px-4 py-1.5 text-xs font-semibold text-white bg-primary dark:bg-primary-dark rounded-lg hover:opacity-90 transition disabled:opacity-50">
                        {{ isEditingUser ? 'Simpan' : 'Tambah' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ─── MODAL: ROLE ─────────────────────────────────────────────────── -->
        <div v-if="showRoleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showRoleModal = false"></div>
            <div class="relative bg-surface dark:bg-surface-dark rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col border border-ghost dark:border-gray-700">
                <!-- Header -->
                <div class="px-5 py-3 border-b border-ghost dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-ink dark:text-ink-dark">{{ isEditingRole ? 'Edit Role & Permissions' : 'Buat Role Baru' }}</h3>
                    <button @click="showRoleModal = false" class="text-ink/40 dark:text-ink-dark/40 hover:text-ink dark:hover:text-ink-dark transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="p-5 overflow-y-auto custom-scrollbar flex-1 space-y-4">
                    <div>
                        <label class="block text-[11px] font-semibold text-ink/60 dark:text-ink-dark/60 mb-1">Nama Role</label>
                        <input v-model="roleForm.name" type="text" class="w-full px-3 py-1.5 text-xs rounded-lg border border-ghost dark:border-gray-600 bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none" placeholder="Contoh: Staff Gudang, HR Manager..." />
                        <p v-if="roleForm.errors.name" class="text-red-500 text-[10px] mt-0.5">{{ roleForm.errors.name }}</p>
                    </div>

                    <div class="border-t border-ghost dark:border-gray-700 pt-3">
                        <h4 class="text-[10px] font-bold text-ink/40 dark:text-ink-dark/40 uppercase tracking-widest mb-3">Setting Hak Akses</h4>

                        <div class="space-y-3">
                            <div v-for="(group, idx) in groups" :key="idx" class="bg-ghost/40 dark:bg-gray-800/50 p-3 rounded-lg border border-ghost dark:border-gray-700">
                                <div class="flex justify-between items-center mb-2">
                                    <h5 class="text-xs font-bold text-ink dark:text-ink-dark">{{ group.category }}</h5>
                                    <button
                                        @click="toggleGroup(group)"
                                        type="button"
                                        class="text-[10px] font-semibold transition"
                                        :class="isGroupAllSelected(group) ? 'text-amber-600 dark:text-amber-400 hover:underline' : 'text-primary dark:text-primary-dark hover:underline'"
                                    >
                                        {{ isGroupAllSelected(group) ? 'Batal Semua' : 'Pilih Semua' }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-1.5">
                                    <label
                                        v-for="permCode in group.permissions"
                                        :key="permCode"
                                        class="flex items-center p-2 bg-surface dark:bg-surface-dark border rounded-lg cursor-pointer transition-all select-none text-xs"
                                        :class="roleForm.permissions.includes(permCode)
                                            ? 'border-primary dark:border-primary-dark bg-primary/5 dark:bg-primary-dark/10 ring-1 ring-primary/30 dark:ring-primary-dark/30'
                                            : 'border-ghost dark:border-gray-600 hover:border-primary/40 dark:hover:border-primary-dark/40'"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="permCode"
                                            v-model="roleForm.permissions"
                                            class="w-3.5 h-3.5 text-primary dark:text-primary-dark border-gray-300 rounded focus:ring-primary"
                                        />
                                        <span class="ml-2 text-ink/80 dark:text-ink-dark/80">{{ getLabel(permCode) }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="px-5 py-3 border-t border-ghost dark:border-gray-700 flex justify-end gap-2">
                    <button @click="showRoleModal = false" class="px-3 py-1.5 text-xs font-medium text-ink/60 dark:text-ink-dark/60 bg-ghost dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">Batal</button>
                    <button @click="saveRole" :disabled="roleForm.processing" class="px-4 py-1.5 text-xs font-semibold text-white bg-primary dark:bg-primary-dark rounded-lg hover:opacity-90 transition disabled:opacity-50">
                        {{ isEditingRole ? 'Simpan Perubahan' : 'Buat Role' }}
                    </button>
                </div>
            </div>
        </div>

    </MainLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4b5563; }
</style>
