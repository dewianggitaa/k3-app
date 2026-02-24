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
    assetsList: Object,
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
const exportForm = ref({ start_date: '', end_date: '', activity_type: 'all', asset_code: 'all' });

const openExportModal = () => {
    exportForm.value = { ...filterForm.value };
    showExportModal.value = true;
};

const generatePdf = () => {
    const url = route('reports.export', { 
        tab: props.activeTab, 
        ...exportForm.value 
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
        asset_code: 'all'
    }, { preserveState: true, preserveScroll: true });
};

const applyFilter = () => {
    router.get(route('reports.index'), {
        tab: props.activeTab, 
        ...filterForm.value
    }, { preserveState: true, preserveScroll: true });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const columnsP3k = computed(() => {
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

const currentAssets = computed(() => props.assetsList[props.activeTab] || []);
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
                
                <div v-if="activeTab === 'p3k'">
                    <DataTable :items="reports.data" :columns="columnsP3k">
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
                                <span :class="item.details.includes('KRITIS') ? 'text-red-600 font-medium ' : 'text-black font-medium'" class="block">
                                    {{ item.details.split('\n')[0] }}
                                </span>
                                <span v-if="item.details.split('\n')[1]" class="block text-[11px] mt-1.5 leading-tight italic text-gray-500">
                                    {{ item.details.split('\n')[1] }}
                                </span>
                            </div>
                        </template>
                    </DataTable>
                </div>

                <div v-else class="overflow-x-auto w-full">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b">
                            <tr>
                                <th class="px-4 py-3">No</th>
                                <th v-if="filterForm.asset_code === 'all'" class="px-4 py-3">Kode Aset</th>
                                <th class="px-4 py-3">Data Pelapor (PIC)</th>
                                <th class="px-4 py-3">Hasil Inspeksi</th>
                                <th class="px-4 py-3 bg-indigo-50 border-l border-indigo-100">Validasi K3</th>
                                <th class="px-4 py-3 bg-indigo-50">Tindakan & Catatan</th>
                                <th class="px-4 py-3 bg-indigo-50">Kondisi Akhir</th>
                            </tr>
                        </thead>
                        <tbody v-for="(k3Report, index) in reports.data" :key="k3Report.id" class="border-b last:border-0 hover:bg-gray-50 transition-colors">
                            <tr v-for="(picReport, picIndex) in k3Report.pic_reports" :key="picReport.id">
                                
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-4 py-3 font-medium text-gray-900 text-center border-r align-top">
                                    {{ index + 1 }}
                                </td>
                                
                                <td v-if="filterForm.asset_code === 'all' && picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-4 py-3 font-bold border-r align-top">
                                    {{ k3Report.asset_code }}
                                </td>

                                <td class="px-4 py-3 border-r align-top">
                                    <div class="font-semibold text-gray-800">{{ picReport.actor }}</div>
                                    <div class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                        <Calendar class="w-3 h-3"/> {{ formatDate(picReport.record_date) }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 border-r align-top">
                                    <span :class="{'text-red-600': picReport.status === 'KRITIS', 'text-green-600': picReport.status === 'SAFE', 'text-gray-400': picReport.status === '-'}" class="font-bold block">
                                        {{ picReport.status }}
                                    </span>
                                    
                                    <div v-if="picReport.status === 'KRITIS'" class="mt-2 p-2.5 bg-red-50 border-l-2 border-red-500 rounded-r shadow-sm">
                                        <span class="text-[10px] font-bold text-red-800 uppercase tracking-wider block mb-1">Temuan Kerusakan:</span>
                                        <span class="text-xs text-red-700 block whitespace-pre-line leading-snug">
                                            {{ picReport.details ? picReport.details.replace('Kondisi: KRITIS\nRincian: ', '') : '' }}
                                        </span>
                                    </div>
                                    
                                    <div v-else-if="picReport.status === 'SAFE'" class="mt-2 text-xs text-green-700 font-medium italic">
                                        ✓ Seluruh komponen standar normal.
                                    </div>

                                    <div v-else class="mt-2 text-xs text-gray-400 font-medium italic">
                                        Belum ada laporan dari PIC.
                                    </div>
                                </td>

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-4 py-3 bg-indigo-50/30 border-r align-top">
                                    <div class="font-bold text-indigo-900">{{ k3Report.actor_k3 }}</div>
                                    
                                    <div v-if="k3Report.record_date_k3" class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                        <Calendar class="w-3 h-3"/> {{ formatDate(k3Report.record_date_k3) }}
                                    </div>
                                </td>

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-4 py-3 bg-indigo-50/30 border-r align-top text-gray-700">
                                    {{ k3Report.tindakan }}
                                </td>

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-4 py-3 bg-indigo-50/30 align-middle text-center">
                                    <span :class="k3Report.kondisi_akhir === 'safe' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide border">
                                        {{ k3Report.kondisi_akhir }}
                                    </span>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>

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
                        <option value="usage">Hanya Cetak Riwayat Stok</option>
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