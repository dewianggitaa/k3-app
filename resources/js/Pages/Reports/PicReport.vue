<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { FileBarChart, Box, ShieldAlert, Calendar, ChevronDown, Check } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    activeTab: String,
    assetsList: Object,
    filters: Object,
    reports: Object,
});

// ─── Filter state ──────────────────────────────────────────────────────────────
const filterForm = ref({
    year:       props.filters.year,
    month:      props.filters.month,
    asset_code: props.filters.asset_code || 'all',
});

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

// ─── Navigation ────────────────────────────────────────────────────────────────
const changeTab = (tabName) => {
    router.get(route('reports.pic'), {
        tab: tabName,
        ...filterForm.value,
    }, { preserveState: true, preserveScroll: true });
};

const applyFilter = () => {
    router.get(route('reports.pic'), {
        tab: props.activeTab,
        ...filterForm.value,
    }, { preserveState: true, preserveScroll: true });
};

// ─── Helpers ──────────────────────────────────────────────────────────────────
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const currentAssets = computed(() => props.assetsList[props.activeTab] || []);

// Status badge config for P3K
const p3kStatusConfig = {
    'Aman':          { bg: 'bg-emerald-100 text-emerald-700 border-emerald-200',   dot: 'bg-emerald-400' },
    'Perlu Ditambah': { bg: 'bg-red-100 text-red-700 border-red-200',            dot: 'bg-red-400' },
    'Sudah Ditambah': { bg: 'bg-blue-100 text-blue-700 border-blue-200',          dot: 'bg-blue-400' },
};

// Status badge config for APAR validation
const validationConfig = {
    'Sudah Divalidasi': { bg: 'bg-indigo-100 text-indigo-700 border-indigo-200', icon: '✓' },
    'Belum Divalidasi': { bg: 'bg-gray-100 text-gray-500 border-gray-200',       icon: '○' },
};

// Status badge config for APAR repair
const repairConfig = {
    'Aman':            { bg: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    'Perlu Perbaikan': { bg: 'bg-red-100 text-red-700 border-red-200' },
    'Sudah Diperbaiki': { bg: 'bg-blue-100 text-blue-700 border-blue-200' },
};
</script>

<template>
    <Head title="Laporan Inspeksi PIC" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center gap-4 px-4">
                <h2 class="font-bold text-lg text-gray-800 leading-tight flex items-center gap-2">
                    <FileBarChart class="w-5 h-5 text-indigo-500" /> Laporan Inspeksi PIC
                </h2>
            </div>
        </template>

        <div class="space-y-4">

            <!-- Tab navigation -->
            <div class="border-b border-gray-200 px-2">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button @click="changeTab('p3k')"
                        :class="activeTab === 'p3k' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition flex items-center gap-2">
                        <Box class="w-4 h-4" /> Kotak P3K
                    </button>
                    <button @click="changeTab('apar')"
                        :class="activeTab === 'apar' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm transition flex items-center gap-2">
                        <ShieldAlert class="w-4 h-4" /> Inspeksi APAR
                    </button>
                </nav>
            </div>

            <!-- ─── Filter card ──────────────────────────────────────────────── -->
            <Card no-padding class="p-4">
                <!-- Quick presets -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider self-center mr-1">Cepat:</span>
                    <button @click="applyPreset('this_month')"
                        :class="activePreset === 'this_month' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600'"
                        class="px-3 py-1 rounded-full text-xs font-semibold border transition">
                        Bulan Ini
                    </button>
                    <button @click="applyPreset('prev_month')"
                        :class="activePreset === 'prev_month' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600'"
                        class="px-3 py-1 rounded-full text-xs font-semibold border transition">
                        Bulan Lalu
                    </button>
                    <button @click="applyPreset('this_year')"
                        :class="activePreset === 'this_year' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:border-indigo-400 hover:text-indigo-600'"
                        class="px-3 py-1 rounded-full text-xs font-semibold border transition">
                        Tahun Ini
                    </button>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <!-- Year -->
                    <div class="w-28">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Tahun</label>
                        <div class="relative">
                            <select v-model="filterForm.year"
                                class="w-full appearance-none bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-2 pl-3 pr-8">
                                <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                            </select>
                            <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
                        </div>
                    </div>

                    <!-- Month -->
                    <div class="w-40">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Bulan</label>
                        <div class="relative">
                            <select v-model="filterForm.month"
                                class="w-full appearance-none bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-2 pl-3 pr-8">
                                <option v-for="opt in monthOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
                        </div>
                    </div>

                    <!-- Asset filter -->
                    <div class="w-44">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Aset Spesifik</label>
                        <div class="relative">
                            <select v-model="filterForm.asset_code"
                                class="w-full appearance-none bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 py-2 pl-3 pr-8">
                                <option value="all">Semua Aset</option>
                                <option v-for="asset in currentAssets" :key="asset.code" :value="asset.code">{{ asset.code }}</option>
                            </select>
                            <ChevronDown class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
                        </div>
                    </div>

                    <button @click="applyFilter"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-lg font-bold text-sm transition shadow-sm flex items-center gap-2">
                        <Check class="w-3.5 h-3.5" /> Terapkan
                    </button>
                </div>
            </Card>

            <!-- ─── Data table ───────────────────────────────────────────────── -->
            <Card no-padding>

                <!-- ══ P3K Tab ══════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'p3k'" class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left text-gray-500 leading-tight border-collapse">
                        <thead class="text-[11px] text-gray-700 uppercase bg-gray-100 border-b">
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
                                class="border-b last:border-0 hover:bg-gray-50/70 transition-colors align-top"
                                :class="row.event_type === 'usage' ? 'bg-amber-50/20' : ''">

                                <!-- No -->
                                <td class="px-2.5 py-2.5 text-center font-medium text-gray-400 text-[11px]">
                                    {{ (reports.current_page - 1) * reports.per_page + index + 1 }}
                                </td>

                                <!-- Aset + Lokasi -->
                                <td class="px-2.5 py-2.5 min-w-[130px]">
                                    <div class="font-bold text-gray-800 text-[12px]">{{ row.asset_code }}</div>
                                    <div v-if="row.location && row.location !== '-'" class="text-[9px] text-gray-400 mt-0.5 leading-tight">
                                        📍 {{ row.location }}
                                    </div>
                                    <!-- Tipe badge kecil -->
                                    <span :class="row.event_type === 'inspection'
                                            ? 'bg-violet-100 text-violet-600 border-violet-200'
                                            : 'bg-amber-100 text-amber-600 border-amber-200'"
                                        class="mt-1 inline-block px-1.5 py-px text-[9px] font-bold border rounded-sm uppercase tracking-wide">
                                        {{ row.event_type === 'inspection' ? 'Inspeksi' : 'Pemakaian' }}
                                    </span>
                                </td>

                                <!-- Pelapor -->
                                <td class="px-2.5 py-2.5 min-w-[120px]">
                                    <div class="font-semibold text-gray-700 text-[11px]">{{ row.reporter_name }}</div>
                                    <div v-if="row.reporter_dept && row.reporter_dept !== '-'"
                                        class="text-[10px] text-indigo-500 font-medium mt-0.5">
                                        {{ row.reporter_dept }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                                        <Calendar class="w-2.5 h-2.5 flex-shrink-0" />
                                        {{ formatDate(row.record_date) }}
                                    </div>
                                </td>

                                <!-- Laporan / Detail -->
                                <td class="px-2.5 py-2.5 min-w-[200px]">
                                    <!-- Inspection detail -->
                                    <template v-if="row.event_type === 'inspection'">
                                        <span :class="row.insp_status === 'KRITIS' ? 'text-red-600 bg-red-50 border-red-200' : 'text-emerald-600 bg-emerald-50 border-emerald-200'"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded border text-[10px] font-bold mb-1">
                                            {{ row.insp_status }}
                                        </span>
                                        <div v-if="row.insp_status === 'KRITIS' && row.details"
                                            class="mt-1 p-1.5 bg-red-50 border-l-2 border-red-400 rounded-r text-[10px] text-red-700 leading-snug whitespace-pre-line">
                                            {{ row.details.replace('Kondisi: KRITIS\nRincian: ', '') }}
                                        </div>
                                        <div v-else-if="row.insp_status === 'SAFE'" class="text-[10px] text-emerald-600 italic mt-0.5">
                                            ✓ Seluruh komponen standar normal.
                                        </div>
                                    </template>

                                    <!-- Pemakaian: compact item list -->
                                    <template v-else>
                                        <ul class="space-y-0.5">
                                            <li v-for="(item, i) in row.items_used" :key="i"
                                                class="flex items-baseline gap-1 text-[11px] text-gray-700">
                                                <span class="text-gray-400 select-none leading-none">·</span>
                                                <span class="font-medium">{{ item.name }}</span>
                                                <span class="text-gray-400">×{{ item.qty }}</span>
                                            </li>
                                        </ul>
                                    </template>
                                </td>

                                <!-- Status -->
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

                <!-- ══ APAR Tab ═════════════════════════════════════════════════ -->
                <div v-else-if="activeTab === 'apar'" class="overflow-x-auto w-full">
                    <table class="w-full text-xs text-left text-gray-500 leading-tight border-collapse">
                        <thead class="text-[11px] text-gray-700 uppercase bg-gray-100 border-b">
                            <tr>
                                <th class="px-2.5 py-2 w-10 text-center">No</th>
                                <th class="px-2.5 py-2">Kode Aset</th>
                                <th class="px-2.5 py-2">Periode</th>
                                <th class="px-2.5 py-2">Laporan PIC</th>
                                <th class="px-2.5 py-2 bg-indigo-50 border-l border-indigo-100 text-center">Status Validasi</th>
                                <th class="px-2.5 py-2 bg-indigo-50 text-center">Status Perbaikan</th>
                                <th class="px-2.5 py-2 bg-indigo-50">Validator K3</th>
                            </tr>
                        </thead>
                        <tbody v-for="(k3Report, index) in reports.data" :key="k3Report.id"
                            class="border-b last:border-0 hover:bg-gray-50 transition-colors">
                            <tr v-for="(picReport, picIndex) in k3Report.pic_reports" :key="picReport.id">

                                <!-- No (rowspan) -->
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 font-medium text-gray-900 text-center border-r align-top">
                                    {{ (reports.current_page - 1) * reports.per_page + index + 1 }}
                                </td>

                                <!-- Kode Aset (rowspan) -->
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 font-bold text-gray-800 border-r align-top">
                                    {{ k3Report.asset_code }}
                                </td>

                                <!-- Periode (rowspan) -->
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 border-r align-middle">
                                    <span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full px-2 py-0.5 text-[10px] font-bold whitespace-nowrap">
                                        📅 {{ k3Report.periode_pemeriksaan }}
                                    </span>
                                </td>

                                <!-- Laporan PIC -->
                                <td class="px-2.5 py-3 border-r align-top min-w-[160px]">
                                    <div v-if="picReport.actor === '-'" class="text-gray-400 italic text-[11px]">Belum ada laporan</div>
                                    <template v-else>
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <span :class="{'text-red-600': picReport.status === 'KRITIS', 'text-emerald-600': picReport.status === 'SAFE', 'text-gray-400': picReport.status === '-'}"
                                                class="font-bold text-[11px]">{{ picReport.status }}</span>
                                        </div>
                                        <div class="text-[10px] text-gray-600 font-medium">{{ picReport.actor }}</div>
                                        <div v-if="picReport.status === 'KRITIS'" class="mt-1 p-1 bg-red-50 border-l-2 border-red-400 rounded-r text-[10px] text-red-700 leading-tight">
                                            {{ picReport.details?.replace('Kondisi: KRITIS\nRincian: ', '') }}
                                        </div>
                                        <div v-if="picReport.record_date" class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                                            <Calendar class="w-2.5 h-2.5" /> {{ formatDate(picReport.record_date) }}
                                        </div>
                                    </template>
                                </td>

                                <!-- Status Validasi (rowspan) -->
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 bg-indigo-50/40 border-r align-middle text-center">
                                    <span :class="validationConfig[k3Report.validation_status]?.bg"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold border inline-flex items-center gap-1">
                                        <span>{{ validationConfig[k3Report.validation_status]?.icon }}</span>
                                        {{ k3Report.validation_status }}
                                    </span>
                                </td>

                                <!-- Status Perbaikan (rowspan) -->
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 bg-indigo-50/40 border-r align-middle text-center">
                                    <span :class="repairConfig[k3Report.repair_status]?.bg"
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold border inline-flex items-center gap-1">
                                        {{ k3Report.repair_status }}
                                    </span>
                                </td>

                                <!-- Validator K3 (rowspan) -->
                                <td v-if="picIndex === 0" :rowspan="k3Report.pic_reports.length"
                                    class="px-2.5 py-3 bg-indigo-50/40 align-top">
                                    <div class="font-semibold text-indigo-900 text-[11px]">{{ k3Report.actor_k3 }}</div>
                                    <div v-if="k3Report.record_date_k3" class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1">
                                        <Calendar class="w-2.5 h-2.5" /> {{ formatDate(k3Report.record_date_k3) }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-if="reports.data.length === 0" class="p-12 text-center text-gray-400">
                    <span class="text-4xl block mb-3">📭</span>
                    <p class="text-sm font-medium">Tidak ada data laporan pada filter ini.</p>
                    <p class="text-xs mt-1 text-gray-400">Coba ubah tahun, bulan, atau aset yang dipilih.</p>
                </div>

                <!-- Pagination -->
                <div v-if="reports.data.length > 0" class="p-4 border-t border-gray-100">
                    <Pagination :links="reports.links" />
                </div>
            </Card>

        </div>
    </MainLayout>
</template>
