<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2'; // Import Swal
import { 
    Pencil, Trash2, Plus, MapPin, Hash, 
    X, Save, Box, Palette, ChevronRight 
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

const props = defineProps({
    rooms: Object,
    filters: Object,
    floors: Array,
});

// --- STATE & LOGIC ---
const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);

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
    color: '#3b82f6',
});

// Toast Notification Helper
const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

// Search Watcher
watch(search, debounce((value) => {
    router.get(route('rooms.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

// Modal Actions
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
    // Pastikan floor_id sesuai tipe data (biasanya integer dari DB)
    form.floor_id = room.floor_id; 
    form.name = room.name;
    form.color = room.color || '#3b82f6';
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
    { label: 'Lokasi (Gedung - Lantai)', key: 'location', class: 'w-64' },
    { label: 'Kode Ruangan', key: 'code', class: 'font-medium'},
    { label: 'Nama Ruangan', key: 'name', class: 'font-medium' },
    { label: 'Warna', key: 'color', class: 'w-24 text-center' },
    { label: 'Status Mapping', key: 'coordinates', class: 'w-48 text-center' },
    { label: '', key: 'action', class: 'w-24 text-right' },
];
</script>

<template>
    <Head title="Master Data Ruangan" />

    <MainLayout>
        <template #header>Master Data Ruangan</template>

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
                        <div v-if="item.coordinates" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-100 uppercase text-[9px] font-bold">
                            Sudah Dimapping
                        </div>
                        <div v-else class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-gray-50 text-gray-400 border border-gray-100 uppercase text-[9px] font-bold">
                            Belum Ada Area
                        </div>
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
                            placeholder="Contoh: Ruang Lab AI"
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
                            placeholder="Contoh: Ruang Lab AI"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
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