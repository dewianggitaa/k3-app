<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { FileText, Calendar, Filter, FileDown, Box, ShieldAlert, Droplet, X } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    activeTab: String,
    assetsList: Object, // Menerima daftar aset dari Controller
    filters: Object,
    reports: Object 
});

const filterForm = ref({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    activity_type: props.filters.activity_type || 'all',
    asset_code: props.filters.asset_code || 'all',
});

const showExportModal = ref(false);
const exportForm = ref({
    start_date: '', end_date: '', activity_type: 'all', asset_code: 'all'
});

const openExportModal = () => {
    exportForm.value.start_date = filterForm.value.start_date;
    exportForm.value.end_date = filterForm.value.end_date;
    exportForm.value.activity_type = filterForm.value.activity_type;
    exportForm.value.asset_code = filterForm.value.asset_code;
    showExportModal.value = true;
};

const generatePdf = () => {
    const url = route('reports.export', {
        tab: props.activeTab,
        start_date: exportForm.value.start_date,
        end_date: exportForm.value.end_date,
        activity_type: exportForm.value.activity_type,
        asset_code: exportForm.value.asset_code
    });
    window.open(url, '_blank');
    showExportModal.value = false;
};

const changeTab = (tabName) => {
    router.get(route('reports.index'), {
        tab: tabName,
        start_date: filterForm.value.start_date,
        end_date: filterForm.value.end_date,
        activity_type: 'all',
        asset_code: 'all' // Reset pilihan aset kalau pindah tab
    }, { preserveState: true, preserveScroll: true });
};

const applyFilter = () => {
    router.get(route('reports.index'), {
        tab: props.activeTab,
        start_date: filterForm.value.start_date,
        end_date: filterForm.value.end_date,
        activity_type: filterForm.value.activity_type,
        asset_code: filterForm.value.asset_code
    }, { preserveState: true, preserveScroll: true });
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const columns = computed(() => {
    const cols = [
        { label: 'Waktu Kejadian', key: 'record_date', class: 'w-48' },
        { label: 'Tipe Aktivitas', key: 'action_type' },
        { label: 'Aktor / Pelapor', key: 'actor' },
        { label: 'Detail Keterangan', key: 'details' },
    ];
    if (filterForm.value.asset_code === 'all') {
        cols.splice(1, 0, { label: 'Kode / Kotak Aset', key: 'asset_code' });
    }
    return cols;
});

// Mengambil list aset yang sedang aktif sesuai tab
const currentAssets = computed(() => {
    return props.assetsList[props.activeTab] || [];
});
</script>

<template>
    <Head title="Riwayat Laporan K3" />

    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-gray-800 leading-tight flex items-center gap-2">
                    <FileText class="w-5 h-5 text-gray-500" /> Riwayat Laporan K3
                </h2>
            </div>
        </template>

        <div class="space-y-4">
            
            <div class="border-b border-gray-200 px-2">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button @click="changeTab('p3k')" :class="activeTab === 'p3k' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition flex items-center gap-2"><Box class="w-4 h-4" /> Kotak P3K</button>
                    <button @click="changeTab('apar')" :class="activeTab === 'apar' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition flex items-center gap-2"><ShieldAlert class="w-4 h-4" /> Inspeksi APAR</button>
                    <button @click="changeTab('hydrant')" :class="activeTab === 'hydrant' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition flex items-center gap-2"><Droplet class="w-4 h-4" /> Inspeksi Hydrant</button>
                </nav>
            </div>

            <Card no-padding class="p-4">
                <div class="flex flex-col lg:flex-row gap-4 justify-between items-start lg:items-end">
                    
                    <div class="flex flex-wrap items-end gap-3 w-full lg:w-auto">
                        
                        <div class="w-full sm:w-auto">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Aset Spesifik</label>
                            <select v-model="filterForm.asset_code" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 block w-full py-2 px-3">
                                <option value="all">Semua Aset</option>
                                <option v-for="asset in currentAssets" :key="asset.code" :value="asset.code">{{ asset.code }}</option>
                            </select>
                        </div>

                        <div class="w-full sm:w-auto">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Dari Tanggal</label>
                            <input type="date" v-model="filterForm.start_date" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 block w-full py-2 px-3">
                        </div>
                        
                        <div class="w-full sm:w-auto">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Sampai</label>
                            <input type="date" v-model="filterForm.end_date" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 block w-full py-2 px-3">
                        </div>
                        
                        <div v-if="activeTab === 'p3k'" class="w-full sm:w-auto">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Tipe Laporan</label>
                            <select v-model="filterForm.activity_type" class="bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 block w-full py-2 px-3">
                                <option value="all">Semua Laporan</option>
                                <option value="usage">Mutasi (Keluar/Masuk)</option>
                                <option value="inspection">Inspeksi Fisik</option>
                            </select>
                        </div>

                        <button @click="applyFilter" class="w-full sm:w-auto bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg font-bold text-sm transition shadow-sm">
                            Terapkan
                        </button>
                    </div>

                    <button @click="openExportModal" class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold text-sm flex justify-center items-center gap-2 transition shadow-sm shrink-0">
                        <FileDown class="w-4 h-4" /> Export PDF
                    </button>
                </div>
            </Card>

            <Card no-padding>
                <div v-if="filterForm.asset_code !== 'all'" class="px-6 py-4 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-indigo-500 uppercase tracking-wider">Menampilkan Data Khusus Aset:</p>
                        <p class="text-lg font-extrabold text-indigo-900">{{ filterForm.asset_code }}</p>
                    </div>
                </div>

                <DataTable :items="reports.data" :columns="columns">
                    <template #cell-record_date="{ item }">
                        <div class="flex items-center gap-1.5 text-gray-600">
                            <Calendar class="w-3.5 h-3.5 text-gray-400" />
                            <span class="text-sm font-medium">{{ formatDate(item.record_date) }}</span>
                        </div>
                    </template>

                    <template #cell-asset_code="{ item }">
                        <span class="font-bold text-gray-800">{{ item.asset_code }}</span>
                    </template>

                    <template #cell-action_type="{ item }">
                        <span :class="{
                                'bg-blue-100 text-blue-700 border-blue-200': item.action_type === 'PENAMBAHAN',
                                'bg-red-100 text-red-700 border-red-200': item.action_type === 'PEMAKAIAN',
                                'bg-orange-100 text-orange-700 border-orange-200': item.action_type === 'INSPEKSI RUTIN'
                            }" 
                            class="px-2.5 py-1 rounded-full text-xs font-bold border flex items-center justify-center w-fit whitespace-nowrap">
                            {{ item.action_type }}
                        </span>
                    </template>

                    <template #cell-actor="{ item }">
                        <span class="text-sm font-medium text-gray-700">{{ item.actor }}</span>
                    </template>

                    <template #cell-details="{ item }">
                        <div class="whitespace-normal min-w-[200px] max-w-sm break-words leading-snug">
                            
                            <span :class="{
                                'text-red-600 font-medium ': item.details.split('\n')[0].includes('KRITIS'),
                                'text-black font-medium': !item.details.split('\n')[0].includes('KRITIS')
                            }" class="block">
                                {{ item.details.split('\n')[0] }}
                            </span>
                            
                            <span v-if="item.details.split('\n')[1]" 
                                  :class="item.details.split('\n')[0].includes('KRITIS') ? 'text-gray-500' : 'text-gray-500'" 
                                  class="block text-[11px] mt-1.5 leading-tight italic">
                                {{ item.details.split('\n')[1] }}
                            </span>
                            
                        </div>
                    </template>
                </DataTable>

                <div v-if="reports.data.length === 0" class="p-12 text-center text-gray-500">
                    <span class="text-4xl block mb-3">📭</span>
                    Tidak ada data laporan pada rentang tanggal atau filter ini.
                </div>

                <div v-if="reports.data.length > 0" class="p-4 border-t border-gray-100">
                    <Pagination :links="reports.links" />
                </div>
            </Card>

        </div>
    </MainLayout>

    <div v-if="showExportModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800 flex items-center gap-2"><FileDown class="w-5 h-5 text-red-600" /> Setting Export PDF</h3>
                <button @click="showExportModal = false" class="text-gray-400 hover:text-red-500 transition"><X class="w-5 h-5" /></button>
            </div>

            <div class="p-6 space-y-4">
                
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Aset Spesifik (Opsional)</label>
                    <select v-model="exportForm.asset_code" class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500">
                        <option value="all">Cetak Semua Aset {{ activeTab.toUpperCase() }}</option>
                        <option v-for="asset in currentAssets" :key="asset.code" :value="asset.code">{{ asset.code }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Mulai Tanggal</label>
                        <input type="date" v-model="exportForm.start_date" class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" v-model="exportForm.end_date" class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500">
                    </div>
                </div>

                <div v-if="activeTab === 'p3k'">
                    <label class="block text-xs font-bold text-gray-700 mb-1">Tipe Data yang Dicetak</label>
                    <select v-model="exportForm.activity_type" class="w-full rounded-lg border-gray-300 text-sm focus:ring-red-500">
                        <option value="all">Cetak Semua Aktivitas</option>
                        <option value="usage">Hanya Cetak Mutasi Obat</option>
                        <option value="inspection">Hanya Cetak Hasil Inspeksi Fisik</option>
                    </select>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="showExportModal = false" class="px-4 py-2 text-sm font-bold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition">Batal</button>
                <button @click="generatePdf" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-lg shadow-md transition flex items-center gap-2">
                    Download PDF
                </button>
            </div>

        </div>
    </div>
</template>