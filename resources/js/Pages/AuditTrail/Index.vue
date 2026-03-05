<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import MainLayout from '@/Layouts/MainLayout.vue';
import {
    ClipboardList, ChevronRight, Search, SlidersHorizontal,
    Calendar, Filter
} from 'lucide-vue-next';

const props = defineProps({
    logs: Object,
    categories: Array,
    filters: Object,
});

// ─── Filter State ──────────────────────────────────────────────────────────
const search   = ref(props.filters.search   || '');
const category = ref(props.filters.category || 'all');
const event    = ref(props.filters.event    || 'all');
const range    = ref(props.filters.range    || 'all');

const expandedRows = ref(new Set());

// ─── Filter Dispatch ───────────────────────────────────────────────────────
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

// ─── Grouping by Date ─────────────────────────────────────────────────────
const groupedLogs = computed(() => {
    const groups = {};
    (props.logs.data || []).forEach(log => {
        // Laravel's created_at is UTC, append 'Z' so JS parses it as UTC and localizes it
        const dateObj = new Date(log.created_at.endsWith('Z') ? log.created_at : log.created_at + 'Z');
        const date = dateObj.toLocaleDateString('id-ID', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
        if (!groups[date]) groups[date] = [];
        groups[date].push(log);
    });
    return groups;
});

// ─── Toggle Expand ────────────────────────────────────────────────────────
const toggleRow = (id) => {
    if (expandedRows.value.has(id)) expandedRows.value.delete(id);
    else expandedRows.value.add(id);
    // force reactivity
    expandedRows.value = new Set(expandedRows.value);
};

// ─── Helpers ──────────────────────────────────────────────────────────────
const parseProps = (val) => {
    if (!val) return {};
    if (typeof val === 'string') { try { return JSON.parse(val); } catch { return {}; } }
    return val;
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
        // Only include if the values are different (loose inequality handles number vs string "1" != 1)
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
        'master-data':      'bg-indigo-50 text-indigo-600 border-indigo-200',
        'aset':             'bg-teal-50 text-teal-600 border-teal-200',
        'jadwal-inspeksi':  'bg-purple-50 text-purple-600 border-purple-200',
        'aktivitas-sistem': 'bg-cyan-50 text-cyan-600 border-cyan-200',
        'default':          'bg-gray-100 text-gray-500 border-gray-200',
    };
    return map[name] || 'bg-gray-100 text-gray-500 border-gray-200';
};

const eventStyle = (ev) => {
    const map = {
        created: { badge: 'bg-emerald-100 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' },
        updated: { badge: 'bg-amber-100 text-amber-700 border-amber-200',       dot: 'bg-amber-500'   },
        deleted: { badge: 'bg-red-100 text-red-700 border-red-200',             dot: 'bg-red-500'     },
        login:   { badge: 'bg-blue-100 text-blue-700 border-blue-200',          dot: 'bg-blue-500'    },
        logout:  { badge: 'bg-gray-100 text-gray-600 border-gray-200',          dot: 'bg-gray-400'    },
    };
    return map[ev] || { badge: 'bg-gray-100 text-gray-600 border-gray-200', dot: 'bg-gray-400' };
};

const formatTime = (ts) => {
    // Append 'Z' to treat Laravel's UTC timestamp correctly if it's missing it
    const dateObj = new Date(ts.endsWith('Z') ? ts : ts + 'Z');
    return dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const formatSummary = (log) => {
    if (log.event === 'created') return 'Menambahkan data baru';
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

// ─── Filter Config ────────────────────────────────────────────────────────
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
                <ClipboardList class="w-5 h-5 text-indigo-500" />
                <h1 class="text-lg font-bold text-ink dark:text-ink-dark">Audit Trail</h1>
            </div>
        </template>

        <div class="w-full px-2 pb-8">

            <!-- ─── FILTER BAR ──────────────────────────────────────────── -->
            <div class="bg-white dark:bg-surface-dark border border-ghost dark:border-gray-700 rounded-2xl p-4 mb-6 shadow-sm sticky top-2 z-20">
                <div class="flex flex-col gap-3">

                    <!-- Category Pills -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <Filter class="w-3.5 h-3.5 text-gray-400 mr-1 flex-shrink-0" />
                        <button
                            @click="category = 'all'"
                            :class="['px-3 py-1 rounded-full text-[11px] font-bold border transition-all', category === 'all' ? 'bg-gray-800 text-white border-gray-800 dark:bg-gray-200 dark:text-gray-900 dark:border-gray-200' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700']"
                        >
                            Semua
                        </button>
                        <button
                            v-for="cat in categories" :key="cat"
                            @click="category = cat"
                            :class="['px-3 py-1 rounded-full text-[11px] font-bold border transition-all', category === cat ? 'bg-gray-800 text-white border-gray-800 dark:bg-gray-200 dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700']"
                        >
                            {{ categoryLabel(cat) }}
                        </button>
                    </div>

                    <!-- Event + Range + Search -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <div class="flex items-center gap-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-2">
                            <SlidersHorizontal class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                            <select
                                v-model="event"
                                class="bg-transparent text-xs py-2 pr-2 text-gray-700 dark:text-gray-300 focus:outline-none cursor-pointer border-none"
                            >
                                <option v-for="opt in eventOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-2">
                            <Calendar class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                            <select
                                v-model="range"
                                class="bg-transparent text-xs py-2 pr-2 text-gray-700 dark:text-gray-300 focus:outline-none cursor-pointer border-none"
                            >
                                <option v-for="opt in rangeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>

                        <div class="flex-1 flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2">
                            <Search class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari deskripsi atau nama user..."
                                class="bg-transparent flex-1 text-xs text-gray-700 dark:text-gray-300 placeholder-gray-400 focus:outline-none border-none"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── TIMELINE ───────────────────────────────────────────── -->
            <div class="space-y-8">
                <div v-if="!logs.data.length" class="text-center py-16 text-gray-400 dark:text-gray-600">
                    <ClipboardList class="w-12 h-12 mx-auto mb-3 opacity-30" />
                    <p class="text-sm">Belum ada aktivitas yang tercatat.</p>
                </div>

                <div v-for="(groupLogs, date) in groupedLogs" :key="date" class="relative">

                    <!-- Date Header -->
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-2 h-2 rounded-full bg-indigo-400 flex-shrink-0"></div>
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest bg-white dark:bg-page-dark px-2">
                            {{ date }}
                        </span>
                        <div class="flex-1 h-px bg-gray-100 dark:bg-gray-700"></div>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500 font-mono">{{ groupLogs.length }} aktivitas</span>
                    </div>

                    <!-- Log Items -->
                    <div class="ml-3 pl-4 border-l-2 border-gray-100 dark:border-gray-700 space-y-2">
                        <div v-for="log in groupLogs" :key="log.id" class="relative group">

                            <!-- Timeline Dot -->
                            <span
                                :class="['absolute -left-[21px] top-4 h-2.5 w-2.5 rounded-full border-2 border-white dark:border-gray-800 transition-all group-hover:scale-125', eventStyle(log.event).dot]"
                            ></span>

                            <!-- Card -->
                            <div
                                @click="toggleRow(log.id)"
                                class="bg-white dark:bg-surface-dark border border-gray-100 dark:border-gray-700 rounded-xl p-3.5 hover:border-indigo-200 dark:hover:border-indigo-700 hover:shadow-sm transition-all duration-150 cursor-pointer select-none"
                                :class="{ 'border-indigo-200 dark:border-indigo-700 shadow-sm': expandedRows.has(log.id) }"
                            >
                                <!-- Card Header -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <ChevronRight
                                            :class="['w-4 h-4 text-gray-300 transition-transform duration-200 flex-shrink-0', expandedRows.has(log.id) ? 'rotate-90 text-indigo-400' : '']"
                                        />

                                        <!-- Avatar -->
                                        <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                                            <span class="text-[9px] font-bold text-indigo-600 dark:text-indigo-300 uppercase">
                                                {{ (log.causer?.name || 'S').charAt(0) }}
                                            </span>
                                        </div>

                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                            {{ log.causer?.name || 'System' }}
                                        </span>

                                        <!-- Event Badge -->
                                        <span :class="['px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide border', eventStyle(log.event).badge]">
                                            {{ log.event }}
                                        </span>

                                        <!-- Model -->
                                        <span v-if="log.subject_type" class="inline-flex items-center gap-1 bg-gray-50 dark:bg-gray-800 px-2 py-0.5 rounded-md border border-gray-100 dark:border-gray-700 text-[10px] font-bold text-gray-600 dark:text-gray-400">
                                            {{ formatModelName(log.subject_type) }}
                                            <span class="text-indigo-500 font-mono">#{{ log.subject_id }}</span>
                                        </span>

                                        <!-- Summary -->
                                        <span class="text-xs text-gray-500 dark:text-gray-400 break-words flex-1">
                                            {{ formatSummary(log) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0 pl-6 sm:pl-0">
                                        <!-- Category pill -->
                                        <span v-if="log.log_name" :class="['text-[10px] font-semibold px-2 py-0.5 rounded-full border', categoryPillColor(log.log_name)]">
                                            {{ categoryLabel(log.log_name) }}
                                        </span>
                                        <!-- Time -->
                                        <span class="text-xs text-gray-400 dark:text-gray-500 font-mono whitespace-nowrap">
                                            {{ formatTime(log.created_at) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- ─── EXPANDED DETAIL ─────────────────── -->
                                <Transition
                                    enter-active-class="transition-all duration-200 ease-out"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition-all duration-150 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1"
                                >
                                    <div v-if="expandedRows.has(log.id)" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">

                                        <!-- Meta Info -->
                                        <div class="mb-2 flex flex-wrap gap-x-4 gap-y-1 text-[10px] text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded p-1.5 border border-gray-100 dark:border-gray-700">
                                            <span><span class="font-medium text-gray-600 dark:text-gray-300">Model:</span> {{ log.subject_type || '-' }}</span>
                                            <span><span class="font-medium text-gray-600 dark:text-gray-300">ID:</span> <span class="font-mono text-indigo-500">#{{ log.subject_id || '-' }}</span></span>
                                            <span v-if="log.ip_address"><span class="font-medium text-gray-600 dark:text-gray-300">IP:</span> {{ log.ip_address }}</span>
                                        </div>

                                        <!-- Created: show new values -->
                                        <div v-if="log.event === 'created'" class="bg-emerald-50/50 dark:bg-emerald-900/10 rounded p-2 text-[11px] border border-emerald-100/50 dark:border-emerald-800/50">
                                            <div class="flex flex-wrap gap-x-6 gap-y-1">
                                                <div v-for="(val, key) in getNewValues(log)" :key="key" class="flex gap-1.5 items-center">
                                                    <span class="text-emerald-700 dark:text-emerald-500 font-medium capitalize">{{ key.replace(/_/g, ' ') }}:</span>
                                                    <span class="text-gray-700 dark:text-gray-300 font-mono text-[10px]">{{ val ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Deleted: show old values -->
                                        <div v-else-if="log.event === 'deleted'" class="bg-red-50/50 dark:bg-red-900/10 rounded p-2 text-[11px] border border-red-100/50 dark:border-red-800/50">
                                            <div class="flex flex-wrap gap-x-6 gap-y-1">
                                                <div v-for="(val, key) in getOldValues(log)" :key="key" class="flex gap-1.5 items-center">
                                                    <span class="text-red-700 dark:text-red-400 font-medium capitalize">{{ key.replace(/_/g, ' ') }}:</span>
                                                    <span class="text-gray-700 dark:text-gray-300 font-mono text-[10px]">{{ val ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Updated: inline diff -->
                                        <div v-else-if="log.event === 'updated'" class="space-y-1">
                                            <div
                                                v-for="(newVal, key) in getDirtyValues(log).new" :key="key"
                                                class="flex items-center gap-2 text-[11px] bg-slate-50/50 dark:bg-slate-800/20 px-2 py-1 rounded border border-slate-100 dark:border-slate-800/50"
                                            >
                                                <span class="w-1/4 sm:w-1/5 font-medium text-slate-600 dark:text-slate-400 capitalize truncate flex-shrink-0">{{ key.replace(/_/g, ' ') }}</span>
                                                <div class="flex-1 flex gap-2 items-center overflow-hidden">
                                                    <div class="flex-1 truncate bg-red-50 dark:bg-red-900/10 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded text-[10px] font-mono" :title="getDirtyValues(log).old[key] ?? '-'">
                                                        {{ getDirtyValues(log).old[key] ?? '-' }}
                                                    </div>
                                                    <ChevronRight class="w-3 h-3 text-slate-300 dark:text-slate-600 flex-shrink-0" />
                                                    <div class="flex-1 truncate bg-emerald-50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400 px-1.5 py-0.5 rounded text-[10px] font-mono font-medium" :title="newVal ?? '-'">
                                                        {{ newVal ?? '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <p v-if="!Object.keys(getDirtyValues(log).new).length" class="text-gray-400 italic text-[10px]">
                                                Tidak ada detail perubahan (atau tidak terdeteksi oleh sistem).
                                            </p>
                                        </div>

                                        <!-- Login/Logout or other -->
                                        <div v-else class="text-xs text-gray-500 dark:text-gray-400 italic px-1">
                                            {{ log.description || 'Tidak ada detail tambahan.' }}
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── PAGINATION ─────────────────────────────────────────── -->
            <div v-if="logs.links && logs.links.length > 3" class="mt-8 flex justify-center">
                <div class="flex gap-1 bg-white dark:bg-surface-dark p-2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <component
                        :is="link.url ? Link : 'span'"
                        v-for="(link, k) in logs.links" :key="k"
                        :href="link.url ?? '#'"
                        v-html="link.label"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                        :class="{
                            'bg-indigo-600 text-white shadow-sm': link.active,
                            'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': !link.active && link.url,
                            'opacity-30 cursor-default text-gray-400': !link.url
                        }"
                    />
                </div>
            </div>
        </div>
    </MainLayout>
</template>
