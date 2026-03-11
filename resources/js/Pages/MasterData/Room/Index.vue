<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2'; 
import { 
    Pencil, Trash2, Plus, MapPin, Hash, 
    X, Save, Box, Palette, ChevronRight, ChevronDown, Map 
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
import Dropdown from '@/Components/Dropdown.vue'; 

const props = defineProps({
    rooms: Object,
    filters: Object,
    floors: Array,
    users: Array,
    can: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const selectedUserLabel = computed(() => {
    if (!form.pic_user_id) return '-- Tidak Ada / Belum Ditentukan --';
    
    const user = props.users.find(u => u.id === form.pic_user_id);
    return user 
        ? `${user.name} (${user.position?.name || 'Staff'})` 
        : '-- User Tidak Ditemukan --';
});

const selectUser = (userId) => {
    form.pic_user_id = userId;
};

const form = useForm({
    id: null,
    floor_id: '',
    name: '',
    code: '',
    pic_user_id: '',
    color: '#3b82f6',
});

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

watch(search, debounce((value) => {
    router.get(route('rooms.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (room) => {
    isEditing.value = true;
    form.clearErrors();
    form.id = room.id;
    form.code = room.code;
    form.floor_id = room.floor_id; 
    form.name = room.name;
    form.color = '#3b82f6'; // Semua pakai default biru
    form.pic_user_id = room.pic_user_id;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('rooms.update', form.id), {
            onSuccess: () => {
                closeModal();
                toast.fire({ icon: 'success', title: 'Ruangan diperbarui' });
            },
            onError: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Waduh!',
                    text: 'Cek lagi inputan lu, ada yang belum lengkap.',
                });
            }
        });
    } else {
        form.post(route('rooms.store'), {
            onSuccess: () => {
                closeModal();
                toast.fire({ icon: 'success', title: 'Ruangan ditambahkan' });
            },
            onError: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Simpan',
                    text: 'Pastikan nama dan lantai sudah diisi ya!',
                });
            }
        });
    }
};

const deleteRoom = (id, name) => {
    Swal.fire({
        title: 'Apakah anda yakin?',
        text: `Ruangan "${name}" akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5', // Indigo-600
        cancelButtonColor: '#ef4444', // Red-600
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('rooms.destroy', id), {
                onSuccess: () => {
                    toast.fire({
                        icon: 'success',
                        title: 'Ruangan berhasil dihapus'
                    });
                }
            });
        }
    });
};

const columns = [
    { label: 'No', key: 'no', class: 'w-10 sm:w-12 text-center' },
    { label: 'Lokasi', key: 'location', class: 'w-32 sm:w-40 min-w-[120px]' },
    { label: 'Kode Ruangan', key: 'code', class: 'font-medium min-w-[110px]'},
    { label: 'Nama Ruangan', key: 'name', class: 'font-medium min-w-[120px]' },
    { label: 'PIC Area', key: 'pic', class: 'font-medium min-w-[100px]' },
    { label: 'Status Mapping', key: 'coordinates', class: 'w-48 text-center' },
    ...(props.can?.manage ? [{ label: 'Aksi', key: 'action', class: 'w-20 sm:w-24 text-center' }] : []),
];
</script>

<template>
    <Head title="Master Data Ruangan" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-4 px-4">
                    <div>
                        <h2 class="font-bold text-lg text-ink dark:text-ink-dark leading-tight">Master Data Ruangan</h2>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            
            <Card no-padding class="p-4 overflow-visible" overflow-visible>
                <div class="flex flex-row justify-between items-center gap-3 sm:gap-4">
                    
                    <div class="flex-1 sm:w-1/3 sm:flex-none min-w-[200px]">
                        <SearchInput v-model="search" placeholder="Cari ruangan, lantai, atau gedung..." />
                    </div>

                    <button 
                        v-if="can?.manage"
                        @click="openCreateModal"
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-md shadow-sm flex items-center justify-center sm:gap-2 transition-all h-[38px] w-[38px] px-0 sm:w-auto sm:px-4 shrink-0"
                    >
                        <Plus class="w-5 h-5 sm:w-4 sm:h-4" />
                        <span class="hidden sm:inline">Tambah Ruangan</span>
                    </button>
                    
                </div>
            </Card>

            <Card no-padding className="h-full">
                <DataTable :items="rooms" :columns="columns">
                    <template #cell-no="{ index }">
                        <span class="text-ink-light font-mono text-[10px]">
                            {{ (rooms.current_page - 1) * rooms.per_page + index + 1 }}
                        </span>
                    </template>

                    <template #cell-location="{ item }">
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-primary uppercase tracking-tighter line-clamp-1">
                                {{ item.floor?.building?.name || 'N/A' }}
                            </span>
                            <span class="text-[10px] text-ink-light flex items-center gap-1">
                                <MapPin class="w-2.5 h-2.5 shrink-0" /> <span class="line-clamp-1">{{ item.floor?.name || 'N/A' }}</span>
                            </span>
                        </div>
                    </template>

                    <template #cell-code="{ item }">
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></div>
                            <span class="text-sm text-ink dark:text-ink-dark/90">{{ item.code }}</span>
                        </div>
                    </template>
                    
                    <template #cell-name="{ item }">
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-ink dark:text-ink-dark/90 line-clamp-2">{{ item.name }}</span>
                        </div>
                    </template>

                    <template #cell-pic="{ item }">
                        <div class="flex items-center gap-2 text-sm line-clamp-2">
                            {{ item.pic?.name || '-' }}
                        </div>
                    </template>

                    <template #cell-coordinates="{ item }">
                        <div class="flex justify-center">
                            <Link 
                                v-if="item.coordinates" 
                                :href="route('floors.mapping', { id: item.floor_id, highlight_room: item.id })"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-success/10 hover:bg-success/20 text-success border border-success/30 uppercase text-[10px] font-bold transition-all group"
                                title="Lihat di Denah"
                            >
                                <span class="whitespace-nowrap">SUDAH DIMAPPING</span>
                                <Map class="w-3 h-3 group-hover:scale-110 transition-transform shrink-0" />
                            </Link>
                            
                            <Link 
                                v-else 
                                :href="route('floors.mapping', { id: item.floor_id, highlight_room: item.id })"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-ghost hover:bg-ghost text-ink-light hover:text-ink-light border border-ghost-hover uppercase text-[10px] font-bold transition-all group"
                                title="Mapping Ruangan Ini"
                            >
                                <span class="whitespace-nowrap">BELUM ADA AREA</span>
                                <Plus class="w-3 h-3 group-hover:scale-110 transition-transform shrink-0" />
                            </Link>
                        </div>
                    </template>

                    <template #cell-action="{ item }">
                        <div v-if="can?.manage" class="flex justify-end items-center gap-0.5 sm:gap-1">
                            <button 
                                @click="openEditModal(item)"
                                class="p-1.5 sm:p-2 text-ink-light hover:text-primary hover:bg-primary/10 rounded-md transition-colors"
                            >
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button 
                                @click="deleteRoom(item.id, item.name)"
                                class="p-1.5 sm:p-2 text-ink-light hover:text-danger hover:bg-danger/10 rounded-md transition-colors"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </Card>

        </div>

        <Modal :show="showModal" @close="closeModal" max-width="md">
            <div class="p-4 sm:p-5">
                <div class="flex justify-between items-center mb-5 sm:mb-6 border-b pb-4">
                    <div class="flex items-center gap-2 text-primary">
                        <Box class="w-5 h-5" />
                        <h2 class="text-base sm:text-lg font-bold text-ink">
                            {{ isEditing ? 'Edit Ruangan' : 'Tambah Ruangan' }}
                        </h2>
                    </div>
                    <button @click="closeModal" class="p-1.5 rounded-full hover:bg-ghost transition-colors">
                        <X class="w-4 h-4 sm:w-5 sm:h-5 text-ink-light" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                    <div>
                        <InputLabel value="Pilih Lantai / Lokasi" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <Dropdown align="left" width="full" contentClasses="py-1 bg-surface max-h-60 overflow-y-auto">
                            <template #trigger>
                                <button type="button" class="appearance-none w-full px-3 py-2 sm:py-2.5 text-sm rounded-md border border-ghost dark:border-ghost-dark bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none text-left flex justify-between items-center transition-colors">
                                    <span class="truncate">
                                        <template v-if="form.floor_id">
                                            {{ floors.find(f => f.id === form.floor_id)?.building?.name }} - {{ floors.find(f => f.id === form.floor_id)?.name }}
                                        </template>
                                        <template v-else>
                                            Pilih Lantai Gedung...
                                        </template>
                                    </span>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                        <ChevronDown class="w-4 h-4" />
                                    </div>
                                </button>
                            </template>
                            <template #content>
                                <div class="py-1 max-h-48 overflow-y-auto">
                                    <button type="button" @click="form.floor_id = ''" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer text-ink-light">
                                        Pilih Lantai Gedung...
                                    </button>
                                    <button type="button" v-for="floor in floors" :key="floor.id" @click="form.floor_id = floor.id" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.floor_id === floor.id }">
                                        {{ floor.building?.name }} - {{ floor.name }}
                                    </button>
                                </div>
                            </template>
                        </Dropdown>
                        <InputError :message="form.errors.floor_id" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Kode Ruangan" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <TextInput 
                            v-model="form.code"
                            type="text"
                            class="w-full text-sm"
                            placeholder="Contoh: 1511000"
                            required
                        />
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Nama Ruangan" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <TextInput 
                            v-model="form.name"
                            type="text"
                            class="w-full text-sm"
                            placeholder="Contoh: Corridor A"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="PIC Area (Penanggung Jawab)" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        
                        <Dropdown align="left" width="full" contentClasses="py-1 bg-surface max-h-60 overflow-y-auto">
                            
                            <template #trigger>
                                <button
                                    type="button"
                                    class="w-full bg-surface border border-ghost-hover rounded-md shadow-sm px-3 py-2 sm:py-2.5 text-start text-sm flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition ease-in-out duration-150"
                                    :class="!form.pic_user_id ? 'text-ink-light' : 'text-ink'"
                                >
                                    <span class="truncate block mr-2">
                                        {{ selectedUserLabel }}
                                    </span>
                                    
                                    <svg class="h-4 w-4 text-ink-light flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <button
                                    type="button"
                                    @click="selectUser(null)"
                                    class="block w-full px-4 py-2 text-start text-sm leading-5 text-ink dark:text-ink-dark/90 hover:bg-ghost focus:outline-none focus:bg-ghost transition duration-150 ease-in-out"
                                    :class="{ 'bg-ghost font-bold': form.pic_user_id === null }"
                                >
                                    -- Tidak Ada / Belum Ditentukan --
                                </button>

                                <button
                                    v-for="user in users"
                                    :key="user.id"
                                    type="button"
                                    @click="selectUser(user.id)"
                                    class="block w-full px-4 py-2 text-start text-sm leading-5 text-ink dark:text-ink-dark/90 hover:bg-ghost focus:outline-none focus:bg-ghost transition duration-150 ease-in-out border-t border-gray-50"
                                    :class="{ 'bg-primary/10 text-primary font-bold': form.pic_user_id === user.id }"
                                >
                                    {{ user.name }} 
                                    <span class="text-xs text-ink-light ml-1">({{ user.position?.name || 'Staff' }})</span>
                                </button>
                            </template>
                        </Dropdown>

                        <p class="text-[10px] text-ink-light mt-1">
                            *Orang ini akan menerima notifikasi tugas otomatis untuk aset di ruangan ini.
                        </p>
                        <InputError :message="form.errors.pic_user_id" class="mt-1" />
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t mt-6">
                        <button 
                            type="button" 
                            @click="closeModal" 
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
                            {{ isEditing ? 'Update Ruangan' : 'Simpan Ruangan' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>