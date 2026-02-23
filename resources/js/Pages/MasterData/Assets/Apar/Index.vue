<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { 
    Pencil, Trash2, Plus, MapPin, Save, X, 
    Flame, ClipboardList, Calendar, Weight, ChevronLeft 
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
    apars: Object,
    aparTypes: Array,
    rooms: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null, // Tambahkan ID untuk update
    code: '',
    apar_type_id: '',
    room_id: '',
    weight: '',
    last_refilled_at: '',
    expired_at: '',
});

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
});

watch(search, debounce((value) => {
    router.get(route('apars.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (apar) => {
    isEditing.value = true;
    form.clearErrors();
    form.id = apar.id;
    form.code = apar.code;
    form.apar_type_id = apar.apar_type_id;
    form.room_id = apar.room_id;
    form.weight = apar.weight;
    form.last_refilled_at = apar.last_refilled_at;
    form.expired_at = apar.expired_at;
    showModal.value = true;
};

const submit = () => {
    const action = isEditing.value 
        ? route('apars.update', form.id) 
        : route('apars.store');
    
    const method = isEditing.value ? 'put' : 'post';

    form[method](action, {
        onSuccess: () => {
            showModal.value = false;
            toast.fire({ icon: 'success', title: isEditing.value ? 'APAR diperbarui' : 'APAR ditambahkan' });
        }
    });
};

const deleteApar = (id, code) => {
    Swal.fire({
        title: 'Hapus APAR?',
        text: `APAR ${code} akan dihapus!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('apars.destroy', id), {
                onSuccess: () => toast.fire({ icon: 'success', title: 'Berhasil dihapus' })
            });
        }
    });
};

const columns = [
    { label: 'No', key: 'no', class: 'w-12 text-center' },
    { label: 'Kode APAR', key: 'code', class: 'font-bold' },
    { label: 'Jenis', key: 'type' },
    { label: 'Lokasi', key: 'location' },
    { label: 'Status', key: 'status' },
    { label: 'Masa Berlaku', key: 'expired', class: 'w-32' },
    { label: '', key: 'action', class: 'text-right' },
];
</script>

<template>
    <Head title="Master Data APAR" />

    <MainLayout>
        <template #header-title>
            <div class="flex flex-col items-start w-full">
                
                <div class="flex items-center gap-4 mb-4 px-4"> <Link :href="route('dashboard')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <ChevronLeft class="w-5 h-5 text-black" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-lg text-gray-800 leading-tight">
                            {{ route().current('apars.*') ? 'Data APAR' : (route().current('hydrants.*') ? 'Data Hydrant' : 'Data P3K') }}
                        </h2>
                    </div>
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
                    <SearchInput v-model="search" placeholder="Cari kode atau jenis APAR..." class="w-64" />
                    <button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-md flex items-center gap-2">
                        <Plus class="w-4 h-4" /> Tambah APAR
                    </button>
                </div>
            </template>

            <DataTable :items="apars" :columns="columns">
                <template #cell-no="{ index }">
                    {{ (apars.current_page - 1) * apars.per_page + index + 1 }}
                </template>

                <template #cell-type="{ item }">
                    <span class="px-2 py-1 rounded-md bg-orange-50 text-orange-700 text-[10px] font-bold border border-orange-100">
                        {{ item.apar_type?.name }}
                    </span>
                </template>

                <template #cell-location="{ item }">
                    <div class="text-[11px]">
                        <p class="font-bold text-gray-700">{{ item.room?.name || '-' }}</p>
                        <p class="text-gray-400">{{ item.room?.floor?.building?.name }} - {{ item.room?.floor?.name }}</p>
                    </div>
                </template>

                <template #cell-status="{ item }">
                    <div :class="[
                        'inline-block px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border',
                        item.status === 'safe' ? 'bg-green-50 text-green-600 border-green-100' : 'bg-red-50 text-red-600 border-red-100'
                    ]">
                        {{ item.status }}
                    </div>
                </template>

                <template #cell-expired="{ item }">
                    <span class="text-xs text-gray-600 font-mono">
                        {{ item.expired_at ? new Date(item.expired_at).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        }) : '-' }}
                    </span>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end gap-1">
                        <div v-if="item.room?.floor_id">
                            <Link 
                                :href="route('assets.mapping', { 
                                    floor: item.room.floor_id, 
                                    target_id: item.id, 
                                    target_type: 'apar' 
                                })"
                                class="p-2 text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md flex items-center justify-center transition-colors"
                                title="Atur Posisi di Peta"
                            >
                                <MapPin class="w-4 h-4" />
                            </Link>
                        </div>
                        <div v-else class="p-2 text-gray-300 cursor-not-allowed" title="Lokasi belum diatur">
                            <MapPin class="w-4 h-4" />
                        </div>

                        <button @click="openEditModal(item)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors">
                            <Pencil class="w-4 h-4" />
                        </button>
                        
                        <button @click="deleteApar(item.id, item.code)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-6">
                <h2 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <Flame class="w-5 h-5 text-orange-500" />
                    {{ isEditing ? 'Edit APAR' : 'Tambah APAR Baru' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel value="Kode APAR" />
                        <TextInput v-model="form.code" class="w-full" placeholder="Misal: APAR-G1-01" />
                        <InputError :message="form.errors.code" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Jenis APAR" />
                            <select v-model="form.apar_type_id" class="w-full border-gray-300 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="" disabled>Pilih Jenis</option>
                                <option v-for="type in aparTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                            </select>
                            <InputError :message="form.errors.apar_type_id" />
                        </div>
                        <div>
                            <InputLabel value="Berat (kg)" />
                            <TextInput v-model="form.weight" type="number" class="w-full" />
                            <InputError :message="form.errors.weight" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Lokasi Ruangan" />
                        <select v-model="form.room_id" class="w-full border-gray-300 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" disabled>Pilih Ruangan</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.floor?.building?.name }} - {{ room.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.room_id" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Terakhir Isi" />
                            <TextInput v-model="form.last_refilled_at" type="date" class="w-full" />
                            <InputError :message="form.errors.last_refilled_at" />
                        </div>
                        <div>
                            <InputLabel value="Kadaluarsa" />
                            <TextInput v-model="form.expired_at" type="date" class="w-full" />
                            <InputError :message="form.errors.expired_at" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showModal = false" class="text-sm font-semibold text-gray-500 hover:text-gray-700">Batal</button>
                        <PrimaryButton :disabled="form.processing">
                            <Save class="w-4 h-4 mr-2" /> Simpan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>