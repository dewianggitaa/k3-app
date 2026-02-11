<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { 
    Pencil, Trash2, Plus, X, Save, Building2, Hash
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
    buildings: Object,
    filters: Object,
});

// --- STATE & LOGIC ---
const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    code: '',
    name: '',
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
    router.get(route('buildings.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

// Modal Actions
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (building) => {
    isEditing.value = true;
    form.clearErrors();
    form.id = building.id;
    form.code = building.code;
    form.name = building.name;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('buildings.update', form.id), {
            onSuccess: () => {
                closeModal();
                toast.fire({ icon: 'success', title: 'Data gedung diperbarui' });
            },
            onError: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Update',
                    text: 'Cek kembali inputan kamu.',
                });
            }
        });
    } else {
        form.post(route('buildings.store'), {
            onSuccess: () => {
                closeModal();
                toast.fire({ icon: 'success', title: 'Gedung berhasil ditambahkan' });
            },
            onError: () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Simpan',
                    text: 'Pastikan kode dan nama gedung sudah benar.',
                });
            }
        });
    }
};

const deleteBuilding = (id, name) => {
    Swal.fire({
        title: 'Hapus Gedung?',
        text: `Gedung "${name}" dan semua data lantai/ruangan di dalamnya akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('buildings.destroy', id), {
                onSuccess: () => {
                    toast.fire({ icon: 'success', title: 'Gedung berhasil dihapus' });
                }
            });
        }
    });
};

const columns = [
    { label: 'No', key: 'no', class: 'w-12 text-center' },
    { label: 'Kode Gedung', key: 'code', class: 'w-32' },
    { label: 'Nama Gedung', key: 'name', class: 'font-medium' },
    { label: '', key: 'action', class: 'w-24 text-right' },
];
</script>

<template>
    <Head title="Master Data Gedung" />

    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-ink dark:text-ink-dark leading-tight">
                    Master Data Gedung
                </h2>
            </div>
        </template>

        <Card no-padding className="h-full">
            <template #header>
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full p-4">
                    <div class="w-full sm:w-64">
                        <SearchInput v-model="search" placeholder="Cari gedung..." />
                    </div>

                    <button 
                        @click="openCreateModal"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-md shadow-sm flex items-center gap-2 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Gedung</span>
                    </button>
                </div>
            </template>

            <DataTable :items="buildings" :columns="columns">
                <template #cell-no="{ index }">
                    <span class="text-gray-400 font-mono text-[10px]">
                        {{ (buildings.current_page - 1) * buildings.per_page + index + 1 }}
                    </span>
                </template>

                <template #cell-code="{ item }">
                    <span class="px-2 py-1 rounded-md text-[10px] font-mono bg-gray-100 text-gray-700 border border-gray-200 tracking-wide uppercase">
                        {{ item.code || 'N/A' }}
                    </span>
                </template>

                <template #cell-name="{ item }">
                    <span class="text-sm text-gray-700 font-medium">{{ item.name }}</span>
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
                            @click="deleteBuilding(item.id, item.name)"
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
                        <Building2 class="w-5 h-5" />
                        <h2 class="text-lg font-bold text-gray-900">
                            {{ isEditing ? 'Edit Gedung' : 'Tambah Gedung' }}
                        </h2>
                    </div>
                    <button @click="closeModal" class="p-1 rounded-full hover:bg-gray-100 transition-colors">
                        <X class="w-5 h-5 text-gray-400" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel value="Kode Gedung" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Hash class="h-4 w-4 text-gray-400" />
                            </div>
                            <TextInput 
                                v-model="form.code"
                                type="text"
                                class="w-full pl-10"
                                placeholder="Contoh: GD-01"
                                required
                            />
                        </div>
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Nama Gedung" class="mb-1 text-[10px] uppercase tracking-widest text-gray-400" />
                        <TextInput 
                            v-model="form.name"
                            type="text"
                            class="w-full"
                            placeholder="Contoh: Gedung Utama / Head Office"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
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
                            {{ isEditing ? 'Update Gedung' : 'Simpan Gedung' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>