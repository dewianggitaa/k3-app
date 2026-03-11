<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { 
    CheckCircle, AlertCircle, Clock, FileText, 
    MapPin, User, Calendar, Search, 
    Filter, RefreshCw, AlertTriangle, Pencil, Trash2, X
} from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    inspections: Object,
    buildings: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const buildingFilter = ref(props.filters?.building_id || '');

watch([search, statusFilter, buildingFilter], debounce(() => {
    router.get(route('inspections.index'), { 
        search: search.value,
        status: statusFilter.value,
        building_id: buildingFilter.value 
    }, { preserveState: true, replace: true });
}, 300));

const columns = [
    { label: 'Status', key: 'status', class: 'w-32' },
    { label: 'Aset & Lokasi', key: 'asset' },
    { label: 'Petugas (PIC)', key: 'user' },
    { label: 'Jadwal & Deadline', key: 'date' },
    { label: 'Aksi', key: 'action', class: 'w-24 text-center' },
];

const getStatusBadge = (status) => {
    switch(status) {
        case 'completed': return 'bg-success/20 text-success border-success/30';
        case 'issue': return 'bg-danger/20 text-danger border-danger/30';
        case 'overdue': return 'bg-warning/20 text-warning border-warning/30';
        default: return 'bg-ghost text-ink-light border-ghost-hover'; // Pending
    }
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'completed': return 'Selesai';
        case 'issue': return 'Masalah';
        case 'overdue': return 'Terlambat';
        default: return 'Pending';
    }
};

const showEditModal = ref(false);
const showFilters = ref(false);
const filterWrapper = ref(null);

const handleOutsideClick = (e) => {
    if (showFilters.value && filterWrapper.value && filterWrapper.value.$el && !filterWrapper.value.$el.contains(e.target)) {
        showFilters.value = false;
    }
};

const handleScroll = (e) => {
    // Only close if scrolling something outside the filterWrapper
    if (showFilters.value && filterWrapper.value && filterWrapper.value.$el) {
        if (!filterWrapper.value.$el.contains(e.target)) {
            showFilters.value = false;
        }
    }
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
    window.addEventListener('scroll', handleScroll, { passive: true, capture: true });
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
    window.removeEventListener('scroll', handleScroll, { capture: true });
});
const editForm = useForm({ id: null, status: '', notes: '' });

const openEdit = (item) => {
    editForm.id     = item.id;
    editForm.status = item.status;
    editForm.notes  = item.report_data?.admin_notes || '';
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('inspections.update', editForm.id), {
        onSuccess: () => { showEditModal.value = false; },
    });
};

const deleteInspection = (id) => {
    if (!confirm('Hapus tugas inspeksi ini? Tindakan tidak dapat dibatalkan.')) return;
    router.delete(route('inspections.destroy', id));
};
</script>

<template>
    <Head title="Monitoring Tugas Inspeksi" />

    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-ink leading-tight">
                    Monitoring Tugas Inspeksi
                </h2>
            </div>
        </template>

        <div class="space-y-4">
            
            <Card ref="filterWrapper" no-padding class="p-4 overflow-visible" overflow-visible>
                <div class="flex flex-col md:flex-row-reverse gap-4 justify-between items-start md:items-center">
                    
                    <div class="w-full md:w-1/3 flex gap-2">
                        <div class="flex-1">
                            <SearchInput v-model="search" placeholder="Cari ID aset..." />
                        </div>
                        <button 
                            @click="showFilters = !showFilters"
                            class="md:hidden p-2 bg-ghost border border-ghost-hover hover:bg-ghost-hover rounded-md text-ink-light transition-colors flex items-center justify-center"
                        >
                            <Filter class="w-5 h-5" />
                        </button>
                    </div>

                    <div :class="[showFilters ? 'flex' : 'hidden', 'md:flex flex-col md:flex-row flex-wrap gap-2 w-full md:w-auto']">
                        
                        <div class="relative w-full md:w-auto md:min-w-[200px]">
                            <Dropdown align="left" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors outline-none cursor-pointer">
                                        <span class="truncate">{{ buildings.find(b => b.id === buildingFilter)?.name || 'Semua Gedung' }}</span>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1">
                                        <button @click="buildingFilter = ''" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': buildingFilter === '' }">
                                            Semua Gedung
                                        </button>
                                        <button v-for="b in buildings" :key="b.id" @click="buildingFilter = b.id" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': buildingFilter === b.id }">
                                            {{ b.name }}
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                <Filter class="w-4 h-4" />
                            </div>
                        </div>

                        <div class="relative w-full md:w-auto md:min-w-[180px]">
                            <Dropdown align="left" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors outline-none cursor-pointer">
                                        <span class="truncate">{{ {
                                            '': 'Semua Status',
                                            'pending': 'Pending',
                                            'completed': 'Selesai',
                                            'issue': 'Ada Masalah',
                                            'overdue': 'Terlambat'
                                        }[statusFilter] || 'Semua Status' }}</span>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1">
                                        <button @click="statusFilter = ''" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': statusFilter === '' }">Semua Status</button>
                                        <button @click="statusFilter = 'pending'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': statusFilter === 'pending' }">Pending</button>
                                        <button @click="statusFilter = 'completed'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': statusFilter === 'completed' }">Selesai</button>
                                        <button @click="statusFilter = 'issue'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': statusFilter === 'issue' }">Ada Masalah</button>
                                        <button @click="statusFilter = 'overdue'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': statusFilter === 'overdue' }">Terlambat</button>
                                    </div>
                                </template>
                            </Dropdown>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                <AlertCircle class="w-4 h-4" />
                            </div>
                        </div>

                        <button v-if="search || statusFilter || buildingFilter" 
                            @click="() => { search = ''; statusFilter = ''; buildingFilter = ''; }"
                            class="p-2 w-full md:w-auto text-ink-light hover:text-danger hover:bg-danger/10 rounded-md transition-colors flex justify-center items-center gap-2"
                            title="Reset Filter">
                            <RefreshCw class="w-4 h-4" />
                            <span class="md:hidden text-sm">Reset Filter</span>
                        </button>
                    </div>
                </div>
            </Card>

            <Card no-padding>
                <DataTable :items="inspections" :columns="columns">
                    
                    <template #cell-status="{ item }">
                        <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border flex items-center gap-1.5 w-fit', getStatusBadge(item.status)]">
                            <CheckCircle v-if="item.status === 'completed'" class="w-3.5 h-3.5" />
                            <AlertTriangle v-else-if="item.status === 'issue'" class="w-3.5 h-3.5" />
                            <Clock v-else-if="item.status === 'overdue'" class="w-3.5 h-3.5" />
                            <div v-else class="w-2 h-2 rounded-full bg-gray-400"></div> {{ getStatusLabel(item.status) }}
                        </span>
                    </template>

                    <template #cell-asset="{ item }">
                        <div class="py-1">
                            <div class="flex justify-between items-start gap-4">
                                
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-ink text-sm">
                                            {{ item.assetable_type.split('\\').pop() }} 
                                        </span>
                                        <span class="bg-ghost text-ink-light text-[10px] px-1.5 py-0.5 rounded border font-mono">
                                            {{ item.assetable.code }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-start gap-1.5 text-xs text-ink-light leading-tight">
                                        <MapPin class="w-3.5 h-3.5 mt-0.5 shrink-0 text-ink-light" />
                                        <div v-if="item.assetable?.room">
                                            <span class="font-medium text-ink dark:text-ink-dark/90">
                                                {{ item.assetable.room.floor?.building?.name || '?' }}
                                            </span>
                                            <span class="mx-1 text-gray-300">|</span>
                                            <span>
                                                {{ item.assetable.room.floor?.name }} - {{ item.assetable.room.name }}
                                            </span>
                                        </div>
                                        <span v-else class="text-red-400 italic">Lokasi aset tidak ditemukan</span>
                                    </div>
                                </div>

                                <div v-if="item.assetable?.room?.floor_id">
                                    <Link 
                                        :href="route('assets.mapping', { 
                                            floor: item.assetable.room.floor_id, 
                                            target_id: item.assetable_id, 
                                            target_type: item.assetable_type.split('\\').pop().toLowerCase() 
                                        })"
                                        class="group flex flex-col items-center justify-center gap-1 p-2 bg-primary/10 hover:bg-primary/10 border border-primary/20 hover:border-primary rounded-md transition-all"
                                        title="Lihat Posisi di Peta"
                                    >
                                        <MapPin class="w-4 h-4 text-primary group-hover:scale-110 transition-transform" />
                                        <span class="text-[10px] font-bold text-primary">Peta</span>
                                    </Link>
                                </div>

                            </div>
                        </div>
                    </template>

                    <template #cell-user="{ item }">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                :class="item.user ? 'bg-primary/10 text-primary' : 'bg-primary/10 text-primary'">
                                <User v-if="!item.user" class="w-4 h-4" />
                                <span v-else>{{ item.user.name.charAt(0).toUpperCase() }}</span>
                            </div>

                            <div class="text-sm">
                                <div v-if="item.user" class="font-bold text-ink truncate max-w-[120px]" :title="item.user.name">
                                    {{ item.user.name }}
                                </div>
                                <div v-else class="font-bold text-ink">
                                    Tim K3
                                </div>
                                <div class="text-[10px] text-ink-light">
                                    {{ item.user ? 'PIC Ruangan' : 'Open Ticket' }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #cell-date="{ item }">
                        <div class="text-xs space-y-1">
                            <div class="flex items-center gap-1.5 text-ink-light">
                                <Calendar class="w-3.5 h-3.5 text-ink-light" />
                                <span>{{ new Date(item.schedule_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</span>
                            </div>
                            <div :class="['flex items-center gap-1.5 font-medium', 
                                new Date() > new Date(item.due_date) && item.status === 'pending' ? 'text-danger' : 'text-ink-light']">
                                <Clock class="w-3.5 h-3.5" />
                                <span>Batas: {{ new Date(item.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-action="{ item }">
                        <div class="flex items-center justify-center gap-1">
                            <button
                                @click.stop="openEdit(item)"
                                class="p-1.5 text-ink-light hover:text-primary hover:bg-primary/10 rounded-md transition-colors"
                                title="Edit Status"
                            >
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button
                                @click.stop="deleteInspection(item.id)"
                                class="p-1.5 text-ink-light hover:text-danger hover:bg-danger/10 rounded-md transition-colors"
                                title="Hapus"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </template>

                </DataTable>
            </Card>
        </div>

        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="showEditModal = false">
                <div class="bg-surface dark:bg-surface-dark rounded-md shadow-2xl w-full max-w-sm mx-4 p-4">

                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-bold text-ink dark:text-ink-dark">Edit Status Inspeksi</h3>
                        <button @click="showEditModal = false" class="text-ink-light hover:text-ink-light transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-ink-light dark:text-ink-light mb-1.5">Status</label>
                            <select v-model="editForm.status"
                                class="w-full rounded-md border border-ghost-hover dark:border-ghost-dark bg-ghost dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-400 focus:outline-none"
                            >
                                <option value="pending">Pending</option>
                                <option value="completed">Selesai</option>
                                <option value="issue">Ada Masalah</option>
                                <option value="overdue">Terlambat</option>
                            </select>
                            <p v-if="editForm.errors.status" class="text-xs text-danger mt-1">{{ editForm.errors.status }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-ink-light dark:text-ink-light mb-1.5">Catatan Admin (opsional)</label>
                            <textarea v-model="editForm.notes" rows="3"
                                placeholder="Tambahkan catatan admin..."
                                class="w-full rounded-md border border-ghost-hover dark:border-ghost-dark bg-ghost dark:bg-gray-700 text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-400 focus:outline-none resize-none"
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" @click="showEditModal = false"
                                class="px-4 py-2 text-sm text-ink-light dark:text-ink-light hover:bg-ghost dark:hover:bg-ghost-dark rounded-md transition-colors"
                            >Batal</button>
                            <button type="submit" :disabled="editForm.processing"
                                class="px-4 py-2 text-sm font-semibold text-white bg-primary hover:bg-primary-hover rounded-md transition-colors disabled:opacity-60"
                            >Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </MainLayout>
</template>