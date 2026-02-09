<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { 
    Pencil, Trash2, Plus, MapPin, Save, X, 
    BriefcaseMedical, ChevronLeft 
} from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import NavLink from '@/Components/NavLink.vue';

const props = defineProps({
    p3ks: Object,
    p3k_types: Array,
    rooms: Array,      
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    code: '',
    p3k_type_id: '',
    room_id: '',
    status: 'safe',
    location_data: null, 
});

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
});

// FIXED: Menggunakan 'p3ks.index'
watch(search, debounce((value) => {
    router.get(route('p3ks.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.code = item.code;
    form.p3k_type_id = item.p3k_type_id;
    form.room_id = item.room_id;
    form.status = item.status;
    form.location_data = item.location_data;
    showModal.value = true;
};

const submit = () => {
    // FIXED: Menggunakan 'p3ks.update' dan 'p3ks.store'
    const action = isEditing.value 
        ? route('p3ks.update', form.id) 
        : route('p3ks.store');
    
    const method = isEditing.value ? 'put' : 'post';

    form[method](action, {
        onSuccess: () => {
            showModal.value = false;
            toast.fire({ 
                icon: 'success', 
                title: isEditing.value ? 'Data P3K diperbarui' : 'Data P3K ditambahkan' 
            });
        }
    });
};

const deleteP3k = (id, code) => {
    Swal.fire({
        title: 'Hapus Kotak P3K?',
        text: `P3K ${code} akan dihapus secara permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        confirmButtonColor: '#ef4444',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // FIXED: Menggunakan 'p3ks.destroy'
            router.delete(route('p3ks.destroy', id), {
                onSuccess: () => toast.fire({ icon: 'success', title: 'Berhasil dihapus' })
            });
        }
    });
};

const columns = [
    { label: 'No', key: 'no', class: 'w-12 text-center' },
    { label: 'Kode P3K', key: 'code', class: 'font-bold' },
    { label: 'Tipe', key: 'type' },
    { label: 'Lokasi', key: 'location' },
    { label: 'Status', key: 'status', class: 'w-32' },
    { label: '', key: 'action', class: 'text-right' },
];
</script>

<template>
    <Head title="Master Data P3K" />

    <MainLayout>
        <template #header-title>
            <div class="flex flex-col items-start w-full">
                
                <div class="flex items-center gap-4 mb-4 px-4"> 
                    <h2 class="font-bold text-lg text-gray-800 leading-tight">
                         {{ route().current('apars.*') ? 'Data APAR' : (route().current('hydrants.*') ? 'Data Hydrant' : 'Data P3K') }}
                    </h2>
                </div>
            </div>
        </template>

        <template #header-nav>
            <nav class="flex items-center gap-6 px-4"> 
                <NavLink 
                    :href="route('apars.index')" 
                    :active="route().current('apars.index')"
                    class="text-xs font-bold transition-all duration-200"
                >
                    APAR
                </NavLink>

                <NavLink 
                    :href="route('hydrants.index')" 
                    :active="route().current('hydrants.index')"
                    class="text-xs font-bold transition-all duration-200"
                >
                    Hydrant
                </NavLink>

                <NavLink 
                    :href="route('p3ks.index')" 
                    :active="route().current('p3ks.index')"
                    class="text-xs font-bold transition-all duration-200"
                >
                    P3K
                </NavLink>
            </nav>
        </template>

        <Card no-padding>
            <template #header>
                <div class="flex justify-between items-center gap-4">
                    <SearchInput v-model="search" placeholder="Cari kode atau tipe P3K..." class="w-64" />
                    <button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-md flex items-center gap-2 transition-colors">
                        <Plus class="w-4 h-4" /> Tambah P3K
                    </button>
                </div>
            </template>

            <DataTable :items="p3ks" :columns="columns">
                <template #cell-no="{ index }">
                    {{ (p3ks.current_page - 1) * p3ks.per_page + index + 1 }}
                </template>

                <template #cell-type="{ item }">
                    <span class="px-2 py-1 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-100">
                        {{ item.p3k_type?.name }}
                    </span>
                </template>

                <template #cell-location="{ item }">
                    <div class="text-[11px]">
                        <p class="font-bold text-gray-700">{{ item.room?.name || 'Belum diatur' }}</p>
                        <p class="text-gray-400">
                            {{ item.room?.floor?.building?.name }} - {{ item.room?.floor?.name }}
                        </p>
                    </div>
                </template>

                <template #cell-status="{ item }">
                    <div :class="[
                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border',
                        item.status === 'safe' ? 'bg-green-50 text-green-600 border-green-100' : 
                        item.status === 'warning' ? 'bg-orange-50 text-orange-600 border-orange-100' : 
                        'bg-red-50 text-red-600 border-red-100'
                    ]">
                        <span class="w-1.5 h-1.5 rounded-full fill-current" :class="item.status === 'safe' ? 'bg-green-500' : 'bg-red-500'"></span>
                        {{ item.status }}
                    </div>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end gap-1">
                        <button class="p-2 text-gray-400 hover:text-orange-500 hover:bg-orange-50 rounded-md">
                            <MapPin class="w-4 h-4" />
                        </button>
                        <button @click="openEditModal(item)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md">
                            <Pencil class="w-4 h-4" />
                        </button>
                        <button @click="deleteP3k(item.id, item.code)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-6">
                <h2 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <BriefcaseMedical class="w-5 h-5 text-emerald-500" />
                    {{ isEditing ? 'Edit Data P3K' : 'Tambah P3K Baru' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel value="Kode P3K" />
                        <TextInput v-model="form.code" class="w-full" placeholder="Misal: P3K-L1-001" />
                        <InputError :message="form.errors.code" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Tipe Kotak" />
                            <select v-model="form.p3k_type_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                                <option value="" disabled>Pilih Tipe</option>
                                <option v-for="type in p3k_types" :key="type.id" :value="type.id">{{ type.name }}</option>
                            </select>
                            <InputError :message="form.errors.p3k_type_id" />
                        </div>
                        <div>
                            <InputLabel value="Status" />
                            <select v-model="form.status" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                                <option value="safe">Safe</option>
                                <option value="warning">Warning</option>
                                <option value="critical">Critical</option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Lokasi Ruangan" />
                        <select v-model="form.room_id" class="w-full border-gray-300 rounded-lg text-sm focus:ring-indigo-500">
                            <option value="" disabled>Pilih Ruangan</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.floor?.building?.name }} - {{ room.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.room_id" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" @click="showModal = false" class="text-sm font-semibold text-gray-500 hover:text-gray-700">
                            Batal
                        </button>
                        <PrimaryButton :disabled="form.processing">
                            <Save class="w-4 h-4 mr-2" /> Simpan Data
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>