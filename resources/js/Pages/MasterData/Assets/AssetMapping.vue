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
import MappingAssetList from '@/Components/MappingAssetList.vue';

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
const isBottomSheetOpen = ref(false); 

const assetConfig = computed(() => ({
    p3k: { label: 'P3K', icon: '/icon-p3k.png', data: props.p3ks || [], color: 'text-success' },
    apar: { label: 'APAR', icon: '/icon-apar.png', data: props.apars || [], color: 'text-danger' },
    hydrant: { label: 'Hydrant', icon: '/icon-hydrant.png', data: props.hydrants || [], color: 'text-primary' },
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

    if (activeType.value !== 'apar') return currentData;

    const groups = {};
    currentData.forEach(asset => {

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
    isBottomSheetOpen.value = false;
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
            <div class="flex items-center gap-4">
                <Link :href="route('floors.index')" class="p-2 hover:bg-ghost rounded-full transition-colors">
                    <ChevronLeft class="w-5 h-5 text-ink" />
                </Link>
                <div>
                    <h2 class="font-bold text-lg text-ink leading-tight">Pemetaan Asset</h2>
                    <p class="text-xs text-ink-light">Lantai: {{ floor.name }}</p>
                </div>
            </div>
        </template>

        <template #header-nav>
            <div class="w-full px-4">
                <MappingNavigation :floorId="floor.id" />
            </div>
            <div class="w-full px-4 pb-2 flex gap-1.5">
                <button v-for="(cfg, key) in assetConfig" :key="key"
                    @click="changeType(key)"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all',
                        activeType === key 
                            ? 'bg-slate-800 text-white shadow-md' 
                            : 'bg-ghost hover:bg-ghost-hover text-ink-light border border-ghost-hover'
                    ]"
                >
                    <img :src="cfg.icon" class="w-4 h-4" />
                    <span>{{ cfg.label }}</span>
                    <span v-if="cfg.data?.length > 0" 
                        :class="[
                            'text-[10px] px-1.5 py-0.5 rounded-full font-bold min-w-[20px] text-center',
                            activeType === key ? 'bg-white/20 text-white' : 'bg-gray-200 text-ink-light'
                        ]">
                        {{ cfg.data.length }}
                    </span>
                </button>
            </div>
        </template>

        <div v-if="!floor.map_image_path" class="p-4 m-4 bg-warning/10 border border-warning/30 rounded-md flex items-center gap-4">
            <div class="p-3 bg-warning/20 rounded-full">
                <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-yellow-800">Denah Belum Diupload</h3>
                <p class="text-sm text-warning">Lantai ini belum memiliki gambar denah. Silakan tambahkan file gambar denah di halaman Master Data Lantai terlebih dahulu agar bisa melakukan pemetaan aset.</p>
            </div>
        </div>

        <div v-else class="flex flex-col lg:flex-row h-full lg:h-[calc(100vh-260px)] gap-4 relative">
            <div class="hidden lg:flex w-full lg:w-64 bg-surface rounded-md shadow-sm border border-ghost-hover flex-col overflow-hidden">
                <div class="p-4 border-b bg-ghost">
                    <div class="relative">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-light" />
                        <input v-model="searchQuery" type="text" :placeholder="`Cari ${activeType}...`" 
                               class="w-full pl-9 py-2 text-sm border-ghost-hover rounded-md focus:ring-primary focus:border-primary" />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-2">
                    <MappingAssetList :assets="filteredAssets" :selectedAsset="selectedAsset" @select="selectAsset" />
                </div>

                <div v-if="selectedAsset" class="p-4 bg-ghost border-t shrink-0">
                    <button @click="submitMapping" :disabled="!form.location_data || form.processing" 
                            class="w-full bg-primary hover:bg-primary-hover disabled:bg-gray-300 text-white py-2.5 rounded-md text-sm font-bold flex justify-center items-center gap-2 shadow-sm transition-all">
                        <Save class="w-4 h-4" /> Simpan Posisi
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-gray-200 rounded-md relative overflow-hidden flex items-center justify-center border-none w-full min-h-[60vh] lg:min-h-0">
                <AssetMappingCore 
                    ref="mapCoreRef"
                    v-model="form.location_data"
                    :floor="floor"
                    :rooms="rooms"
                    :assets="groupedAssets"
                    :selectedAsset="selectedAsset"
                    :iconPath="assetConfig[activeType].icon"
                    :isBottomSheetOpen="isBottomSheetOpen"
                    @onAreaError="(name) => toast.fire({ icon: 'warning', title: `Klik di area ${name}` })"
                />

                <button 
                    @click="isBottomSheetOpen = true"
                    class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 bg-primary text-white px-6 py-2.5 rounded-full shadow-lg z-20 font-bold text-sm flex items-center gap-2"
                >
                    <Box class="w-4 h-4" /> Daftar {{ assetConfig[activeType].label }}
                </button>
            </div>

            <!-- Bottom Sheet -->
            <div v-if="isBottomSheetOpen" class="lg:hidden fixed inset-0 z-[100] flex flex-col justify-end">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="isBottomSheetOpen = false"></div>
                <div class="relative bg-surface w-full rounded-t-2xl shadow-xl flex flex-col max-h-[80vh]">
                    <div class="p-4 border-b flex justify-between items-center bg-ghost rounded-t-2xl shrink-0">
                        <div class="font-bold text-ink flex items-center gap-2">
                            <Box class="w-4 h-4 text-primary" /> Daftar {{ assetConfig[activeType].label }}
                        </div>
                        <button @click="isBottomSheetOpen = false" class="p-1 rounded-full text-ink-light hover:bg-surface border border-transparent">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    
                    <div class="p-3 border-b bg-ghost shrink-0">
                        <div class="relative">
                            <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-light" />
                            <input v-model="searchQuery" type="text" :placeholder="`Cari ${activeType}...`" 
                                   class="w-full pl-9 py-2 text-sm border-ghost-hover rounded-md focus:ring-primary focus:border-primary" />
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 shrink-1">
                        <MappingAssetList :assets="filteredAssets" :selectedAsset="selectedAsset" @select="selectAsset" />
                    </div>

                    <div v-if="selectedAsset" class="p-4 bg-ghost border-t shrink-0">
                        <button @click="submitMapping" :disabled="!form.location_data || form.processing" 
                                class="w-full bg-primary hover:bg-primary-hover disabled:bg-gray-300 text-white py-2.5 rounded-md text-sm font-bold flex justify-center items-center gap-2 shadow-sm transition-all">
                            <Save class="w-4 h-4" /> Simpan Posisi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>