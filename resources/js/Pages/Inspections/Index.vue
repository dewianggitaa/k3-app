<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { 
    CheckCircle, AlertCircle, Clock, FileText, 
    MapPin, User, Calendar, Search, 
    Filter, RefreshCw, AlertTriangle
} from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';

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
];

// Helper Warna Badge Status
const getStatusBadge = (status) => {
    switch(status) {
        case 'completed': return 'bg-green-100 text-green-700 border-green-200';
        case 'issue': return 'bg-red-100 text-red-700 border-red-200';
        case 'overdue': return 'bg-orange-100 text-orange-700 border-orange-200';
        default: return 'bg-gray-100 text-gray-600 border-gray-200'; // Pending
    }
};

// Helper Label Status (Bahasa Indonesia)
const getStatusLabel = (status) => {
    switch(status) {
        case 'completed': return 'Selesai';
        case 'issue': return 'Masalah';
        case 'overdue': return 'Terlambat';
        default: return 'Pending';
    }
};
</script>

<template>
    <Head title="Monitoring Tugas Inspeksi" />

    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-gray-800 leading-tight">
                    Monitoring Tugas Inspeksi
                </h2>
            </div>
        </template>

        <div class="space-y-4">
            
            <Card no-padding class="p-4">
                <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
                    
                    <div class="w-full md:w-1/3">
                        <SearchInput v-model="search" placeholder="Cari ID aset atau nama petugas..." />
                    </div>

                    <div class="flex flex-wrap gap-2 w-full md:w-auto">
                        
                        <div class="relative">
                            <select v-model="buildingFilter" class="appearance-none bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-3 pr-8 py-2">
                                <option value="">Semua Gedung</option>
                                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <Filter class="w-4 h-4" />
                            </div>
                        </div>

                        <div class="relative">
                            <select v-model="statusFilter" class="appearance-none bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-3 pr-8 py-2">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Selesai</option>
                                <option value="issue">Ada Masalah</option>
                                <option value="overdue">Terlambat</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <AlertCircle class="w-4 h-4" />
                            </div>
                        </div>

                        <button v-if="search || statusFilter || buildingFilter" 
                            @click="() => { search = ''; statusFilter = ''; buildingFilter = ''; }"
                            class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                            title="Reset Filter">
                            <RefreshCw class="w-4 h-4" />
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
                                        <span class="font-bold text-gray-800 text-sm">
                                            {{ item.assetable_type.split('\\').pop() }} 
                                        </span>
                                        <span class="bg-gray-100 text-gray-500 text-[10px] px-1.5 py-0.5 rounded border font-mono">
                                            {{ item.assetable.code }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-start gap-1.5 text-xs text-gray-500 leading-tight">
                                        <MapPin class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" />
                                        <div v-if="item.assetable?.room">
                                            <span class="font-medium text-gray-700">
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
                                        class="group flex flex-col items-center justify-center gap-1 p-2 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 hover:border-indigo-200 rounded-lg transition-all"
                                        title="Lihat Posisi di Peta"
                                    >
                                        <MapPin class="w-4 h-4 text-indigo-600 group-hover:scale-110 transition-transform" />
                                        <span class="text-[10px] font-bold text-indigo-700">Peta</span>
                                    </Link>
                                </div>

                            </div>
                        </div>
                    </template>

                    <template #cell-user="{ item }">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                :class="item.user ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700'">
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
                                <div class="text-[10px] text-gray-400">
                                    {{ item.user ? 'PIC Ruangan' : 'Open Ticket' }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #cell-date="{ item }">
                        <div class="text-xs space-y-1">
                            <div class="flex items-center gap-1.5 text-gray-600">
                                <Calendar class="w-3.5 h-3.5 text-gray-400" />
                                <span>{{ new Date(item.schedule_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</span>
                            </div>
                            <div :class="['flex items-center gap-1.5 font-medium', 
                                new Date() > new Date(item.due_date) && item.status === 'pending' ? 'text-red-600' : 'text-gray-500']">
                                <Clock class="w-3.5 h-3.5" />
                                <span>Batas: {{ new Date(item.due_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</span>
                            </div>
                        </div>
                    </template>

                </DataTable>

                <div class="p-4 border-t border-gray-100">
                    <Pagination :links="inspections.links" />
                </div>
            </Card>
        </div>
    </MainLayout>
</template>