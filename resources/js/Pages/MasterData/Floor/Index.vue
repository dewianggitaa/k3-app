<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { Pencil, Trash2, Plus, Layers, X, Map, Save, ChevronDown, Filter, Building2 } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    floors: Object,
    buildings: Array,
    filters: Object,
    can: Object,
});

const showFilters = ref(false);
const building = ref(props.filters?.building || 'all');
const search = ref(props.filters?.search || '');

const applyFilters = debounce(() => {
    router.get(
        route('floors.index'), 
        { 
            search: search.value || undefined,
            building: building.value !== 'all' ? building.value : undefined
        }, 
        { preserveState: true, replace: true }
    );
}, 300);

watch([search, building], () => applyFilters());

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
    { label: 'No', key: 'no', class: 'w-10 sm:w-12 text-center' },
    { label: 'Gedung', key: 'building_name', class: 'w-32 sm:w-48 min-w-[120px]' },
    { label: 'Nama Lantai', key: 'name', class: 'font-medium min-w-[120px]' },
    { label: 'Denah', key: 'map_url', class: 'min-w-[140px]' },
    { label: 'Tinggi (px)', key: 'map_height', class: 'hidden md:table-cell w-24 text-center', rowClass: 'hidden md:table-cell text-center' },
    { label: 'Lebar (px)', key: 'map_width', class: 'hidden md:table-cell w-24 text-center', rowClass: 'hidden md:table-cell text-center' },
    ...(props.can?.manage ? [{ label: 'Aksi', key: 'action', class: 'w-20 sm:w-24 text-center' }] : []),
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
        
        <div class="space-y-4">
            
            <Card no-padding class="p-4 overflow-visible" overflow-visible>
                <div class="flex flex-col xl:flex-row-reverse gap-4 justify-between items-start xl:items-center w-full">
                    
                    <button 
                        v-if="can?.manage"
                        @click="openCreate"
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-md shadow-sm flex items-center justify-center sm:gap-2 transition-all h-[38px] w-full xl:w-auto sm:px-4 shrink-0"
                    >
                        <Plus class="w-5 h-5 sm:w-4 sm:h-4" />
                        <span>Tambah Lantai</span>
                    </button>

                    <div class="flex flex-col xl:flex-row gap-4 w-full xl:w-auto xl:flex-1 items-start xl:items-center">
                        <div class="flex w-full xl:w-auto gap-2">
                            <div class="flex-1 min-w-0 xl:flex-none xl:w-64">
                                <SearchInput v-model="search" placeholder="Cari lantai atau gedung..." />
                            </div>
                            <button @click="showFilters = !showFilters" class="xl:hidden p-2 bg-ghost border border-ghost-hover hover:border-primary text-ink-light rounded-md flex items-center justify-center h-[38px] w-[38px] shrink-0 transition-colors">
                                <Filter class="w-4 h-4" />
                            </button>
                        </div>

                        <div :class="[showFilters ? 'flex' : 'hidden', 'xl:flex flex-col xl:flex-row flex-wrap gap-3 w-full xl:w-auto items-start xl:items-center']">
                            <div class="flex flex-col sm:flex-row w-full xl:w-auto gap-3 items-center pt-1 xl:pt-0">
                                <div class="w-full sm:w-48 flex-shrink-0">
                                    <Dropdown align="left" width="full">
                                        <template #trigger>
                                            <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors outline-none cursor-pointer">
                                                <div class="flex items-center gap-2 truncate whitespace-nowrap">
                                                    <Building2 class="w-3.5 h-3.5 text-ink-light shrink-0" />
                                                    <span class="truncate">{{ building === 'all' ? 'Semua Gedung' : buildings.find(b => b.id === building)?.name }}</span>
                                                </div>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                                    <ChevronDown class="w-3.5 h-3.5" />
                                                </div>
                                            </button>
                                        </template>
                                        <template #content>
                                            <div class="py-1 max-h-60 overflow-y-auto">
                                                <button @click="building = 'all'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': building === 'all' }">
                                                    Semua Gedung
                                                </button>
                                                <button v-for="b in buildings" :key="b.id" @click="building = b.id" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': building === b.id }">
                                                    {{ b.name }}
                                                </button>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </Card>

            <Card no-padding className="h-full">
                <DataTable :items="floors || []" :columns="columns">
                    <template #cell-no="{ index }">
                        <span class="text-ink-light font-mono text-[10px]">
                            {{ (floors.current_page - 1) * floors.per_page + index + 1 }}
                        </span>
                    </template>

                    <template #cell-building_name="{ item }">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-indigo-400/40 flex-shrink-0"></div>
                            <span class="font-semibold text-ink/80 dark:text-ink-dark/80 italic text-xs sm:text-sm">
                                {{ item.building?.name }}
                            </span>
                        </div>
                    </template>

                    <template #cell-name="{ item }">
                        <div class="flex items-center gap-2">
                            <Layers class="w-3 h-3 text-ink-light flex-shrink-0" />
                            <span class="text-sm text-ink dark:text-ink-dark/90 font-medium line-clamp-2">{{ item.name }}</span>
                        </div>
                    </template>

                    <template #cell-map_url="{ item }">
                        <div v-if="item.map_image_path" class="flex flex-wrap items-center gap-2">
                            <a 
                                :href="'/storage/floors/' + item.map_image_path" 
                                target="_blank" 
                                class="text-[10px] bg-ghost dark:bg-surface-dark text-primary px-2 py-1 sm:py-0.5 rounded border border-primary hover:bg-primary/10 transition-all font-medium whitespace-nowrap"
                            >
                                Gambar Denah
                            </a>

                            <Link 
                                :href="route('floors.mapping', item.id)" 
                                class="p-1.5 sm:p-2 text-ink-light hover:text-primary hover:bg-primary/10 rounded-md transition-colors"
                                title="Mapping Ruangan"
                            >
                                <Map class="w-4 h-4" />
                            </Link>
                        </div>
                        <span v-else class="text-[10px] text-ink-light italic">No Image</span>
                    </template>

                    <template #cell-action="{ item }">
                        <div v-if="can?.manage" class="flex justify-end items-center gap-0.5 sm:gap-1">
                            <button @click="openEdit(item)" class="p-1.5 sm:p-2 text-ink-light hover:text-primary hover:bg-primary/10 rounded-md transition-colors">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="deleteFloor(item.id, item.name)" class="p-1.5 sm:p-2 text-ink-light hover:text-danger hover:bg-danger/10 rounded-md transition-colors">
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
                        <Layers class="w-5 h-5" />
                        <h2 class="text-base sm:text-lg font-bold text-ink">
                            {{ editMode ? 'Edit Lantai' : 'Tambah Lantai' }}
                        </h2>
                    </div>
                    <button @click="closeModal" class="p-1.5 rounded-full hover:bg-ghost transition-colors">
                        <X class="w-4 h-4 sm:w-5 sm:h-5 text-ink-light" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                    <div>
                        <InputLabel value="Pilih Gedung" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <Dropdown align="left" width="full" contentClasses="py-1 bg-surface max-h-60 overflow-y-auto">
                            <template #trigger>
                                <button type="button" class="appearance-none w-full px-3 py-2 text-sm rounded-md border border-ghost dark:border-ghost-dark bg-page dark:bg-page-dark text-ink dark:text-ink-dark focus:ring-1 focus:ring-primary outline-none text-left flex justify-between items-center transition-colors">
                                    <span class="truncate">{{ form.building_id ? buildings.find(b => b.id === form.building_id)?.name : 'Pilih Gedung' }}</span>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                        <ChevronDown class="w-4 h-4" />
                                    </div>
                                </button>
                            </template>
                            <template #content>
                                <div class="py-1 max-h-48 overflow-y-auto">
                                    <button type="button" @click="form.building_id = ''" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer text-ink-light">
                                        Pilih Gedung
                                    </button>
                                    <button type="button" v-for="b in buildings" :key="b.id" @click="form.building_id = b.id" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.building_id === b.id }">
                                        {{ b.name }}
                                    </button>
                                </div>
                            </template>
                        </Dropdown>
                        <InputError :message="form.errors.building_id" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Nama Lantai" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <TextInput 
                            v-model="form.name" 
                            type="text" 
                            class="w-full text-sm" 
                            placeholder="Contoh: Lantai 01 / Mezzanine" 
                            required 
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Upload Denah (PNG/JPG)" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <input 
                            type="file" 
                            @input="form.map_image = $event.target.files[0]"
                            class="mt-1 block w-full text-[11px] sm:text-xs text-ink-light file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-[11px] sm:file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/10 cursor-pointer"
                        />
                        <p class="text-[10px] text-ink-light mt-1.5 italic">* Kosongkan jika tidak ingin mengubah denah</p>
                        <InputError :message="form.errors.map_image" class="mt-1" />
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
                            {{ editMode ? 'Update Lantai' : 'Simpan Lantai' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>