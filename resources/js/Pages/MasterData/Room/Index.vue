<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2'; 
import { 
    Pencil, Trash2, Plus, MapPin, Hash, 
    X, Save, Box, Palette, ChevronRight, Map 
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

const colorPresets = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', 
    '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', 
    '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', 
    '#ec4899', '#f43f5e', '#71717a', '#78350f', '#134e4a'
];

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
    form.color = room.color || '#3b82f6';
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
    { label: 'No', key: 'no', class: 'w-12 text-center' },
    { label: 'Lokasi', key: 'location', class: 'w-40' },
    { label: 'Kode Ruangan', key: 'code', class: 'font-medium'},
    { label: 'Nama Ruangan', key: 'name', class: 'font-medium' },
    { label: 'PIC Area', key: 'pic', class: 'font-medium' },
    { label: 'Warna', key: 'color', class: 'w-24 text-center' },
    { label: 'Status Mapping', key: 'coordinates', class: 'w-48 text-center' },
    { label: '', key: 'action', class: 'w-24 text-right' },
];
</script>

<template>
    <Head title="Master Data Ruangan" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center justify-between w-full">
                
                <div class="flex items-center gap-4">
                    <div>
                        <h2 class="font-bold text-lg text-ink dark:text-ink-dark leading-tight">Master Data Ruangan</h2>
                    </div>
                </div>
            </div>
        </template>

        <Card no-padding className="h-full">
            <template #header>
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                    <div class="w-full sm:w-64">
                        <SearchInput v-model="search" placeholder="Cari ruangan, lantai, atau gedung..." />
                    </div>

                    <button 
                        @click="openCreateModal"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-md shadow-sm flex items-center gap-2 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Ruangan</span>
                    </button>
                </div>
            </template>

            <DataTable :items="rooms" :columns="columns">
                <template #cell-no="{ index }">
                    <span class="text-gray-400 font-mono text-[10px]">
                        {{ (rooms.current_page - 1) * rooms.per_page + index + 1 }}
                    </span>
                </template>

                <template #cell-location="{ item }">
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-tighter">
                            {{ item.floor?.building?.name || 'N/A' }}
                        </span>
                        <span class="text-[10px] text-gray-500 flex items-center gap-1">
                            <MapPin class="w-2.5 h-2.5" /> {{ item.floor?.name || 'N/A' }}
                        </span>
                    </div>
                </template>

                <template #cell-code="{ item }">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: item.color || '#ddd' }"></div>
                        <span class="text-sm text-gray-700">{{ item.code }}</span>
                    </div>
                </template>
                
                <template #cell-name="{ item }">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-700">{{ item.name }}</span>
                    </div>
                </template>

                <template #cell-pic="{ item }">
                    <div class="flex items-center gap-2">
                        {{ item.pic?.name || '-' }}
                    </div>
                </template>

                <template #cell-color="{ item }">
                    <div class="flex justify-center">
                        <div 
                            v-if="item.color"
                            class="w-5 h-5 rounded border border-white shadow-sm ring-1 ring-gray-200"
                            :style="{ backgroundColor: item.color }"
                        ></div>
                        <span v-else class="text-gray-300">-</span>
                    </div>
                </template>

                <template #cell-coordinates="{ item }">
                    <div class="flex justify-center">
                        <Link 
                            v-if="item.coordinates" 
                            :href="route('floors.mapping', { id: item.floor_id, highlight_room: item.id })"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 uppercase text-[10px] font-bold transition-all group"
                            title="Lihat di Denah"
                        >
                            <span>SUDAH DIMAPPING</span>
                            <Map class="w-3 h-3 group-hover:scale-110 transition-transform" />
                        </Link>
                        
                        <Link 
                            v-else 
                            :href="route('floors.mapping', { id: item.floor_id, highlight_room: item.id })"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-50 hover:bg-gray-100 text-gray-400 hover:text-gray-600 border border-gray-200 uppercase text-[10px] font-bold transition-all group"
                            title="Mapping Ruangan Ini"
                        >
                            <span>BELUM ADA AREA</span>
                            <Plus class="w-3 h-3 group-hover:scale-110 transition-transform" />
                        </Link>
                    </div>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end items-center gap-1">
                        <button 
                            @click="openEditModal(item)"
                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors"
                        >
                            <Pencil class="w-4 h-4" />
                        </button>
                        <button 
                            @click="deleteRoom(item.id, item.name)"
                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <Modal :show="showModal" @close="closeModal" max-width="md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <div class="flex items-center gap-2 text-indigo-600">
                        <Box class="w-5 h-5" />
                        <h2 class="text-lg font-bold text-gray-900">
                            {{ isEditing ? 'Edit Ruangan' : 'Tambah Ruangan' }}
                        </h2>
                    </div>
                    <button @click="closeModal" class="p-1 rounded-full hover:bg-gray-100 transition-colors">
                        <X class="w-5 h-5 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel value="Pilih Lantai / Lokasi" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        <select 
                            v-model="form.floor_id"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5"
                        >
                            <option value="" disabled>Pilih Lantai Gedung...</option>
                            <option v-for="floor in floors" :key="floor.id" :value="floor.id">
                                {{ floor.building?.name }} - {{ floor.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.floor_id" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Kode Ruangan" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        <TextInput 
                            v-model="form.code"
                            type="text"
                            class="w-full"
                            placeholder="Contoh: 1511000"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Nama Ruangan" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        <TextInput 
                            v-model="form.name"
                            type="text"
                            class="w-full"
                            placeholder="Contoh: Corridor A"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="PIC Area (Penanggung Jawab)" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        
                        <Dropdown align="left" width="full" contentClasses="py-1 bg-white max-h-60 overflow-y-auto">
                            
                            <template #trigger>
                                <button
                                    type="button"
                                    class="w-full bg-white border border-gray-300 rounded-lg shadow-sm px-4 py-2.5 text-start text-sm flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition ease-in-out duration-150"
                                    :class="!form.pic_user_id ? 'text-gray-500' : 'text-gray-900'"
                                >
                                    <span class="truncate block mr-2">
                                        {{ selectedUserLabel }}
                                    </span>
                                    
                                    <svg class="h-4 w-4 text-gray-500 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <button
                                    type="button"
                                    @click="selectUser(null)"
                                    class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    :class="{ 'bg-gray-50 font-bold': form.pic_user_id === null }"
                                >
                                    -- Tidak Ada / Belum Ditentukan --
                                </button>

                                <button
                                    v-for="user in users"
                                    :key="user.id"
                                    type="button"
                                    @click="selectUser(user.id)"
                                    class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out border-t border-gray-50"
                                    :class="{ 'bg-indigo-50 text-indigo-700 font-bold': form.pic_user_id === user.id }"
                                >
                                    {{ user.name }} 
                                    <span class="text-xs text-gray-400 ml-1">({{ user.position?.name || 'Staff' }})</span>
                                </button>
                            </template>
                        </Dropdown>

                        <p class="text-[10px] text-gray-500 mt-1">
                            *Orang ini akan menerima notifikasi tugas otomatis untuk aset di ruangan ini.
                        </p>
                        <InputError :message="form.errors.pic_user_id" class="mt-1" />
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <InputLabel value="Warna Area (Peta)" class="text-[10px] uppercase tracking-widest text-gray-400" />
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-mono text-gray-400 uppercase">{{ form.color }}</span>
                                <div class="w-4 h-4 rounded-full border border-gray-200" :style="{ backgroundColor: form.color }"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-10 gap-2 p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <button 
                                v-for="color in colorPresets" 
                                :key="color"
                                type="button"
                                @click="form.color = color"
                                :class="[
                                    'w-6 h-6 rounded-md transition-all hover:scale-125 shadow-sm',
                                    form.color === color ? 'ring-2 ring-indigo-500 ring-offset-2 scale-110' : 'border border-black/5'
                                ]"
                                :style="{ backgroundColor: color }"
                            ></button>
                            <div class="relative w-6 h-6 group">
                                <input type="color" v-model="form.color" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                <div class="w-6 h-6 rounded-md border border-dashed border-gray-400 flex items-center justify-center text-[10px] text-gray-500 group-hover:bg-gray-100 transition-colors">
                                    +
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t mt-6">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 transition-colors"
                        >
                            Batal
                        </button>
                        <PrimaryButton 
                            class="bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-200"
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