<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { 
    Plus, Pencil, Trash2, 
    CalendarClock, Flame, Droplet, BriefcaseMedical, 
    Repeat, Calendar, MapPin, Building, Globe, CheckSquare, Users, UserCheck
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
    buildings: Array,
    can: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    asset_type: 'App\\Models\\Apar', 
    months_interval: 1,
    week_rank: null, 
    start_date: new Date().toISOString().split('T')[0],
    assign_type: 'k3',
    scope: 'global', // 'global' or 'building'
    building_ids: [], // Array [1, 2, 5]
});

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
});

watch(search, debounce((value) => {
    router.get(route('schedules.index'), { search: value }, { preserveState: true, replace: true });
}, 300));

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    form.scope = 'global';
    form.building_ids = [];
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    form.clearErrors();
    form.id = item.id;
    form.asset_type = item.asset_type;
    form.assign_type= item.assign_type;
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
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('schedules.destroy', id), {
                onSuccess: () => toast.fire({ icon: 'success', title: 'Jadwal dihapus' })
            });
        }
    });
};

const columns = [
    { label: 'Target Inspeksi', key: 'target', class: 'w-1/3 text-center' },
    { label: 'Jadwal & Frekuensi', key: 'frequency', class:'text-center' },
    { label: 'Generate Berikutnya', key: 'next_run', class:'text-center w-fit px-4' },
    ...(props.can?.create || props.can?.delete ? [{ label: 'Aksi', key: 'action', class: 'text-center' }] : []),
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

        <div class="space-y-4">
            
            <Card no-padding class="p-4 overflow-visible" overflow-visible>
                <div class="flex flex-row justify-between items-center gap-3 sm:gap-4">
                    <div class="flex-1 sm:w-1/3 sm:flex-none min-w-[200px]">
                        <SearchInput v-model="search" placeholder="Cari aset..." />
                    </div>
                    
                    <button 
                        v-if="can?.create" 
                        @click="openCreateModal" 
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-md shadow-sm flex items-center justify-center sm:gap-2 transition-all h-[38px] w-[38px] px-0 sm:w-auto sm:px-4 shrink-0"
                    >
                        <Plus class="w-5 h-5 sm:w-4 sm:h-4" /> 
                        <span class="hidden sm:inline">Buat Jadwal Baru</span>
                    </button>
                </div>
            </Card>

            <Card no-padding className="h-full">
                <DataTable :items="schedules" :columns="columns">
                    
                    <template #cell-target="{ item }">
                        <div class="flex items-start gap-3 py-2">
                            <div class="p-2.5 rounded-md bg-surface border border-ghost-hover shadow-sm shrink-0">
                                <component :is="getAssetIcon(item.asset_type)" class="w-6 h-6 text-primary" />
                            </div>
                            
                            <div class="w-full">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-ink text-sm">
                                        {{ getAssetName(item.asset_type) }}
                                    </span>
                                    
                                    <span v-if="item.assign_type === 'pic'" 
                                        class="flex items-center gap-1 bg-purple-100 text-purple-700 text-[10px] px-2 py-0.5 rounded-full font-bold border border-purple-200">
                                        <UserCheck class="w-3 h-3" /> PIC Area
                                    </span>
                                    <span v-else 
                                        class="flex items-center gap-1 bg-primary/10 text-primary text-[10px] px-2 py-0.5 rounded-full font-bold border border-primary">
                                        <Users class="w-3 h-3" /> Tim K3
                                    </span>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <div v-if="item.scope === 'global'" class="flex items-center gap-1.5 text-xs text-ink-light">
                                        <Globe class="w-3.5 h-3.5 text-primary shrink-0" />
                                        <span>Berlaku untuk <b class="text-ink">Semua Gedung</b></span>
                                    </div>

                                    <div v-else class="flex items-start gap-1.5 text-xs text-ink-light">
                                        <Building class="w-3.5 h-3.5 mt-0.5 text-warning shrink-0" />
                                        <div>
                                            <span>Berlaku di <b class="text-ink">{{ item.buildings?.length || 0 }} Gedung</b>:</span>
                                            <div class="text-[10px] text-ink-light mt-0.5 leading-tight">
                                                <span v-for="(b, index) in item.buildings?.slice(0, 2)" :key="b.id">
                                                    {{ b.name }}<span v-if="index < Math.min(item.buildings.length, 2) - 1">, </span>
                                                </span>
                                                <span v-if="item.buildings?.length > 2" class="text-warning font-medium">
                                                    +{{ item.buildings.length - 2 }} lainnya
                                                </span>
                                                <span v-if="!item.buildings?.length" class="text-danger italic">Belum pilih gedung</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #cell-frequency="{ item }">
                        <div class="flex flex-col gap-1 py-1 items-center">
                            <div class="text-xs font-medium text-ink-light flex items-center gap-2 whitespace-nowrap">
                                <Repeat class="w-3.5 h-3.5" />
                                <span v-if="item.months_interval === 1">Setiap Bulan</span>
                                <span v-else>Setiap {{ item.months_interval }} Bulan</span>
                            </div>
                            
                            <div v-if="item.week_rank">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-ghost text-ink-light border border-ghost-hover whitespace-nowrap">
                                    Mgg ke-{{ item.week_rank }}
                                </span>
                            </div>
                            <div v-else>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-success/10 text-success border border-success/30 whitespace-nowrap">
                                    Sepanjang Bulan
                                </span>
                            </div>
                        </div>
                    </template>

                    <template #cell-next_run="{ item }">
                        <div class="flex flex-col items-center whitespace-nowrap">
                            <span class="font-bold text-sm text-ink">
                                {{ new Date(item.next_run_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                            </span>
                            <span class="text-[10px] text-ink-light">Jadwal Generate</span>
                        </div>
                    </template>

                    <template #cell-action="{ item }">
                        <div v-if="can?.create || can?.delete" class="flex justify-center gap-1">
                            <button v-if="can?.create" @click="openEditModal(item)" class="p-1.5 text-ink-light hover:text-primary hover:bg-primary/10 rounded transition-colors" title="Edit Aturan">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button v-if="can?.delete" @click="deleteSchedule(item.id)" class="p-1.5 text-ink-light hover:text-danger hover:bg-danger/10 rounded transition-colors" title="Hapus Aturan">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </template>

                </DataTable>
            </Card>

        </div>

        <Modal :show="showModal" @close="showModal = false" max-width="lg">
            <div class="p-4 sm:p-5">
                <h2 class="text-lg font-bold mb-5 sm:mb-6 flex items-center gap-2 text-ink border-b pb-4">
                    <CalendarClock class="w-5 h-5 text-primary" />
                    {{ isEditing ? 'Edit Aturan Jadwal' : 'Buat Jadwal Massal' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-5 sm:space-y-6">
                    
                    <div>
                        <InputLabel value="Ditugaskan Kepada Siapa?" class="mb-2" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="cursor-pointer relative">
                                <input type="radio" v-model="form.assign_type" value="k3" class="peer sr-only">
                                <div class="p-3 rounded-md border bg-surface hover:bg-ghost transition-all peer-checked:border-primary peer-checked:ring-1 peer-checked:ring-primary peer-checked:bg-primary/10">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="p-1.5 bg-primary/10 rounded-md text-primary shrink-0">
                                            <Users class="w-4 h-4" />
                                        </div>
                                        <span class="font-bold text-sm text-ink">Tim K3</span>
                                    </div>
                                </div>
                            </label>

                            <label class="cursor-pointer relative">
                                <input type="radio" v-model="form.assign_type" value="pic" class="peer sr-only">
                                <div class="p-3 rounded-md border bg-surface hover:bg-ghost transition-all peer-checked:border-purple-500 peer-checked:ring-1 peer-checked:ring-purple-500 peer-checked:bg-purple-50">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="p-1.5 bg-purple-100 rounded-md text-purple-600 shrink-0">
                                            <UserCheck class="w-4 h-4" />
                                        </div>
                                        <span class="font-bold text-sm text-ink">PIC Ruangan</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <InputError :message="form.errors.assign_type" class="mt-1" />
                    </div>

                    <div v-if="!isEditing">
                        <InputLabel value="Jenis Aset" />
                        <div class="grid grid-cols-3 gap-2 sm:gap-3 mt-1">
                            <label class="cursor-pointer">
                                <input type="radio" v-model="form.asset_type" value="App\Models\Apar" class="peer sr-only">
                                <div class="p-2 sm:p-3 rounded-md border text-center peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary hover:bg-ghost">
                                    <Flame class="w-5 h-5 sm:w-6 sm:h-6 mx-auto mb-1" />
                                    <span class="text-[10px] sm:text-xs font-bold">APAR</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" v-model="form.asset_type" value="App\Models\Hydrant" class="peer sr-only">
                                <div class="p-2 sm:p-3 rounded-md border text-center peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary hover:bg-ghost">
                                    <Droplet class="w-5 h-5 sm:w-6 sm:h-6 mx-auto mb-1" />
                                    <span class="text-[10px] sm:text-xs font-bold">Hydrant</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" v-model="form.asset_type" value="App\Models\P3k" class="peer sr-only">
                                <div class="p-2 sm:p-3 rounded-md border text-center peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary hover:bg-ghost">
                                    <BriefcaseMedical class="w-5 h-5 sm:w-6 sm:h-6 mx-auto mb-1" />
                                    <span class="text-[10px] sm:text-xs font-bold">P3K</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div v-if="!isEditing" class="p-4 bg-ghost rounded-md border border-ghost-hover">
                        <InputLabel value="Terapkan Pada:" class="mb-3 text-ink" />

                        <label class="flex items-start gap-3 mb-3 cursor-pointer">
                            <input type="radio" v-model="form.scope" value="global" class="mt-1">
                            <div>
                                <div class="flex items-center gap-2 font-bold text-sm text-ink dark:text-ink-dark/90">
                                    <Globe class="w-4 h-4 text-primary shrink-0" />
                                    Semua Gedung (Global)
                                </div>
                                <p class="text-[11px] sm:text-xs text-ink-light">Jadwalkan untuk seluruh aset di pabrik.</p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="radio" v-model="form.scope" value="building" class="mt-1">
                            <div>
                                <div class="flex items-center gap-2 font-bold text-sm text-ink dark:text-ink-dark/90">
                                    <Building class="w-4 h-4 text-warning shrink-0" />
                                    Pilih Gedung Tertentu
                                </div>
                                <p class="text-[11px] sm:text-xs text-ink-light">Pilih satu atau lebih gedung spesifik.</p>
                            </div>
                        </label>

                        <div v-if="form.scope === 'building'" class="mt-3 ml-7 p-3 bg-surface border rounded-md max-h-40 overflow-y-auto">
                            <div v-if="buildings.length === 0" class="text-xs text-danger">Data gedung kosong.</div>
                            <div v-for="b in buildings" :key="b.id" class="flex items-center gap-2 mb-2 last:mb-0">
                                <input type="checkbox" :value="b.id" v-model="form.building_ids" class="rounded text-primary focus:ring-primary border-ghost-hover">
                                <span class="text-[13px] sm:text-sm text-ink dark:text-ink-dark/90">{{ b.name }}</span>
                            </div>
                        </div>
                        <InputError :message="form.errors.building_ids" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Interval (Bulan)" />
                            <TextInput v-model="form.months_interval" type="number" min="1" class="w-full text-center sm:text-left font-bold" />
                            <p class="text-[10px] text-ink-light mt-1">Contoh: 1 = Tiap bulan.</p>
                        </div>
                        <div>
                            <InputLabel value="Start Date" />
                            <TextInput v-model="form.start_date" type="date" class="w-full" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Target Pengerjaan (Mingguan)" class="mb-2" />
                        <div class="grid grid-cols-5 gap-1.5 sm:gap-2">
                            <div @click="form.week_rank = null"
                                :class="['cursor-pointer rounded border py-2 text-center text-[10px] sm:text-xs transition-all', form.week_rank === null ? 'bg-primary text-white font-bold' : 'bg-surface hover:bg-ghost']">
                                Bebas
                            </div>
                            <div v-for="i in 4" :key="i" @click="form.week_rank = i"
                                :class="['cursor-pointer rounded border py-2 text-center text-[10px] sm:text-xs transition-all', form.week_rank === i ? 'bg-primary text-white font-bold' : 'bg-surface hover:bg-ghost']">
                                Mgg {{ i }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t mt-4">
                        <button 
                            type="button" 
                            @click="showModal = false" 
                            class="w-full sm:w-auto px-4 py-2 sm:py-2.5 text-sm font-semibold text-ink-light hover:text-ink dark:text-ink-dark/90 transition-colors bg-ghost sm:bg-transparent rounded-md"
                        >
                            Batal
                        </button>
                        <PrimaryButton 
                            class="w-full sm:w-auto justify-center py-2 sm:py-2.5 shadow-md sm:shadow-lg shadow-indigo-200"
                            :disabled="form.processing"
                        >
                            {{ isEditing ? 'Simpan Perubahan' : 'Generate Jadwal' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>