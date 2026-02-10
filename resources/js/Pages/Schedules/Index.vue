<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { 
    Plus, Pencil, Trash2, 
    CalendarClock, Flame, Droplet, BriefcaseMedical, 
    Repeat, Calendar, MapPin, Building, Globe, CheckSquare
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
    schedules: Object,
    filters: Object,
    buildings: Array, // <--- NEW: Received from Controller
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const isEditing = ref(false);

// --- FORM SETUP ---
const form = useForm({
    id: null,
    asset_type: 'App\\Models\\Apar', 
    months_interval: 1,
    week_rank: null, 
    start_date: new Date().toISOString().split('T')[0],
    
    // NEW: Logic for Bulk Selection
    scope: 'global', // 'global' or 'building'
    building_ids: [], // Array [1, 2, 5]
});

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
});

// Search Logic
watch(search, debounce((value) => {
    router.get(route('schedules.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

// --- MODAL LOGIC ---

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    // Reset to sensible defaults
    form.scope = 'global';
    form.building_ids = [];
    showModal.value = true;
};

// Note: Edit mode usually only edits the SCHEDULE RULE, not the Scope
// Because 'Scope' is a generator tool.
const openEditModal = (item) => {
    isEditing.value = true;
    form.clearErrors();
    form.id = item.id;
    form.asset_type = item.assetable_type;
    // For single edit, we don't use scope/buildings
    form.months_interval = item.months_interval;
    form.week_rank = item.week_rank;
    form.start_date = item.next_run_date; 
    showModal.value = true;
};

const submit = () => {
    const action = isEditing.value 
        ? route('schedules.update', form.id) 
        : route('schedules.store'); // Calls the Bulk Generator
    
    const method = isEditing.value ? 'put' : 'post';

    form[method](action, {
        onSuccess: () => {
            showModal.value = false;
            toast.fire({ 
                icon: 'success', 
                title: isEditing.value ? 'Jadwal diperbarui' : 'Jadwal berhasil dibuat' 
            });
        }
    });
};

const deleteSchedule = (id) => {
    Swal.fire({
        title: 'Hapus Jadwal?',
        text: "Robot tidak akan membuat tugas inspeksi otomatis lagi untuk aset ini.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        confirmButtonColor: '#ef4444',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('schedules.destroy', id), {
                onSuccess: () => toast.fire({ icon: 'success', title: 'Jadwal dihapus' })
            });
        }
    });
};

// --- HELPER UI ---
const columns = [
    { label: 'Target Inspeksi', key: 'target', class: 'w-1/3' },
    { label: 'Jadwal & Frekuensi', key: 'frequency' },
    { label: 'Generate Berikutnya', key: 'next_run' },
    { label: 'Aksi', key: 'action', class: 'text-right' },
];

const getAssetIcon = (type) => {
    if (type.includes('Apar')) return Flame;
    if (type.includes('Hydrant')) return Droplet;
    if (type.includes('P3k')) return BriefcaseMedical;
    return CalendarClock;
};

const getAssetName = (type) => {
    return type.split('\\').pop().toUpperCase();
};
</script>

<template>
    <Head title="Manajemen Jadwal Inspeksi" />

    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-ink dark:text-ink-dark leading-tight">
                    Manajemen Jadwal Otomatis
                </h2>
            </div>
        </template>

        <Card no-padding>
            <template #header>
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <SearchInput v-model="search" placeholder="Cari aset..." class="w-full md:w-64" />
                    
                    <button @click="openCreateModal" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-md flex items-center gap-2 shadow-sm transition-all">
                        <Plus class="w-4 h-4" /> Buat Jadwal Baru
                    </button>
                </div>
            </template>

            <DataTable :items="schedules" :columns="columns">
                
                <template #cell-target="{ item }">
                    <div class="flex items-start gap-3 py-1">
                        <div class="p-2 rounded-lg bg-gray-50 border border-gray-100 shrink-0">
                            <component :is="getAssetIcon(item.assetable_type)" class="w-5 h-5 text-gray-600" />
                        </div>
                        <div>
                            <div class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                {{ getAssetName(item.assetable_type) }}
                                <span class="bg-gray-100 text-gray-500 text-[10px] px-1.5 py-0.5 rounded border">
                                    #{{ item.assetable_id }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1 flex items-start gap-1">
                                <MapPin class="w-3 h-3 mt-0.5 shrink-0" :class="item.assetable?.room ? 'text-gray-400' : 'text-red-300'" />
                                
                                <div v-if="item.assetable?.room">
                                    <span class="font-medium text-gray-700">
                                        {{ item.assetable.room.floor?.building?.name || 'Gedung ?' }}
                                    </span>
                                    <span class="mx-1">/</span>
                                    <span>
                                        {{ item.assetable.room.name }} ({{ item.assetable.room.floor?.name || '-' }})
                                    </span>
                                </div>
                                
                                <span v-else class="text-red-400 italic font-light">
                                    Lokasi ruangan belum diatur
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <template #cell-frequency="{ item }">
                    <div class="flex flex-col gap-1.5 py-1">
                        <div class="text-sm font-medium text-gray-800 flex items-center gap-2">
                            <Repeat class="w-4 h-4 text-gray-400" />
                            <span v-if="item.months_interval === 1">
                                Diulang <span class="font-bold text-indigo-600">Setiap Bulan</span>
                            </span>
                            <span v-else>
                                Diulang <span class="font-bold text-indigo-600">Setiap {{ item.months_interval }} Bulan</span>
                            </span>
                        </div>
                        <div class="ml-6">
                            <div v-if="item.week_rank" 
                                class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                <CalendarClock class="w-3 h-3 mr-1.5" />
                                Target: Minggu ke-{{ item.week_rank }}
                            </div>
                        </div>
                    </div>
                </template>

                <template #cell-next_run="{ item }">
                    <div class="text-xs">
                        <div class="font-mono font-semibold text-gray-800">
                            {{ new Date(item.next_run_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                        </div>
                        <span class="text-[10px] text-gray-500">Auto-generate date</span>
                    </div>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end gap-1">
                        <button @click="openEditModal(item)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-md transition-colors" title="Edit Jadwal">
                            <Pencil class="w-4 h-4" />
                        </button>
                        <button @click="deleteSchedule(item.id)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors" title="Hapus Jadwal">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>

            </DataTable>
        </Card>

        <Modal :show="showModal" @close="showModal = false" max-width="lg">
            <div class="p-6">
                <h2 class="text-lg font-bold mb-6 flex items-center gap-2 text-gray-800 border-b pb-4">
                    <CalendarClock class="w-5 h-5 text-blue-600" />
                    {{ isEditing ? 'Edit Aturan Jadwal' : 'Buat Jadwal Massal' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-6">
                    
                    <div v-if="!isEditing">
                        <InputLabel value="Jenis Aset" />
                        <div class="grid grid-cols-3 gap-3 mt-1">
                            <label class="cursor-pointer">
                                <input type="radio" v-model="form.asset_type" value="App\Models\Apar" class="peer sr-only">
                                <div class="p-3 rounded-lg border text-center peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-gray-50">
                                    <Flame class="w-6 h-6 mx-auto mb-1" />
                                    <span class="text-xs font-bold">APAR</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" v-model="form.asset_type" value="App\Models\Hydrant" class="peer sr-only">
                                <div class="p-3 rounded-lg border text-center peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-gray-50">
                                    <Droplet class="w-6 h-6 mx-auto mb-1" />
                                    <span class="text-xs font-bold">Hydrant</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" v-model="form.asset_type" value="App\Models\P3k" class="peer sr-only">
                                <div class="p-3 rounded-lg border text-center peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 hover:bg-gray-50">
                                    <BriefcaseMedical class="w-6 h-6 mx-auto mb-1" />
                                    <span class="text-xs font-bold">P3K</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div v-if="!isEditing" class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <InputLabel value="Terapkan Pada:" class="mb-3 text-gray-800" />

                        <label class="flex items-start gap-3 mb-3 cursor-pointer">
                            <input type="radio" v-model="form.scope" value="global" class="mt-1">
                            <div>
                                <div class="flex items-center gap-2 font-bold text-sm text-gray-700">
                                    <Globe class="w-4 h-4 text-blue-500" />
                                    Semua Gedung (Global)
                                </div>
                                <p class="text-xs text-gray-500">Jadwalkan untuk seluruh aset di pabrik.</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="radio" v-model="form.scope" value="building" class="mt-1">
                            <div>
                                <div class="flex items-center gap-2 font-bold text-sm text-gray-700">
                                    <Building class="w-4 h-4 text-orange-500" />
                                    Pilih Gedung Tertentu
                                </div>
                                <p class="text-xs text-gray-500">Pilih satu atau lebih gedung spesifik.</p>
                            </div>
                        </label>

                        <div v-if="form.scope === 'building'" class="mt-3 ml-7 p-3 bg-white border rounded-md max-h-40 overflow-y-auto">
                            <div v-if="buildings.length === 0" class="text-xs text-red-500">Data gedung kosong.</div>
                            <div v-for="b in buildings" :key="b.id" class="flex items-center gap-2 mb-2 last:mb-0">
                                <input type="checkbox" :value="b.id" v-model="form.building_ids" class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                <span class="text-sm text-gray-700">{{ b.name }}</span>
                            </div>
                        </div>
                        <InputError :message="form.errors.building_ids" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Interval (Bulan)" />
                            <TextInput v-model="form.months_interval" type="number" min="1" class="w-full text-center font-bold" />
                            <p class="text-[10px] text-gray-500 mt-1">Contoh: 1 = Tiap bulan.</p>
                        </div>
                        <div>
                            <InputLabel value="Start Date" />
                            <TextInput v-model="form.start_date" type="date" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Target Pengerjaan (Mingguan)" class="mb-2" />
                        <div class="grid grid-cols-5 gap-2">
                            <div @click="form.week_rank = null"
                                :class="['cursor-pointer rounded border py-2 text-center text-xs transition-all', form.week_rank === null ? 'bg-blue-600 text-white font-bold' : 'bg-white hover:bg-gray-50']">
                                Bebas
                            </div>
                            <div v-for="i in 4" :key="i" @click="form.week_rank = i"
                                :class="['cursor-pointer rounded border py-2 text-center text-xs transition-all', form.week_rank === i ? 'bg-blue-600 text-white font-bold' : 'bg-white hover:bg-gray-50']">
                                Mgg {{ i }}
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t mt-4">
                        <button type="button" @click="showModal = false" class="text-sm font-semibold text-gray-500 hover:text-gray-700">
                            Batal
                        </button>
                        <PrimaryButton :disabled="form.processing">
                            {{ isEditing ? 'Simpan Perubahan' : 'Generate Jadwal' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>