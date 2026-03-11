<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { debounce } from 'lodash';
import { FileText, Calendar, Filter, FileDown, Box, ShieldAlert, Droplet, X, Search } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    activeTab: String,
    assetsList: Object,
    filters: Object,
    reports: Object 
});

const filterForm = ref({
    search: props.filters.search || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    activity_type: props.filters.activity_type || 'all',
    asset_code: props.filters.asset_code || 'all',
});

const showFilters = ref(false);
const filterWrapper = ref(null);

const handleOutsideClick = (e) => {
    if (showFilters.value && filterWrapper.value && filterWrapper.value.$el && !filterWrapper.value.$el.contains(e.target)) {
        showFilters.value = false;
    }
};

const handleScroll = (e) => {
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

watch(filterForm, debounce(() => {
    router.get(route('reports.index'), {
        tab: props.activeTab, 
        ...filterForm.value
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300), { deep: true });

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

const applyFilter = () => {};

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
                <h2 class="font-bold text-lg text-ink leading-tight flex items-center gap-2">
                    <FileText class="w-5 h-5 text-ink-light" /> Riwayat Laporan K3
                </h2>
            </div>
        </template>

        <template #header-nav>
            <div class="px-4 bg-surface dark:bg-surface-dark overflow-x-auto scrollbar-hide border-b border-ghost-hover custom-scrollbar w-full">
                <nav class="-mb-px flex space-x-6">
                    <button @click="changeTab('p3k')" :class="activeTab === 'p3k' ? 'border-primary text-primary' : 'border-transparent text-ink-light hover:text-ink dark:text-ink-dark/90'" class="whitespace-nowrap pb-3 border-b-2 font-bold text-sm transition flex items-center gap-2 pt-1"><Box class="w-4 h-4" /> Kotak P3K</button>
                    <button @click="changeTab('apar')" :class="activeTab === 'apar' ? 'border-primary text-primary' : 'border-transparent text-ink-light hover:text-ink dark:text-ink-dark/90'" class="whitespace-nowrap pb-3 border-b-2 font-bold text-sm transition flex items-center gap-2 pt-1"><ShieldAlert class="w-4 h-4" /> Inspeksi APAR</button>
                    <button @click="changeTab('hydrant')" :class="activeTab === 'hydrant' ? 'border-primary text-primary' : 'border-transparent text-ink-light hover:text-ink dark:text-ink-dark/90'" class="whitespace-nowrap pb-3 border-b-2 font-bold text-sm transition flex items-center gap-2 pt-1"><Droplet class="w-4 h-4" /> Inspeksi Hydrant</button>
                </nav>
            </div>
        </template>

        <div class="space-y-4">
            
            <Card ref="filterWrapper" no-padding class="p-4 overflow-visible" overflow-visible>
                <div class="flex flex-col lg:flex-row-reverse gap-4 justify-between items-start lg:items-end w-full">
                    
                    <div class="flex flex-row gap-2 w-full lg:w-auto shrink-0 lg:mb-[3px]">
                        <div class="flex-1 min-w-0 lg:flex-none lg:w-64">
                            <SearchInput v-model="filterForm.search" placeholder="Cari laporan..." />
                        </div>
                        <button @click="showFilters = !showFilters" class="lg:hidden p-2 bg-ghost border border-ghost-hover hover:border-primary text-ink-light rounded-md flex items-center justify-center h-[38px] w-[38px] shrink-0 transition-colors">
                            <Filter class="w-4 h-4" />
                        </button>
                        <button @click="openExportModal" class="lg:hidden p-2 sm:px-4 bg-danger hover:bg-red-700 text-white rounded-md text-sm font-bold flex items-center justify-center transition-colors shadow-sm shrink-0 h-[38px] w-[38px] sm:w-auto gap-2">
                            <FileDown class="w-5 h-5 sm:w-4 sm:h-4" />
                            <span class="hidden sm:inline">Export PDF</span>
                        </button>
                    </div>

                    <div :class="[showFilters ? 'flex' : 'hidden', 'lg:flex flex-col md:flex-row flex-wrap gap-3 w-full lg:w-auto items-end']">
                        
                        <div class="w-full sm:w-48 lg:w-56 flex-shrink-0">
                            <label class="block text-xs font-bold text-ink-light mb-1">Aset Spesifik</label>
                            <Dropdown align="right" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors cursor-pointer outline-none w-full">
                                        <span class="truncate">{{ filterForm.asset_code === 'all' ? 'Semua Aset' : filterForm.asset_code }}</span>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                            <ChevronDown class="w-3.5 h-3.5" />
                                        </div>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1 max-h-60 overflow-y-auto">
                                        <button @click="filterForm.asset_code = 'all'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': filterForm.asset_code === 'all' }">
                                            Semua Aset
                                        </button>
                                        <button v-for="asset in currentAssets" :key="asset.code" @click="filterForm.asset_code = asset.code" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': filterForm.asset_code === asset.code }">
                                            {{ asset.code }}
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <div class="w-full sm:w-auto">
                            <label class="block text-xs font-bold text-ink-light mb-1">Dari Tanggal</label>
                            <input type="date" v-model="filterForm.start_date" class="h-[38px] bg-ghost border border-ghost-hover text-ink dark:text-ink-dark/90 text-sm rounded-md focus:ring-primary block w-full px-3 outline-none">
                        </div>
                        
                        <div class="w-full sm:w-auto">
                            <label class="block text-xs font-bold text-ink-light mb-1">Sampai</label>
                            <input type="date" v-model="filterForm.end_date" class="h-[38px] bg-ghost border border-ghost-hover text-ink dark:text-ink-dark/90 text-sm rounded-md focus:ring-primary block w-full px-3 outline-none">
                        </div>
                        
                        <div v-if="activeTab === 'p3k'" class="w-full sm:w-48 lg:w-56 flex-shrink-0">
                            <label class="block text-xs font-bold text-ink-light mb-1">Tipe Laporan</label>
                            <Dropdown align="right" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors cursor-pointer outline-none w-full">
                                        <span class="truncate">
                                            {{ filterForm.activity_type === 'all' ? 'Semua Laporan' : filterForm.activity_type === 'usage' ? 'Mutasi (Keluar/Masuk)' : 'Inspeksi Fisik' }}
                                        </span>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                            <ChevronDown class="w-3.5 h-3.5" />
                                        </div>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1 max-h-60 overflow-y-auto">
                                        <button @click="filterForm.activity_type = 'all'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': filterForm.activity_type === 'all' }">
                                            Semua Laporan
                                        </button>
                                        <button @click="filterForm.activity_type = 'usage'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': filterForm.activity_type === 'usage' }">
                                            Mutasi (Keluar/Masuk)
                                        </button>
                                        <button @click="filterForm.activity_type = 'inspection'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': filterForm.activity_type === 'inspection' }">
                                            Inspeksi Fisik
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <button @click="openExportModal" class="hidden lg:flex bg-danger hover:bg-red-700 text-white px-4 py-2 rounded-md font-bold text-sm justify-center items-center gap-2 transition shadow-sm h-[38px] w-full sm:w-auto">
                            <FileDown class="w-4 h-4" /> Export PDF
                        </button>
                    </div>
                </div>
            </Card>

            <Card no-padding>
                
                <div v-if="activeTab === 'p3k'">
                    <DataTable :items="reports.data" :columns="columnsP3k">
                        <template #cell-record_date="{ item }">
                            <div class="flex items-center gap-1.5 text-ink-light">
                                <Calendar class="w-3.5 h-3.5 text-ink-light" />
                                <span class="text-sm font-medium">{{ formatDate(item.record_date) }}</span>
                            </div>
                        </template>

                        <template #cell-asset_code="{ item }">
                            <span class="font-bold text-ink">{{ item.asset_code }}</span>
                        </template>

                        <template #cell-action_type="{ item }">
                            <span :class="{
                                    'bg-primary/10 text-primary border-primary': item.action_type === 'PENAMBAHAN',
                                    'bg-danger/20 text-danger border-danger/30': item.action_type === 'PEMAKAIAN',
                                    'bg-warning/20 text-warning border-warning/30': item.action_type === 'INSPEKSI RUTIN'
                                }" 
                                class="px-2.5 py-1 rounded-full text-xs font-bold border flex items-center justify-center w-fit whitespace-nowrap">
                                {{ item.action_type }}
                            </span>
                        </template>

                        <template #cell-actor="{ item }">
                            <span class="text-sm font-medium text-ink dark:text-ink-dark/90">{{ item.actor }}</span>
                        </template>

                        <template #cell-details="{ item }">
                            <div class="whitespace-normal min-w-[200px] max-w-sm break-words leading-snug">
                                <span :class="item.details.includes('KRITIS') ? 'text-danger font-medium ' : 'text-ink font-medium'" class="block">
                                    {{ item.details.split('\n')[0] }}
                                </span>
                                <span v-if="item.details.split('\n')[1]" class="block text-[11px] mt-1.5 leading-tight italic text-ink-light">
                                    {{ item.details.split('\n')[1] }}
                                </span>
                            </div>
                        </template>
                    </DataTable>
                </div>

                <div v-else-if="activeTab === 'apar'" class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left text-ink-light leading-tight border-collapse">
                        <thead class="text-[11px] text-ink dark:text-ink-dark/90 uppercase bg-ghost border-b">
                            <tr>
                                <th class="px-2.5 py-2">No</th>
                                <th v-if="filterForm.asset_code === 'all'" class="px-2.5 py-2">Kode Aset</th>
                                <th class="px-2.5 py-2">Periode Pemeriksaan</th>
                                <th class="px-2.5 py-2">Pelapor PIC</th>
                                <th class="px-2.5 py-2">Hasil Inspeksi</th>
                                <th class="px-2.5 py-2 bg-primary/10 border-l border-primary/20">Validasi K3</th>
                                <th class="px-2.5 py-2 bg-primary/10">Tindakan</th>
                                <th class="px-2.5 py-2 bg-primary/10">Catatan</th>
                                <th class="px-2.5 py-2 bg-primary/10">Kondisi Akhir</th>
                            </tr>
                        </thead>
                        <tbody v-for="(k3Report, index) in reports.data" :key="k3Report.id" class="border-b last:border-0 hover:bg-ghost transition-colors">
                            <tr v-for="(picReport, picIndex) in k3Report.pic_reports" :key="picReport.id">
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-2.5 py-2 font-medium text-ink text-center border-r align-top">{{ index + 1 }}</td>
                                <td v-if="filterForm.asset_code === 'all' && picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-2.5 py-2 font-bold border-r align-top">{{ k3Report.asset_code }}</td>
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-2.5 py-2 border-r align-middle">
                                    <span class="inline-flex items-center gap-1 bg-primary/10 text-primary border border-primary rounded-full px-2 py-0.5 text-[10px] font-bold whitespace-nowrap">
                                        📅 {{ k3Report.periode_pemeriksaan }}
                                    </span>
                                </td>
                                <td class="px-2.5 py-2 border-r align-top">
                                    <div class="font-semibold text-ink">{{ picReport.actor }}</div>
                                    <div class="text-[10px] text-ink-light mt-0.5 flex items-center gap-1">
                                        <Calendar class="w-3 h-3"/> {{ formatDate(picReport.record_date) }}
                                    </div>
                                </td>
                                <td class="px-2.5 py-2 border-r align-top">
                                    <span :class="{'text-danger': picReport.status === 'KRITIS', 'text-success': picReport.status === 'SAFE', 'text-ink-light': picReport.status === '-'}" class="font-bold block text-[11px]">{{ picReport.status }}</span>
                                    <div v-if="picReport.status === 'KRITIS'" class="mt-1.5 p-1.5 bg-danger/10 border-l-2 border-danger/30 rounded-r shadow-sm">
                                        <span class="text-[9px] font-bold text-red-800 uppercase tracking-wider block mb-0.5">Temuan Kerusakan:</span>
                                        <span class="text-[10px] text-danger block whitespace-pre-line leading-tight">{{ picReport.details ? picReport.details.replace('Kondisi: KRITIS\nRincian: ', '') : '' }}</span>
                                    </div>
                                    <div v-else-if="picReport.status === 'SAFE'" class="mt-1.5 text-[10px] text-success font-medium italic">✓ Seluruh komponen standar normal.</div>
                                    <div v-else class="mt-1.5 text-[10px] text-ink-light font-medium italic">Belum ada laporan dari PIC.</div>
                                </td>
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-2.5 py-2 bg-primary/10/30 border-r align-top">
                                    <div class="font-bold text-indigo-900">{{ k3Report.actor_k3 }}</div>
                                    <div v-if="k3Report.record_date_k3" class="text-[10px] text-ink-light mt-0.5 flex items-center gap-1">
                                        <Calendar class="w-3 h-3"/> {{ formatDate(k3Report.record_date_k3) }}
                                    </div>
                                </td>
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-2.5 py-2 bg-primary/10/30 border-r align-middle text-center">
                                    <span :class="k3Report.status_penggantian === 'Diganti' ? 'bg-primary/10 text-blue-800 border-primary' : 'bg-ghost text-ink-light border-ghost-hover'" class="px-2 py-0.5 rounded-full text-[10px] font-bold border">{{ k3Report.status_penggantian }}</span>
                                </td>
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-2.5 py-2 bg-primary/10/30 border-r align-top text-ink dark:text-ink-dark/90 max-w-[150px] break-words">{{ k3Report.tindakan }}</td>
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length" class="px-2.5 py-2 bg-primary/10/30 align-middle text-center">
                                    <span :class="k3Report.kondisi_akhir === 'SAFE' ? 'bg-success/20 text-green-800 border-success/30' : 'bg-danger/20 text-red-800 border-danger/30'" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border inline-block">{{ k3Report.kondisi_akhir }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else-if="activeTab === 'hydrant'" class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left text-ink-light leading-tight border-collapse">
                        <thead class="text-[11px] text-ink dark:text-ink-dark/90 uppercase bg-ghost border-b">
                            <tr>
                                <th class="px-2.5 py-2 w-12 text-center">No</th>
                                <th v-if="filterForm.asset_code === 'all'" class="px-2.5 py-2">Kode Aset</th>
                                <th class="px-2.5 py-2">Periode Pemeriksaan</th>
                                <th class="px-2.5 py-2">Pelapor</th>
                                <th class="px-2.5 py-2">Hasil Inspeksi</th>
                                <th class="px-2.5 py-2 bg-primary/10 border-l border-teal-100">Catatan</th>
                                <th class="px-2.5 py-2 bg-primary/10 text-center">Kondisi Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(report, index) in reports.data" :key="report.id" class="border-b last:border-0 hover:bg-ghost transition-colors">
                                <td class="px-2.5 py-2 font-medium text-ink text-center align-top">
                                    {{ (reports.current_page - 1) * reports.per_page + index + 1 }}
                                </td>
                                
                                <td v-if="filterForm.asset_code === 'all'" class="px-2.5 py-2 font-bold align-top">
                                    {{ report.asset_code }}
                                </td>

                                <td class="px-2.5 py-2 align-middle">
                                    <span class="inline-flex items-center gap-1 bg-primary/10 text-primary border border-primary rounded-full px-2 py-0.5 text-[10px] font-bold whitespace-nowrap">
                                        📅 {{ report.periode_pemeriksaan }}
                                    </span>
                                </td>

                                <td class="px-2.5 py-2 align-top">
                                    <div class="font-semibold text-ink">{{ report.actor }}</div>
                                    <div class="text-[10px] text-ink-light mt-0.5 flex items-center gap-1">
                                        <Calendar class="w-3 h-3"/> {{ formatDate(report.record_date) }}
                                    </div>
                                </td>

                                <td class="px-2.5 py-2 align-top">
                                    <span :class="{'text-danger': report.status === 'KRITIS', 'text-success': report.status === 'SAFE'}" class="font-bold block text-[11px]">
                                        {{ report.status }}
                                    </span>
                                    
                                    <div v-if="report.status === 'KRITIS'" class="mt-1.5 p-1.5 bg-danger/10 border-l-2 border-danger/30 rounded-r shadow-sm">
                                        <span class="text-[9px] font-bold text-red-800 uppercase tracking-wider block mb-0.5">Temuan Kerusakan:</span>
                                        <span class="text-[10px] text-danger block whitespace-pre-line leading-tight">
                                            {{ report.details ? report.details.replace('Kondisi: KRITIS\nRincian: ', '') : '' }}
                                        </span>
                                    </div>
                                    
                                    <div v-else-if="report.status === 'SAFE'" class="mt-1.5 text-[10px] text-success font-medium italic">
                                        ✓ Seluruh komponen standar normal.
                                    </div>
                                </td>

                                <td class="px-2.5 py-2 bg-primary/10/30 align-top text-ink dark:text-ink-dark/90 max-w-[150px] break-words">
                                    {{ report.notes || '-' }}
                                </td>

                                <td class="px-2.5 py-2 bg-primary/10/30 align-middle text-center">
                                    <span :class="report.kondisi_akhir === 'SAFE' ? 'bg-success/20 text-green-800 border-success/30' : 'bg-danger/20 text-red-800 border-danger/30'" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border inline-block">
                                        {{ report.kondisi_akhir }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="reports.data.length === 0" class="p-4 text-center text-ink-light">
                    <span class="text-4xl block mb-3">📭</span>
                    Tidak ada data laporan pada rentang tanggal atau filter ini.
                </div>

                <div v-if="reports.data.length > 0" class="p-4 border-t border-ghost-hover">
                    <Pagination :links="reports.links" />
                </div>
            </Card>

        </div>
    </MainLayout>

    <div v-if="showExportModal" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-surface rounded-md shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            
            <div class="px-4 py-4 border-b border-ghost-hover flex justify-between items-center bg-ghost">
                <h3 class="font-bold text-ink flex items-center gap-2"><FileDown class="w-5 h-5 text-danger" /> Setting Export PDF</h3>
                <button @click="showExportModal = false" class="text-ink-light hover:text-danger transition"><X class="w-5 h-5" /></button>
            </div>

            <div class="p-4 space-y-4">
                
                <div>
                    <label class="block text-xs font-bold text-ink dark:text-ink-dark/90 mb-1">Aset Spesifik (Opsional)</label>
                    <select v-model="exportForm.asset_code" class="w-full rounded-md border-ghost-hover text-sm focus:ring-red-500">
                        <option value="all">Cetak Semua Aset {{ activeTab.toUpperCase() }}</option>
                        <option v-for="asset in currentAssets" :key="asset.code" :value="asset.code">{{ asset.code }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-ink dark:text-ink-dark/90 mb-1">Mulai Tanggal</label>
                        <input type="date" v-model="exportForm.start_date" class="w-full rounded-md border-ghost-hover text-sm focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-ink dark:text-ink-dark/90 mb-1">Sampai Tanggal</label>
                        <input type="date" v-model="exportForm.end_date" class="w-full rounded-md border-ghost-hover text-sm focus:ring-red-500">
                    </div>
                </div>

                <div v-if="activeTab === 'p3k'">
                    <label class="block text-xs font-bold text-ink dark:text-ink-dark/90 mb-1">Tipe Data yang Dicetak</label>
                    <select v-model="exportForm.activity_type" class="w-full rounded-md border-ghost-hover text-sm focus:ring-red-500">
                        <option value="all">Cetak Semua Aktivitas</option>
                        <option value="usage">Hanya Cetak Riwayat Stok</option>
                        <option value="inspection">Hanya Cetak Hasil Inspeksi Fisik</option>
                    </select>
                </div>
            </div>

            <div class="px-4 py-4 bg-ghost border-t border-ghost-hover flex justify-end gap-3">
                <button @click="showExportModal = false" class="px-4 py-2 text-sm font-bold text-ink-light hover:text-ink hover:bg-ghost rounded-md transition">Batal</button>
                <button @click="generatePdf" class="px-4 py-2 bg-danger hover:bg-red-700 text-white text-sm font-bold rounded-md shadow-md transition flex items-center gap-2">
                    Download PDF
                </button>
            </div>

        </div>
    </div>
</template>