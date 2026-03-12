<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { debounce } from 'lodash';
import { FileBarChart, Box, ShieldAlert, Calendar, ChevronDown, Filter, Search } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    activeTab: String,
    assetsList: Object,
    filters: Object,
    reports: Object,
});

const filterForm = ref({
    search:     props.filters.search || '',
    year:       props.filters.year,
    month:      props.filters.month,
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
    router.get(route('reports.pic'), {
        tab: props.activeTab,
        ...filterForm.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300), { deep: true });

const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: 4 }, (_, i) => currentYear - 1 + i);

const monthOptions = [
    { value: 'all', label: 'Semua Bulan' },
    { value: '1',  label: 'Januari' },
    { value: '2',  label: 'Februari' },
    { value: '3',  label: 'Maret' },
    { value: '4',  label: 'April' },
    { value: '5',  label: 'Mei' },
    { value: '6',  label: 'Juni' },
    { value: '7',  label: 'Juli' },
    { value: '8',  label: 'Agustus' },
    { value: '9',  label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

/** Quick preset buttons */
const applyPreset = (preset) => {
    const now = new Date();
    if (preset === 'this_month') {
        filterForm.value.year  = now.getFullYear();
        filterForm.value.month = String(now.getMonth() + 1);
    } else if (preset === 'this_year') {
        filterForm.value.year  = now.getFullYear();
        filterForm.value.month = 'all';
    } else if (preset === 'prev_month') {
        const d = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        filterForm.value.year  = d.getFullYear();
        filterForm.value.month = String(d.getMonth() + 1);
    }
    applyFilter();
};

const activePreset = computed(() => {
    const now = new Date();
    const y = filterForm.value.year;
    const m = filterForm.value.month;
    if (y === now.getFullYear() && m === String(now.getMonth() + 1)) return 'this_month';
    if (y === now.getFullYear() && m === 'all') return 'this_year';
    const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    if (y === prev.getFullYear() && m === String(prev.getMonth() + 1)) return 'prev_month';
    return null;
});

const changeTab = (tabName) => {
    router.get(route('reports.pic'), {
        tab: tabName,
        ...filterForm.value,
    }, { preserveState: true, preserveScroll: true });
};

const applyFilter = () => {};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const currentAssets = computed(() => props.assetsList[props.activeTab] || []);

const p3kStatusConfig = {
    'Aman':          { bg: 'bg-success/20 text-success border-success/30',   dot: 'bg-emerald-400' },
    'Belum Ditambah': { bg: 'bg-danger/20 text-danger border-danger/30',            dot: 'bg-red-400' },
    'Sudah Ditambah': { bg: 'bg-primary/10 text-primary border-primary',          dot: 'bg-blue-400' },
};

const validationConfig = {
    'Sudah Divalidasi': { bg: 'bg-primary/10 text-primary border-primary', icon: '✓' },
    'Belum Divalidasi': { bg: 'bg-ghost text-ink-light border-ghost-hover',       icon: '○' },
};

const repairConfig = {
    'Aman':            { bg: 'bg-success/20 text-success border-success/30' },
    'Perlu Perbaikan': { bg: 'bg-danger/20 text-danger border-danger/30' },
    'Sudah Diperbaiki': { bg: 'bg-primary/10 text-primary border-primary' },
};
</script>

<template>
    <Head title="Laporan Inspeksi PIC" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center gap-4 px-4">
                <h2 class="font-bold text-lg text-ink leading-tight flex items-center gap-2">
                    <FileBarChart class="w-5 h-5 text-primary" /> Status Laporan
                </h2>
            </div>
        </template>

        <template #header-nav>
            <div class="px-4 bg-surface dark:bg-surface-dark overflow-x-auto scrollbar-hide border-b border-ghost-hover custom-scrollbar w-full">
                <nav class="-mb-px flex space-x-6">
                    <button @click="changeTab('p3k')"
                        :class="activeTab === 'p3k' ? 'border-primary text-primary' : 'border-transparent text-ink-light hover:text-ink dark:text-ink-dark/90'"
                        class="whitespace-nowrap pb-3 border-b-2 font-bold text-sm transition flex items-center gap-2 pt-1">
                        <Box class="w-4 h-4" /> Kotak P3K
                    </button>
                    <button @click="changeTab('apar')"
                        :class="activeTab === 'apar' ? 'border-primary text-primary' : 'border-transparent text-ink-light hover:text-ink dark:text-ink-dark/90'"
                        class="whitespace-nowrap pb-3 border-b-2 font-bold text-sm transition flex items-center gap-2 pt-1">
                        <ShieldAlert class="w-4 h-4" /> Inspeksi APAR
                    </button>
                </nav>
            </div>
        </template>

        <div class="space-y-4">

            <Card ref="filterWrapper" no-padding class="p-4 overflow-visible" overflow-visible>
                
                <div class="flex flex-col xl:flex-row-reverse gap-4 justify-between items-start xl:items-end w-full">
                    
                    <div class="flex flex-row gap-2 w-full xl:w-auto shrink-0 xl:mb-[3px]">
                        <div class="flex-1 min-w-0 xl:flex-none xl:w-64">
                            <SearchInput v-model="filterForm.search" placeholder="Cari laporan..." />
                        </div>
                        <button @click="showFilters = !showFilters" class="xl:hidden p-2 bg-ghost border border-ghost-hover hover:border-primary text-ink-light rounded-md flex items-center justify-center h-[38px] w-[38px] shrink-0 transition-colors">
                            <Filter class="w-4 h-4" />
                        </button>
                    </div>

                    <div :class="[showFilters ? 'flex' : 'hidden', 'xl:flex flex-col md:flex-row flex-wrap gap-3 w-full xl:w-auto items-end']">

                        <div class="w-full sm:w-28 flex-shrink-0">
                            <label class="block text-xs font-bold text-ink-light mb-1">Tahun</label>
                            <Dropdown align="right" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors cursor-pointer outline-none w-full">
                                        <span class="truncate">{{ filterForm.year }}</span>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                            <ChevronDown class="w-3.5 h-3.5" />
                                        </div>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1 max-h-60 overflow-y-auto">
                                        <button v-for="y in yearOptions" :key="y" @click="filterForm.year = y" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': filterForm.year === y }">
                                            {{ y }}
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <div class="w-full sm:w-40 flex-shrink-0">
                            <label class="block text-xs font-bold text-ink-light mb-1">Bulan</label>
                            <Dropdown align="right" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors cursor-pointer outline-none w-full">
                                        <span class="truncate">{{ monthOptions.find(m => m.value == filterForm.month)?.label || 'Semua Bulan' }}</span>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                            <ChevronDown class="w-3.5 h-3.5" />
                                        </div>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1 max-h-60 overflow-y-auto">
                                        <button v-for="opt in monthOptions" :key="opt.value" @click="filterForm.month = opt.value" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': filterForm.month == opt.value }">
                                            {{ opt.label }}
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <div class="w-full sm:w-44 lg:w-48 flex-shrink-0">
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
                    </div>
                </div>
            </Card>

            <Card no-padding>

                <div v-if="activeTab === 'p3k'" class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left text-ink-light leading-tight border-collapse">
                        <thead class="text-[11px] text-ink dark:text-ink-dark/90 uppercase bg-ghost border-b">
                            <tr>
                                <th class="px-2.5 py-2 w-10 text-center">No</th>
                                <th class="px-2.5 py-2">Aset & Lokasi</th>
                                <th class="px-2.5 py-2">Pelapor</th>
                                <th class="px-2.5 py-2">Laporan</th>
                                <th class="px-2.5 py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in reports.data" :key="row.id"
                                class="border-b last:border-0 hover:bg-ghost/70 transition-colors align-top"
                                :class="row.event_type === 'usage' ? 'bg-warning/10/20' : ''">

                                <td class="px-2.5 py-2.5 text-center font-medium text-ink-light text-[11px]">
                                    {{ (reports.current_page - 1) * reports.per_page + index + 1 }}
                                </td>

                                <td class="px-2.5 py-2.5 min-w-[130px]">
                                    <div class="font-bold text-ink text-[12px]">{{ row.asset_code }}</div>
                                    <div v-if="row.location && row.location !== '-'" class="text-[9px] text-ink-light mt-0.5 leading-tight">
                                        📍 {{ row.location }}
                                    </div>
                                    
                                    <span :class="row.event_type === 'inspection'
                                            ? 'bg-violet-100 text-violet-600 border-violet-200'
                                            : 'bg-warning/20 text-warning border-warning/30'"
                                        class="mt-1 inline-block px-1.5 py-px text-[9px] font-bold border rounded-md uppercase tracking-wide">
                                        {{ row.event_type === 'inspection' ? 'Inspeksi' : 'Pemakaian' }}
                                    </span>
                                </td>

                                <td class="px-2.5 py-2.5 min-w-[120px]">
                                    <div class="font-semibold text-ink dark:text-ink-dark/90 text-[11px]">{{ row.reporter_name }}</div>
                                    <div v-if="row.reporter_dept && row.reporter_dept !== '-'"
                                        class="text-[10px] text-primary font-medium mt-0.5">
                                        {{ row.reporter_dept }}
                                    </div>
                                    <div class="text-[10px] text-ink-light mt-1 flex items-center gap-1">
                                        <Calendar class="w-2.5 h-2.5 flex-shrink-0" />
                                        {{ formatDate(row.record_date) }}
                                    </div>
                                </td>

                                <td class="px-2.5 py-2.5 min-w-[200px]">
                                    
                                    <template v-if="row.event_type === 'inspection'">
                                        <span :class="row.insp_status === 'KRITIS' ? 'text-danger bg-danger/10 border-danger/30' : 'text-success bg-success/10 border-success/30'"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[10px] font-bold mb-1">
                                            {{ row.insp_status }}
                                        </span>
                                        <div v-if="row.insp_status === 'KRITIS' && row.details"
                                            class="mt-1 p-1.5 bg-danger/10 border-l-2 border-red-400 rounded-r text-[10px] text-danger leading-snug whitespace-pre-line">
                                            {{ row.details.replace('Kondisi: KRITIS\nRincian: ', '') }}
                                        </div>
                                        <div v-else-if="row.insp_status === 'SAFE'" class="text-[10px] text-success italic mt-0.5">
                                            ✓ Seluruh komponen standar normal.
                                        </div>
                                    </template>

                                    <template v-else>
                                        <ul class="space-y-0.5">
                                            <li v-for="(item, i) in row.items_used" :key="i"
                                                class="flex items-baseline gap-1 text-[11px] text-ink dark:text-ink-dark/90">
                                                <span class="text-ink-light select-none leading-none">·</span>
                                                <span class="font-medium">{{ item.name }}</span>
                                                <span class="text-ink-light">×{{ item.qty }}</span>
                                            </li>
                                        </ul>
                                    </template>
                                </td>

                                <td class="px-2.5 py-2.5 text-center">
                                    <span :class="p3kStatusConfig[row.status]?.bg"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border whitespace-nowrap">
                                        <span :class="p3kStatusConfig[row.status]?.dot" class="w-1.5 h-1.5 rounded-full inline-block flex-shrink-0"></span>
                                        {{ row.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else-if="activeTab === 'apar'" class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left text-ink-light leading-tight border-collapse">
                        <thead class="text-[11px] text-ink dark:text-ink-dark/90 uppercase bg-ghost border-b">
                            <tr>
                                <th class="px-2.5 py-2 w-10 text-center">No</th>
                                <th class="px-2.5 py-2">Kode Aset</th>
                                <th class="px-2.5 py-2">Periode</th>
                                <th class="px-2.5 py-2">Laporan PIC</th>
                                <th class="px-2.5 py-2 bg-primary/10 border-l border-primary/20 text-center">Status Validasi</th>
                                <th class="px-2.5 py-2 bg-primary/10 text-center">Status Perbaikan</th>
                                <th class="px-2.5 py-2 bg-primary/10">Validator K3</th>
                            </tr>
                        </thead>
                        <tbody v-for="(k3Report, index) in reports.data" :key="k3Report.id"
                            class="border-b last:border-0 hover:bg-ghost transition-colors">
                            <tr v-for="(picReport, picIndex) in k3Report.pic_reports" :key="picReport.id">

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 font-medium text-ink text-center border-r align-top">
                                    {{ (reports.current_page - 1) * reports.per_page + index + 1 }}
                                </td>

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 font-bold text-ink border-r align-top">
                                    {{ k3Report.asset_code }}
                                </td>

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 border-r align-middle">
                                    <span class="inline-flex items-center gap-1 bg-primary/10 text-primary border border-primary rounded-full px-2 py-0.5 text-[10px] font-bold whitespace-nowrap">
                                        📅 {{ k3Report.periode_pemeriksaan }}
                                    </span>
                                </td>

                                <td class="px-2.5 py-3 border-r align-top min-w-[160px]">
                                    <div v-if="picReport.actor === '-'" class="text-ink-light italic text-[11px]">Belum ada laporan</div>
                                    <template v-else>
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <span :class="{'text-danger': picReport.status === 'KRITIS', 'text-success': picReport.status === 'SAFE', 'text-ink-light': picReport.status === '-'}"
                                                class="font-bold text-[11px]">{{ picReport.status }}</span>
                                        </div>
                                        <div class="text-[10px] text-ink-light font-medium">{{ picReport.actor }}</div>
                                        <div v-if="picReport.status === 'KRITIS'" class="mt-1 p-1 bg-danger/10 border-l-2 border-red-400 rounded-r text-[10px] text-danger leading-tight">
                                            {{ picReport.details?.replace('Kondisi: KRITIS\nRincian: ', '') }}
                                        </div>
                                        <div v-if="picReport.record_date" class="text-[10px] text-ink-light mt-0.5 flex items-center gap-1">
                                            <Calendar class="w-2.5 h-2.5" /> {{ formatDate(picReport.record_date) }}
                                        </div>
                                    </template>
                                </td>

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 bg-primary/10/40 border-r align-middle text-center">
                                    <span :class="validationConfig[k3Report.validation_status]?.bg"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold border inline-flex items-center gap-1">
                                        <span>{{ validationConfig[k3Report.validation_status]?.icon }}</span>
                                        {{ k3Report.validation_status }}
                                    </span>
                                </td>

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 bg-primary/10/40 border-r align-middle text-center">
                                    <span :class="repairConfig[k3Report.repair_status]?.bg"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold border inline-flex items-center gap-1">
                                        {{ k3Report.repair_status }}
                                    </span>
                                </td>

                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 bg-primary/10/40 align-top">
                                    <div class="font-semibold text-indigo-900 text-[11px]">{{ k3Report.actor_k3 }}</div>
                                    <div v-if="k3Report.record_date_k3" class="text-[10px] text-ink-light mt-0.5 flex items-center gap-1">
                                        <Calendar class="w-2.5 h-2.5" /> {{ formatDate(k3Report.record_date_k3) }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="reports.data.length === 0" class="p-4 text-center text-ink-light">
                    <span class="text-4xl block mb-3">📭</span>
                    <p class="text-sm font-medium">Tidak ada data laporan pada filter ini.</p>
                    <p class="text-xs mt-1 text-ink-light">Coba ubah tahun, bulan, atau aset yang dipilih.</p>
                </div>

                <div v-if="reports.data.length > 0" class="p-4 border-t border-ghost-hover">
                    <Pagination :links="reports.links" />
                </div>
            </Card>

        </div>
    </MainLayout>
</template>
