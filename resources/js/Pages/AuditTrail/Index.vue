<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import MainLayout from '@/Layouts/MainLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Dropdown from '@/Components/Dropdown.vue';
import SearchInput from '@/Components/SearchInput.vue';
import {
    ClipboardList, ChevronRight, ChevronDown, SlidersHorizontal,
    Calendar, Filter
} from 'lucide-vue-next';

const props = defineProps({
    logs: Object,
    categories: Array,
    filters: Object,
});

const search   = ref(props.filters.search   || '');
const category = ref(props.filters.category || 'all');
const event    = ref(props.filters.event    || 'all');
const range    = ref(props.filters.range    || 'all');

const showFilters = ref(false);

const expandedRows = ref(new Set());

const applyFilters = debounce(() => {
    router.get(route('audit-trail.index'), {
        search:   search.value   || undefined,
        category: category.value !== 'all' ? category.value : undefined,
        event:    event.value    !== 'all' ? event.value    : undefined,
        range:    range.value    !== 'all' ? range.value    : undefined,
    }, { preserveState: true, replace: true });
}, 350);

watch([category, event, range], () => applyFilters());
watch(search, () => applyFilters());

const groupedLogs = computed(() => {
    const groups = {};
    (props.logs.data || []).forEach(log => {

        const dateObj = new Date(log.created_at.endsWith('Z') ? log.created_at : log.created_at + 'Z');
        const date = dateObj.toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
        if (!groups[date]) groups[date] = [];
        groups[date].push(log);
    });
    return groups;
});

const toggleRow = (id) => {
    if (expandedRows.value.has(id)) expandedRows.value.delete(id);
    else expandedRows.value.add(id);

    expandedRows.value = new Set(expandedRows.value);
};

const isComplexValue = (val) => {
    if (val === null || val === undefined || val === '-') return false;
    if (typeof val === 'object') return true;
    if (String(val).length > 50) return true;
    if (String(val).includes('\n')) return true;
    return false;
};
const parseProps = (val) => {
    if (!val) return {};
    if (typeof val === 'string') { try { return JSON.parse(val); } catch { return {}; } }
    return val;
};

const formatValue = (val) => {
    if (val === null || val === undefined) return '-';
    if (typeof val === 'object') return JSON.stringify(val);
    return String(val);
};

const getOldValues = (log) => parseProps(log.properties)?.old || {};
const getNewValues = (log) => parseProps(log.properties)?.attributes || {};

const getDirtyValues = (log) => {
    if (log.event !== 'updated') return { old: getOldValues(log), new: getNewValues(log) };
    
    const oldVals = getOldValues(log);
    const newVals = getNewValues(log);
    const dirtyOld = {};
    const dirtyNew = {};
    
    Object.keys(newVals).forEach(key => {

        if (oldVals[key] != newVals[key]) {
            dirtyOld[key] = oldVals[key];
            dirtyNew[key] = newVals[key];
        }
    });
    
    return { old: dirtyOld, new: dirtyNew };
};

const formatModelName = (path) => {
    if (!path) return '-';
    return path.split('\\').pop().replace(/[_-]/g, ' ');
};

const categoryLabel = (name) => {
    const map = {
        'master-data':     'Master Data',
        'aset':            'Aset',
        'jadwal-inspeksi': 'Jadwal & Inspeksi',
        'aktivitas-sistem':'Aktivitas Sistem',
        'default':         'Sistem',
    };
    return map[name] || name;
};

const categoryPillColor = (name) => {
    const map = {
        'master-data':      'bg-primary/10 text-primary border-primary/30',
        'aset':             'bg-primary/10 text-primary border-primary/30',
        'jadwal-inspeksi':  'bg-primary/10 text-primary border-primary/30',
        'aktivitas-sistem': 'bg-primary/10 text-primary border-primary/30',
        'default':          'bg-primary/10 text-primary border-primary/30',
    };
    return map[name] || 'bg-ghost dark:bg-ghost-dark text-ink-light border-ghost-hover dark:border-ghost-dark';
};

const eventStyle = (ev) => {
    const map = {
        created: { badge: 'bg-success/20 text-success border-success/30', dot: 'bg-success' },
        updated: { badge: 'bg-warning/20 text-warning border-warning/30',       dot: 'bg-warning'   },
        deleted: { badge: 'bg-danger/20 text-danger border-danger/30',             dot: 'bg-danger'     },
        login:   { badge: 'bg-primary/10 text-primary border-primary',          dot: 'bg-primary'    },
        logout:  { badge: 'bg-ghost text-ink-light border-ghost-hover',          dot: 'bg-gray-400'    },
    };
    return map[ev] || { badge: 'bg-ghost text-ink-light border-ghost-hover', dot: 'bg-gray-400' };
};

const formatTime = (ts) => {

    const dateObj = new Date(ts.endsWith('Z') ? ts : ts + 'Z');
    return dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const formatSummary = (log) => {
    if (log.event === 'created') {
        const desc = log.description;
        if (desc && desc !== 'created' && !desc.toLowerCase().endsWith('created')) {
            return desc;
        }
        return 'Menambahkan data baru';
    }
    if (log.event === 'deleted') return 'Menghapus data permanen';
    if (log.event === 'login')  return 'Masuk ke dalam sistem';
    if (log.event === 'logout') return 'Keluar dari sistem';
    if (log.event === 'updated') {
        const dirty = getDirtyValues(log).new;
        const keys = Object.keys(dirty);
        if (!keys.length) return 'Melakukan perubahan data (tidak ada data yang berubah)';
        return `Mengubah: ${keys.map(k => k.replace(/_/g, ' ')).join(', ')}`;
    }
    return log.description || log.event || '-';
};

const rangeOptions = [
    { value: 'all',    label: 'Semua Waktu' },
    { value: 'today',  label: 'Hari Ini'    },
    { value: '7days',  label: '7 Hari'      },
    { value: '30days', label: '30 Hari'     },
];

const eventOptions = [
    { value: 'all',     label: 'Semua Aksi' },
    { value: 'created', label: 'Dibuat'     },
    { value: 'updated', label: 'Diupdate'   },
    { value: 'deleted', label: 'Dihapus'    },
];
</script>

<template>
    <Head title="Audit Trail" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center gap-2">
                <ClipboardList class="w-5 h-5 text-primary" />
                <h1 class="text-lg font-bold text-ink dark:text-ink-dark">Audit Trail</h1>
            </div>
        </template>

        <div class="w-full px-2 pb-8">

            <div class="bg-surface dark:bg-surface-dark border border-ghost dark:border-ghost-dark bg-white rounded-md p-4 mb-6 shadow-sm sticky top-2 z-20">
                <div class="flex flex-col xl:flex-row-reverse gap-4 justify-between items-start xl:items-center">

                    <div class="flex w-full xl:w-auto gap-2">
                        <div class="flex-1 min-w-0 xl:flex-none xl:w-64">
                            <SearchInput v-model="search" placeholder="Cari nama atau rincian..." />
                        </div>
                        <button @click="showFilters = !showFilters" class="xl:hidden p-2 bg-ghost border border-ghost-hover hover:border-primary text-ink-light rounded-md flex items-center justify-center h-[38px] w-[38px] shrink-0 transition-colors">
                            <Filter class="w-4 h-4" />
                        </button>
                    </div>

                    <div :class="[showFilters ? 'flex' : 'hidden', 'xl:flex flex-col xl:flex-row flex-wrap gap-3 w-full xl:w-auto items-start xl:items-center']">
                        
                        <div class="flex items-center flex-wrap gap-2 w-full xl:w-auto border-b xl:border-b-0 xl:border-r border-ghost-hover pb-3 xl:pb-0 xl:pr-3">
                            <div class="flex gap-1.5 flex-wrap">
                                <button
                                    @click="category = 'all'"
                                    :class="['px-3 py-1 rounded-full text-[11px] font-bold border transition-all whitespace-nowrap outline-none', category === 'all' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-surface dark:bg-surface-dark text-ink-light dark:text-ink-light border-ghost-hover dark:border-ghost-dark hover:bg-ghost dark:hover:bg-ghost-dark']"
                                >
                                    Semua
                                </button>
                                <button
                                    v-for="cat in categories" :key="cat"
                                    @click="category = cat"
                                    :class="['px-3 py-1 rounded-full text-[11px] font-bold border transition-all whitespace-nowrap outline-none', category === cat ? 'bg-primary text-white border-primary shadow-sm' : 'bg-surface dark:bg-surface-dark text-ink-light dark:text-ink-light border-ghost-hover dark:border-ghost-dark hover:bg-ghost dark:hover:bg-ghost-dark']"
                                >
                                    {{ categoryLabel(cat) }}
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row w-full xl:w-auto gap-3 items-center pt-1 xl:pt-0">
                            
                            <div class="w-full sm:w-48 flex-shrink-0">
                                <Dropdown align="left" width="full">
                                    <template #trigger>
                                        <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors outline-none cursor-pointer">
                                            <div class="flex items-center gap-2 truncate whitespace-nowrap">
                                                <SlidersHorizontal class="w-3.5 h-3.5 text-ink-light shrink-0" />
                                                <span class="truncate">{{ eventOptions.find(e => e.value === event)?.label || 'Semua Aksi' }}</span>
                                            </div>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                                <ChevronDown class="w-3.5 h-3.5" />
                                            </div>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="py-1">
                                            <button v-for="opt in eventOptions" :key="opt.value" @click="event = opt.value" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': event === opt.value }">
                                                {{ opt.label }}
                                            </button>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <div class="w-full sm:w-48 flex-shrink-0">
                                <Dropdown align="left" width="full">
                                    <template #trigger>
                                        <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors outline-none cursor-pointer">
                                            <div class="flex items-center gap-2 truncate whitespace-nowrap">
                                                <Calendar class="w-3.5 h-3.5 text-ink-light shrink-0" />
                                                <span class="truncate">{{ rangeOptions.find(r => r.value === range)?.label || 'Semua Waktu' }}</span>
                                            </div>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light">
                                                <ChevronDown class="w-3.5 h-3.5" />
                                            </div>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="py-1">
                                            <button v-for="opt in rangeOptions" :key="opt.value" @click="range = opt.value" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': range === opt.value }">
                                                {{ opt.label }}
                                            </button>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div v-if="!logs.data.length" class="text-center py-16 text-ink-light dark:text-ink-light">
                    <ClipboardList class="w-12 h-12 mx-auto mb-3 opacity-30" />
                    <p class="text-sm">Belum ada aktivitas yang tercatat.</p>
                </div>

                <div v-for="(groupLogs, date) in groupedLogs" :key="date" class="relative">

                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-2 h-2 rounded-full bg-indigo-400 flex-shrink-0"></div>
                        <span class="text-xs font-bold text-ink-light dark:text-ink-light uppercase tracking-widest bg-surface dark:bg-page-dark px-2">
                            {{ date }}
                        </span>
                        <div class="flex-1 h-px bg-ghost dark:bg-gray-700"></div>
                        <span class="text-[11px] text-ink-light dark:text-ink-light font-mono">{{ groupLogs.length }} aktivitas</span>
                    </div>

                    <div class="ml-3 pl-4 border-l-2 border-ghost-hover dark:border-ghost-dark space-y-2">
                        <div v-for="log in groupLogs" :key="log.id" class="relative group">

                            <span
                                :class="['absolute -left-[21px] top-4 h-2.5 w-2.5 rounded-full border-2 border-white dark:border-ghost-dark transition-all group-hover:scale-125', eventStyle(log.event).dot]"
                            ></span>

                            <div
                                @click="toggleRow(log.id)"
                                class="bg-surface dark:bg-surface-dark border border-ghost-hover dark:border-ghost-dark rounded-md p-2.5 hover:border-primary dark:hover:border-indigo-700 hover:shadow-sm transition-all duration-150 cursor-pointer select-none"
                                :class="{ 'border-primary dark:border-indigo-700 shadow-sm': expandedRows.has(log.id) }"
                            >
                                
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <ChevronRight
                                            :class="['w-4 h-4 text-gray-300 transition-transform duration-200 flex-shrink-0', expandedRows.has(log.id) ? 'rotate-90 text-primary-dark' : '']"
                                        />

                                        <div class="w-6 h-6 rounded-full bg-primary/10 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                                            <span class="text-[9px] font-bold text-primary dark:text-primary-light uppercase">
                                                {{ (log.causer?.name || getNewValues(log).pelapor || parseProps(log.properties).attempted_username || 'S').charAt(0) }}
                                            </span>
                                        </div>

                                        <span class="text-sm font-bold text-ink dark:text-ink-dark">
                                            {{ log.causer?.name || getNewValues(log).pelapor || parseProps(log.properties).attempted_username || 'System' }}
                                        </span>

                                        <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide border', eventStyle(log.event).badge]">
                                            {{ log.event }}
                                        </span>

                                        <span v-if="log.subject_type" class="inline-flex items-center gap-1 bg-ghost dark:bg-surface-dark px-2 py-0.5 rounded-md border border-ghost-hover dark:border-ghost-dark text-[10px] font-bold text-ink-light dark:text-ink-light">
                                            {{ formatModelName(log.subject_type) }}
                                            <span class="text-primary font-mono">#{{ log.subject_id }}</span>
                                        </span>

                                        <span class="text-[11px] text-ink-light dark:text-ink-light break-words flex-1">
                                            {{ formatSummary(log) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 flex-shrink-0 pl-6 sm:pl-0">
                                        
                                        <span v-if="log.log_name" :class="['text-[9px] font-semibold px-1.5 py-0.5 rounded-full border', categoryPillColor(log.log_name)]">
                                            {{ categoryLabel(log.log_name) }}
                                        </span>
                                        
                                        <span class="text-xs text-ink-light dark:text-ink-light font-mono whitespace-nowrap">
                                            {{ formatTime(log.created_at) }}
                                        </span>
                                    </div>
                                </div>

                                <Transition
                                    enter-active-class="transition-all duration-200 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-150 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1"
                                >
                                    <div v-if="expandedRows.has(log.id)" class="mt-3 pt-3 border-t border-ghost-hover dark:border-ghost-dark">

                                        <div class="mb-1.5 flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-ink-light dark:text-ink-light bg-ghost dark:bg-surface-dark/50 rounded flex-row p-1 border border-ghost-hover dark:border-ghost-dark">
                                            <span><span class="font-medium text-ink-light dark:text-ink-light">Model:</span> {{ log.subject_type || '-' }}</span>
                                            <span><span class="font-medium text-ink-light dark:text-ink-light">ID:</span> <span class="font-mono text-primary">#{{ log.subject_id || '-' }}</span></span>
                                            <span v-if="log.ip_address"><span class="font-medium text-ink-light dark:text-ink-light">IP:</span> {{ log.ip_address }}</span>
                                        </div>

                                        <div v-if="log.event === 'created'" class="bg-success/10/50 dark:bg-emerald-900/10 rounded p-2 border border-emerald-100/50 dark:border-emerald-800/50">
                                            <div class="flex flex-col gap-1.5">
                                                <div v-for="(val, key) in getNewValues(log)" :key="key" class="flex flex-col border-b border-emerald-100/30 dark:border-emerald-800/30 pb-1.5 last:border-0 last:pb-0">
                                                    <span class="text-success dark:text-success text-[10px] font-medium capitalize mb-0.5">{{ key.replace(/_/g, ' ') }}</span>
                                                    <div class="text-ink dark:text-ink-dark/90 dark:text-ink-light font-mono text-[9.5px] whitespace-pre-wrap break-all">{{ formatValue(val) }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else-if="log.event === 'deleted'" class="bg-danger/10/50 dark:bg-red-900/10 rounded p-2 border border-red-100/50 dark:border-red-800/50">
                                            <div class="flex flex-col gap-1.5">
                                                <div v-for="(val, key) in getOldValues(log)" :key="key" class="flex flex-col border-b border-red-100/30 dark:border-red-800/30 pb-1.5 last:border-0 last:pb-0">
                                                    <span class="text-danger dark:text-danger/80 text-[10px] font-medium capitalize mb-0.5">{{ key.replace(/_/g, ' ') }}</span>
                                                    <div class="text-ink dark:text-ink-dark/90 dark:text-ink-light font-mono text-[9.5px] whitespace-pre-wrap break-all">{{ formatValue(val) }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else-if="log.event === 'updated'" class="space-y-1.5">
                                            <div
                                                v-for="(newVal, key) in getDirtyValues(log).new" :key="key"
                                                class="flex flex-col gap-1 bg-ghost/50 dark:bg-surface-dark/20 px-2.5 py-1.5 rounded border border-ghost-hover dark:border-ghost-dark/50"
                                            >
                                                <span class="text-[10px] font-medium text-ink-light dark:text-ink-light capitalize">{{ key.replace(/_/g, ' ') }}</span>
                                                
                                                <div v-if="isComplexValue(getDirtyValues(log).old[key]) || isComplexValue(newVal)" class="flex flex-col sm:flex-row gap-1 w-full">
                                                    <div v-if="getDirtyValues(log).old[key] !== null && getDirtyValues(log).old[key] !== undefined && getDirtyValues(log).old[key] !== '-'" class="flex-1 flex flex-col items-start overflow-hidden">
                                                        <div class="w-full whitespace-pre-wrap break-all bg-danger/10 dark:bg-red-900/10 text-danger dark:text-danger/80 px-1.5 py-1 rounded text-[9.5px] font-mono border border-red-100/50 dark:border-red-800/30 overflow-x-auto leading-tight">
                                                            {{ formatValue(getDirtyValues(log).old[key]) }}
                                                        </div>
                                                    </div>
                                                    <div class="hidden sm:flex items-center justify-center shrink-0">
                                                        <ChevronRight class="w-2.5 h-2.5 text-slate-300 dark:text-ink-light" />
                                                    </div>
                                                    <div class="flex-1 flex flex-col items-start overflow-hidden">
                                                        <div class="w-full whitespace-pre-wrap break-all bg-success/10 dark:bg-emerald-900/10 text-success dark:text-success/80 px-1.5 py-1 rounded text-[9.5px] font-mono font-medium border border-emerald-100/50 dark:border-emerald-800/30 overflow-x-auto leading-tight">
                                                            {{ formatValue(newVal) }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div v-else class="flex flex-row items-center flex-wrap gap-1.5 w-full">
                                                    <div class="bg-danger/10 dark:bg-red-900/10 text-danger dark:text-danger/80 px-1.5 py-0.5 rounded text-[10px] font-mono">
                                                        {{ formatValue(getDirtyValues(log).old[key]) }}
                                                    </div>
                                                    <ChevronRight class="w-3 h-3 text-slate-300 dark:text-ink-light shrink-0" />
                                                    <div class="bg-success/10 dark:bg-emerald-900/10 text-success dark:text-success/80 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium">
                                                        {{ formatValue(newVal) }}
                                                    </div>
                                                </div>
                                            </div>
                                            <p v-if="!Object.keys(getDirtyValues(log).new).length" class="text-ink-light italic text-[10px]">
                                                Tidak ada detail perubahan (atau tidak terdeteksi oleh sistem).
                                            </p>
                                        </div>

                                        <div v-else class="text-xs text-ink-light dark:text-ink-light italic px-1">
                                            {{ log.description || 'Tidak ada detail tambahan.' }}
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Pagination :links="logs.links" :meta="logs" />
        </div>
    </MainLayout>
</template>
