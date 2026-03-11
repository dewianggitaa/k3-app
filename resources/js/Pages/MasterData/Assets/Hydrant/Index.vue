<script setup>
import { ref, watch } from 'vue';

import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { 
    Pencil, Trash2, Plus, MapPin, Save, X, 
    Droplets, ClipboardList, Activity, Settings, ChevronLeft 
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
    hydrants: Object,
    hydrant_types: Array,
    rooms: Array,     
    filters: Object,
    can: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    code: '',
    hydrant_type_id: '',
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

watch(search, debounce((value) => {
    router.get(route('hydrants.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (hydrant) => {
    isEditing.value = true;
    form.id = hydrant.id;
    form.code = hydrant.code;
    form.hydrant_type_id = hydrant.hydrant_type_id;
    form.room_id = hydrant.room_id;
    form.status = hydrant.status;
    form.location_data = hydrant.location_data;
    showModal.value = true;
};

const submit = () => {
    const action = isEditing.value 
        ? route('hydrants.update', form.id) 
        : route('hydrants.store');
    
    const method = isEditing.value ? 'put' : 'post';

    form[method](action, {
        onSuccess: () => {
            showModal.value = false;
            toast.fire({ 
                icon: 'success', 
                title: isEditing.value ? 'Hydrant diperbarui' : 'Hydrant ditambahkan' 
            });
        }
    });
};

const deleteHydrant = (id, code) => {
    Swal.fire({
        title: 'Hapus Hydrant?',
        text: `Hydrant ${code} akan dihapus secara permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('hydrants.destroy', id), {
                onSuccess: () => toast.fire({ icon: 'success', title: 'Berhasil dihapus' })
            });
        }
    });
};

const columns = [
    { label: 'No', key: 'no', class: 'w-10 sm:w-12 text-center' },
    { label: 'Kode Hydrant', key: 'code', class: 'font-bold min-w-[120px]' },
    { label: 'Tipe', key: 'type', class: 'min-w-[100px]' },
    { label: 'Lokasi', key: 'location', class: 'min-w-[150px]' },
    { label: 'Status', key: 'status', class: 'min-w-[100px]' },
    ...(props.can?.manage ? [{ label: 'Aksi', key: 'action', class: 'w-24 text-center' }] : []),
];
</script>

<template>
    <Head title="Master Data Hydrant" />

    <MainLayout>
        <template #header-title>
            <div class="flex flex-col items-start w-full">
                
                <div class="flex items-center gap-4 mb-4 px-4"> 
                    <Link :href="route('dashboard')" class="p-2 -ml-2 hover:bg-ghost rounded-full transition-colors">
                        <ChevronLeft class="w-5 h-5 text-ink" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-lg text-ink leading-tight">
                            {{ route().current('apars.*') ? 'Data APAR' : (route().current('hydrants.*') ? 'Data Hydrant' : 'Data P3K') }}
                        </h2>
                    </div>
                </div>
            </div>
        </template>

        <template #header-nav>
            <nav class="flex items-center gap-4 px-4 overflow-x-auto custom-scrollbar pb-1"> 
                <NavLink 
                    :href="route('apars.index')" 
                    :active="route().current('apars.index')"
                    class="text-xs font-bold transition-all duration-200 whitespace-nowrap"
                >
                    APAR
                </NavLink>

                <NavLink 
                    :href="route('hydrants.index')" 
                    :active="route().current('hydrants.index')"
                    class="text-xs font-bold transition-all duration-200 whitespace-nowrap"
                >
                    Hydrant
                </NavLink>

                <NavLink 
                    :href="route('p3ks.index')" 
                    :active="route().current('p3ks.index')"
                    class="text-xs font-bold transition-all duration-200 whitespace-nowrap"
                >
                    P3K
                </NavLink>
            </nav>
        </template>

        <div class="space-y-4">
            
            <Card no-padding class="p-4 overflow-visible" overflow-visible>
                <div class="flex flex-row justify-between items-center gap-3 sm:gap-4">
                    
                    <div class="flex-1 sm:w-1/3 sm:flex-none min-w-[200px]">
                        <SearchInput v-model="search" placeholder="Cari kode atau tipe hydrant..." />
                    </div>

                    <button 
                        v-if="can?.manage" 
                        @click="openCreateModal" 
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-md shadow-sm flex items-center justify-center sm:gap-2 transition-all h-[38px] w-[38px] px-0 sm:w-auto sm:px-4 shrink-0"
                    >
                        <Plus class="w-5 h-5 sm:w-4 sm:h-4" /> 
                        <span class="hidden sm:inline">Tambah Hydrant</span>
                    </button>
                    
                </div>
            </Card>

            <Card no-padding className="h-full">
                <DataTable :items="hydrants" :columns="columns">
                    <template #cell-no="{ index }">
                        <span class="text-ink-light font-mono text-[10px]">
                            {{ (hydrants.current_page - 1) * hydrants.per_page + index + 1 }}
                        </span>
                    </template>

                    <template #cell-type="{ item }">
                        <span class="px-2 py-1 rounded-md bg-primary/10 text-primary text-[10px] font-bold border border-blue-100 whitespace-nowrap">
                            {{ item.type?.name }}
                        </span>
                    </template>

                    <template #cell-location="{ item }">
                        <div class="text-[11px]">
                            <p class="font-bold text-ink dark:text-ink-dark/90 line-clamp-1">{{ item.room?.name || 'Belum diatur' }}</p>
                            <p class="text-ink-light line-clamp-1">
                                {{ item.room?.floor?.building?.name }} - {{ item.room?.floor?.name }}
                            </p>
                        </div>
                    </template>

                    <template #cell-status="{ item }">
                        <div :class="[
                            'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border whitespace-nowrap',
                            item.status === 'safe' ? 'bg-success/10 text-success border-green-100' : 
                            item.status === 'warning' ? 'bg-warning/10 text-warning border-orange-100' : 
                            'bg-danger/10 text-danger border-red-100'
                        ]">
                            <span class="w-1.5 h-1.5 rounded-full fill-current" :class="item.status === 'safe' ? 'bg-success' : item.status === 'warning' ? 'bg-warning' : 'bg-danger'"></span>
                            {{ item.status }}
                        </div>
                    </template>

                    <template #cell-action="{ item }">
                        <div v-if="can?.manage" class="flex justify-end gap-0.5 sm:gap-1">
                            <div v-if="item.room?.floor_id">
                                <Link 
                                    :href="route('assets.mapping', { 
                                        floor: item.room.floor_id, 
                                        target_id: item.id, 
                                        target_type: 'hydrant' 
                                    })"
                                    class="p-1.5 sm:p-2 text-primary hover:text-primary hover:bg-primary/10 rounded-md flex items-center justify-center transition-colors"
                                    title="Atur Posisi di Peta"
                                >
                                    <MapPin class="w-4 h-4" />
                                </Link>
                            </div>
                            <div v-else class="p-1.5 sm:p-2 text-gray-300 cursor-not-allowed flex items-center justify-center" title="Lokasi belum diatur">
                                <MapPin class="w-4 h-4" />
                            </div>

                            <button @click="openEditModal(item)" class="p-1.5 sm:p-2 text-ink-light hover:text-primary hover:bg-primary/10 rounded-md transition-colors">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="deleteHydrant(item.id, item.code)" class="p-1.5 sm:p-2 text-ink-light hover:text-danger hover:bg-danger/10 rounded-md transition-colors">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </Card>

        </div>

        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-4 sm:p-5">
                <div class="flex justify-between items-center mb-5 sm:mb-6 border-b pb-4">
                    <h2 class="text-base sm:text-lg font-bold flex items-center gap-2 text-ink">
                        <Droplets class="w-5 h-5 text-primary" />
                        {{ isEditing ? 'Edit Hydrant' : 'Tambah Hydrant Baru' }}
                    </h2>
                    <button @click="showModal = false" class="p-1.5 rounded-full hover:bg-ghost transition-colors">
                        <X class="w-4 h-4 sm:w-5 sm:h-5 text-ink-light" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                    <div>
                        <InputLabel value="Kode Hydrant" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <TextInput v-model="form.code" class="w-full text-sm py-2" placeholder="Misal: HYD-001" required />
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                        <div>
                            <InputLabel value="Tipe Hydrant" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                            <select v-model="form.hydrant_type_id" class="w-full border-ghost-hover rounded-md text-sm py-2 px-3 focus:border-primary focus:ring-primary bg-surface dark:bg-page-dark text-ink dark:text-ink-dark/90" required>
                                <option value="" disabled>Pilih Tipe</option>
                                <option v-for="type in hydrant_types" :key="type.id" :value="type.id">{{ type.name }}</option>
                            </select>
                            <InputError :message="form.errors.hydrant_type_id" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Status" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                            <select v-model="form.status" class="w-full border-ghost-hover rounded-md text-sm py-2 px-3 focus:border-primary focus:ring-primary bg-surface dark:bg-page-dark text-ink dark:text-ink-dark/90" required>
                                <option value="safe">Safe</option>
                                <option value="warning">Warning</option>
                                <option value="critical">Critical</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Lokasi Ruangan" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <select v-model="form.room_id" class="w-full border-ghost-hover rounded-md text-sm py-2 px-3 focus:border-primary focus:ring-primary bg-surface dark:bg-page-dark text-ink dark:text-ink-dark/90">
                            <option value="" disabled>Pilih Ruangan (Opsional)</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.floor?.building?.name }} - {{ room.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.room_id" class="mt-1" />
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t mt-6">
                        <button 
                            type="button" 
                            @click="showModal = false" 
                            class="w-full sm:w-auto px-4 py-2 sm:py-2.5 text-sm font-semibold text-ink-light hover:text-ink transition-colors bg-ghost sm:bg-transparent rounded-md"
                        >
                            Batal
                        </button>
                        <PrimaryButton 
                            class="w-full sm:w-auto justify-center bg-primary hover:bg-primary-hover shadow-md sm:shadow-lg shadow-indigo-200 py-2 sm:py-2.5"
                            :class="{ 'opacity-25': form.processing }" 
                            :disabled="form.processing"
                        >
                            <Save class="w-4 h-4 mr-2" />
                            {{ isEditing ? 'Update Hydrant' : 'Simpan Data' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>