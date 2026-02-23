<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2'; // Tambahkan ini
import { Pencil, Trash2, Plus, Layers, X, Map, Save } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    floors: Object,
    buildings: Array,
    filters: Object,
});

// --- TOAST CONFIG ---
const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

const showModal = ref(false);
const editMode = ref(false);
const targetId = ref(null);

const form = useForm({
    building_id: '',
    name: '',
    map_image: null,
});

const openCreate = () => {
    editMode.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (item) => {
    editMode.value = true;
    targetId.value = item.id;
    form.clearErrors();
    form.building_id = item.building_id;
    form.name = item.name;
    form.map_image = null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (editMode.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put', 
        })).post(route('floors.update', targetId.value), {
            forceFormData: true,
            onSuccess: () => {
                closeModal();
                toast.fire({ icon: 'success', title: 'Data lantai diperbarui' });
            },
            onError: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Update',
                    text: 'Cek kembali inputan dan file denah kamu.',
                });
            }
        });
    } else {
        form.post(route('floors.store'), {
            forceFormData: true,
            onSuccess: () => {
                closeModal();
                toast.fire({ icon: 'success', title: 'Lantai berhasil ditambahkan' });
            },
            onError: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Simpan',
                    text: 'Pastikan semua form terisi dengan benar.',
                });
            }
        });
    }
};

const search = ref(props.filters.search || '');
watch(search, debounce((value) => {
    router.get(
        route('floors.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300));

const deleteFloor = (id, name) => {
    Swal.fire({
        title: 'Hapus Lantai?',
        text: `Lantai "${name}" dan semua data ruangan di dalamnya akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('floors.destroy', id), {
                onSuccess: () => {
                    toast.fire({ icon: 'success', title: 'Lantai berhasil dihapus' });
                }
            });
        }
    });
};

const columns = [
    { label: 'No', key: 'no', class: 'w-12 text-center' },
    { label: 'Gedung', key: 'building_name', class: 'w-48' },
    { label: 'Nama Lantai', key: 'name', class: 'font-medium' },
    { label: 'Gambar Denah', key: 'map_url', class: 'min-w-[150px]' },
    { label: 'Tinggi (px)', key: 'map_height', class: 'w-24 text-center' },
    { label: 'Lebar (px)', key: 'map_width', class: 'w-24 text-center' },
    { label: '', key: 'action', class: 'w-24 text-right' },
];
</script>

<template>
    <Head title="Master Data Lantai" />

    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-ink dark:text-ink-dark leading-tight">
                    Master Data Lantai
                </h2>
            </div>
        </template>
        
        <Card no-padding className="h-full">
            <template #header>
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full p-4">
                    <div class="w-full sm:w-64">
                        <SearchInput v-model="search" placeholder="Cari lantai atau gedung..." />
                    </div>

                    <button 
                        @click="openCreate"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-md shadow-sm flex items-center gap-2 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Lantai</span>
                    </button>
                </div>
            </template>

            <DataTable :items="floors || []" :columns="columns">
                <template #cell-no="{ index }">
                    <span class="text-gray-400 font-mono text-[10px]">
                        {{ (floors.current_page - 1) * floors.per_page + index + 1 }}
                    </span>
                </template>

                <template #cell-building_name="{ item }">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-400/40"></div>
                        <span class="font-semibold text-ink/80 dark:text-ink-dark/80 italic">
                            {{ item.building?.name }}
                        </span>
                    </div>
                </template>

                <template #cell-name="{ item }">
                    <div class="flex items-center gap-2">
                        <Layers class="w-3 h-3 text-gray-400" />
                        <span class="text-sm text-gray-700 font-medium">{{ item.name }}</span>
                    </div>
                </template>

                <template #cell-map_url="{ item }">
                    <div v-if="item.map_image_path" class="flex items-center">
                        <a 
                            :href="'/storage/floors/' + item.map_image_path" 
                            target="_blank" 
                            class="text-[10px] bg-gray-100 dark:bg-gray-800 text-indigo-600 px-2 py-0.5 rounded border border-indigo-200 hover:bg-indigo-50 transition-all font-medium"
                        >
                            Lihat Denah
                        </a>
                    </div>
                    <span v-else class="text-[10px] text-gray-400 italic">No Image</span>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end items-center gap-1">
                        <Link 
                            :href="route('floors.mapping', item.id)" 
                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors"
                            title="Mapping Ruangan"
                        >
                            <Map class="w-4 h-4" />
                        </Link>
                        <button @click="openEdit(item)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors">
                            <Pencil class="w-4 h-4" />
                        </button>
                        <button @click="deleteFloor(item.id, item.name)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors">
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
                        <Layers class="w-5 h-5" />
                        <h2 class="text-lg font-bold text-gray-900">
                            {{ editMode ? 'Edit Lantai' : 'Tambah Lantai' }}
                        </h2>
                    </div>
                    <button @click="closeModal" class="p-1 rounded-full hover:bg-gray-100 transition-colors">
                        <X class="w-5 h-5 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel value="Pilih Gedung" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        <select 
                            v-model="form.building_id" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            required
                        >
                            <option value="" disabled>Pilih Gedung</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <InputError :message="form.errors.building_id" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Nama Lantai" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        <TextInput 
                            v-model="form.name" 
                            type="text" 
                            class="w-full" 
                            placeholder="Contoh: Lantai 01 / Mezzanine" 
                            required 
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Upload Denah (PNG/JPG)" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        <input 
                            type="file" 
                            @input="form.map_image = $event.target.files[0]"
                            class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 cursor-pointer"
                        />
                        <p class="text-[10px] text-gray-400 mt-2 italic">* Kosongkan jika tidak ingin mengubah denah</p>
                        <InputError :message="form.errors.map_image" class="mt-1" />
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
                            {{ editMode ? 'Update Lantai' : 'Simpan Lantai' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>