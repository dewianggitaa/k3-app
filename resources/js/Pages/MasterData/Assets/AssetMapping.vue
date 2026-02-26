<script setup>
import { onMounted, ref, nextTick, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    ChevronLeft, Search, MapPin, Save, RotateCcw, 
    CheckCircle2, Box, ShieldAlert, Droplets, Thermometer
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import MainLayout from '@/Layouts/MainLayout.vue';
import MappingNavigation from '@/Components/MappingNavigation.vue';
import AssetMappingCore from '@/Components/AssetMappingCore.vue';

const props = defineProps({
    floor: Object,
    rooms: Array,
    p3ks: Array,
    apars: Array,
    hydrants: Array,
    target_id: [String, Number],
    target_type: String
});
const mapCoreRef = ref(null);
const activeType = ref('p3k'); 

const assetConfig = computed(() => ({
    p3k: { label: 'P3K', icon: '/icon-p3k.png', data: props.p3ks || [], color: 'text-green-600' },
    apar: { label: 'APAR', icon: '/icon-apar.png', data: props.apars || [], color: 'text-red-600' },
    hydrant: { label: 'Hydrant', icon: '/icon-hydrant.png', data: props.hydrants || [], color: 'text-blue-600' },
}));

const selectedAsset = ref(null);
const searchQuery = ref('');

onMounted(() => {
    if (props.target_type && props.target_id) {
        
        const requestedType = props.target_type.toLowerCase();

        if (assetConfig.value[requestedType]) {
            
            activeType.value = requestedType; 
            
            const assetsList = assetConfig.value[requestedType].data || [];
            const target = assetsList.find(a => a.id == props.target_id);

            if (target) {
                selectAsset(target);
            }
        }
    }
});

const form = useForm({
    id: '',
    type: '', 
    location_data: null,
});

const groupedAssets = computed(() => {
    const currentData = assetConfig.value[activeType.value]?.data || [];
    
    // Kita hanya grouping kalau tipenya APAR
    if (activeType.value !== 'apar') return currentData;

    const groups = {};
    currentData.forEach(asset => {
        // Cek apakah kodenya pakai akhiran -A atau -B
        const isDouble = /-[AB]$/i.test(asset.code);
        const baseCode = isDouble ? asset.code.replace(/-[AB]$/i, '') : asset.code;

        if (!groups[baseCode]) {
            groups[baseCode] = {
                ...asset,        // Ambil data dasar dari asset pertama yang ketemu
                code: baseCode,  // Pakai kode tanpa -A/-B
                original_items: [],
                is_double: false
            };
        }
        
        groups[baseCode].original_items.push(asset);
        
        if (groups[baseCode].original_items.length > 1) {
            groups[baseCode].is_double = true;
        }
    });

    return Object.values(groups);
});

const filteredAssets = computed(() => {
    // Gunakan groupedAssets.value, jangan assetConfig lagi
    return groupedAssets.value.filter(a => 
        a.code?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const changeType = (type) => {
    activeType.value = type;
    selectedAsset.value = null;
    form.reset();
};

const selectAsset = (asset) => {
    if (selectedAsset.value?.id === asset.id) {
        selectedAsset.value = null;
        form.reset();
    } else {
        selectedAsset.value = asset;
        form.id = asset.id;
        form.type = activeType.value;
        form.location_data = asset.location_data ? { ...asset.location_data } : null;
    }
};

const submitMapping = () => {
    form.post(route('assets.update-location', { type: activeType.value }), {
        preserveScroll: true,
        onSuccess: () => {
            selectedAsset.value = null;
            form.reset();
            toast.fire({ icon: 'success', title: 'Posisi berhasil disimpan' });
        },
        onError: (errors) => {
            // Tambahan: tampilkan pesan error jika validasi gagal
            console.error(errors);
            toast.fire({ icon: 'error', title: 'Gagal menyimpan posisi' });
        }
    });
};

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
});
</script>

<template>
    <Head :title="`Mapping Asset - ${floor.name}`" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center justify-between w-full">
                
                <div class="flex items-center gap-4">
                    <Link :href="route('floors.index')" class="p-2 hover:bg-gray-100 rounded-full">
                        <ChevronLeft class="w-5 h-5 text-black" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-lg text-gray-800 leading-tight">Pemetaan Asset</h2>
                        <p class="text-xs text-gray-500">Lantai: {{ floor.name }}</p>
                    </div>
                </div>

                <div class="flex gap-1 p-1 bg-gray-100 rounded-xl border border-gray-200 shadow-sm">
                    <button v-for="(cfg, key) in assetConfig" :key="key"
                        @click="changeType(key)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center justify-center gap-2 transition-all whitespace-nowrap',
                            activeType === key ? 'bg-slate-800 text-white shadow-md' : 'hover:bg-white/50 text-gray-500'
                        ]"
                    >
                        <span>{{ cfg.label }}</span>
                        <span v-if="cfg.data?.length > 0" 
                            :class="[
                                'text-[10px] px-1.5 py-0.5 rounded-md font-medium',
                                activeType === key ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600'
                            ]">
                            {{ cfg.data.length }}
                        </span>
                    </button>
                </div>
            </div>
        </template>

        <template #header-nav>
            <div class="w-full px-6"> 
                <MappingNavigation :floorId="floor.id" />
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-220px)]">
            <div class="w-full lg:w-64 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <div class="relative">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" type="text" :placeholder="`Cari ${activeType}...`" 
                               class="w-full pl-9 py-2 text-sm border-gray-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                    <button v-for="asset in filteredAssets" :key="asset.id" @click="selectAsset(asset)"
                        :class="[
                            'w-full text-left p-3 rounded-lg border transition-all flex justify-between items-start',
                            selectedAsset?.id === asset.id ? 'bg-indigo-50 border-indigo-500 ring-2 ring-indigo-500/10' : 'bg-white border-transparent hover:bg-gray-50'
                        ]"
                    >
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <Box class="w-3.5 h-3.5 text-gray-400" />
                                <span :class="['font-bold text-sm', selectedAsset?.id === asset.id ? 'text-indigo-700' : 'text-gray-700']">
                                    {{ asset.code }}
                                    <span v-if="asset.is_double" class="ml-1.5 text-[9px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded border border-blue-200 uppercase">
                                        2 Tabung
                                    </span>
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500">Ruang: {{ asset.room?.name || 'N/A' }}</p>
                        </div>
                        <div v-if="asset.location_data" class="bg-green-100 text-green-600 p-1 rounded-full">
                            <CheckCircle2 class="w-3.5 h-3.5" />
                        </div>
                    </button>
                </div>

                <div v-if="selectedAsset" class="p-4 bg-slate-50 border-t">
                    <button @click="submitMapping" :disabled="!form.location_data || form.processing" 
                            class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white py-2.5 rounded-lg text-sm font-bold flex justify-center items-center gap-2 shadow-sm transition-all">
                        <Save class="w-4 h-4" /> Simpan Posisi
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-gray-200 rounded-xl relative overflow-hidden flex items-center justify-center border-none">
                <AssetMappingCore 
                    ref="mapCoreRef"
                    v-model="form.location_data"
                    :floor="floor"
                    :rooms="rooms"
                    :assets="groupedAssets"
                    :selectedAsset="selectedAsset"
                    :iconPath="assetConfig[activeType].icon"
                    @onAreaError="(name) => toast.fire({ icon: 'warning', title: `Klik di area ${name}` })"
                />
            </div>
        </div>
    </MainLayout>
</template>