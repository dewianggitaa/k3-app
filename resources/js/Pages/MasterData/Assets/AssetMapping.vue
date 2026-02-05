<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    ChevronLeft, Search, MapPin, Save, RotateCcw, 
    CheckCircle2, Box 
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import MainLayout from '@/Layouts/MainLayout.vue';
import MappingNavigation from '@/Components/MappingNavigation.vue';

const props = defineProps({
    floor: Object,
    rooms: Array,
    p3ks: Array, 
});

const selectedAsset = ref(null);
const searchQuery = ref('');

const form = useForm({
    id: '',
    location_data: null,
});

// --- LOGIC: POINT IN POLYGON ---
const isPointInPolygon = (point, polygon) => {
    let x = point.x, y = point.y;
    let inside = false;
    for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
        let xi = polygon[i].x, yi = polygon[i].y;
        let xj = polygon[j].x, yj = polygon[j].y;
        let intersect = ((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
        if (intersect) inside = !inside;
    }
    return inside;
};

// --- COMPUTED ---
const filteredAssets = computed(() => {
    return props.p3ks.filter(a => 
        a.code.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const activeRoom = computed(() => {
    if (!selectedAsset.value) return null;
    return props.rooms.find(r => r.id === selectedAsset.value.room_id);
});

// --- METHODS ---
const selectAsset = (asset) => {
    selectedAsset.value = asset;
    form.id = asset.id;
    form.location_data = asset.location_data ? { ...asset.location_data } : null;
};

const handleMapClick = (event) => {
    if (!selectedAsset.value) {
        toast.fire({ icon: 'info', title: 'Pilih aset terlebih dahulu' });
        return;
    }

    const room = activeRoom.value;
    if (!room || !room.coordinates) {
        Swal.fire('Error', 'Area ruangan belum dipetakan!', 'error');
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const x = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
    const y = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));

    if (isPointInPolygon({ x, y }, room.coordinates)) {
        form.location_data = { x, y };
    } else {
        toast.fire({ icon: 'warning', title: `Klik di dalam area ${room.name}` });
    }
};

const submitMapping = () => {
    form.post(route('assets.update-location'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedAsset.value = null;
            form.reset();
            toast.fire({ icon: 'success', title: 'Posisi berhasil disimpan' });
        }
    });
};

const resetLocation = () => {
    Swal.fire({
        title: 'Reset Posisi?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Reset'
    }).then((result) => {
        if (result.isConfirmed) {
            form.location_data = null;
            submitMapping();
        }
    });
};

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

const hexToRgba = (hex, opacity) => {
    if (!hex) return `rgba(79, 70, 229, ${opacity})`;
    let c = hex.substring(1).split('');
    if(c.length == 3) c = [c[0], c[0], c[1], c[1], c[2], c[2]];
    c = '0x'+c.join('');
    return `rgba(${[(c>>16)&255, (c>>8)&255, c&255].join(',')},${opacity})`;
};
</script>

<template>
    <Head :title="`Mapping Asset - ${floor.name}`" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center gap-4">
                <Link :href="route('floors.index')" class="p-2 hover:bg-gray-100 rounded-full">
                    <ChevronLeft class="w-5 h-5 text-black" />
                </Link>
                <div>
                    <h2 class="font-bold text-lg text-gray-800 leading-tight">Pemetaan Asset</h2>
                    <p class="text-xs text-gray-500">Lantai: {{ floor.name }}</p>
                </div>
            </div>
        </template>

        <template #header-nav>
            <div class="w-full bg-red-300 px-6"> 
                <MappingNavigation :floorId="floor.id" />
            </div>
        </template>


        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-100px)]">
            <div class="w-full lg:w-56 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <div class="relative">
                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Cari Kode Aset..." 
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
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500">Ruang: {{ asset.room?.name || 'N/A' }}</p>
                        </div>
                        <div v-if="asset.location_data" class="bg-green-100 text-green-600 p-1 rounded-full">
                            <CheckCircle2 class="w-3.5 h-3.5" />
                        </div>
                    </button>
                </div>

                <div v-if="selectedAsset" class="p-4 bg-slate-50 border-t space-y-3">
                    <div class="flex gap-2">
                        <button @click="submitMapping" :disabled="!form.location_data || form.processing" 
                                class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white py-2.5 rounded-lg text-sm font-bold flex justify-center items-center gap-2 transition-all shadow-sm">
                            <Save class="w-4 h-4" /> Simpan
                        </button>
                        <button v-if="selectedAsset.location_data" @click="resetLocation" :disabled="form.processing"
                                class="p-2.5 bg-white border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                            <RotateCcw class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1 bg-gray-200 rounded-xl border-2 border-dashed border-gray-300 relative overflow-hidden flex items-center justify-center p-8">
                <div class="relative inline-block shadow-2xl rounded-lg overflow-hidden bg-white">
                    
                    <img :src="'/storage/floors/' + floor.map_image_path" @click="handleMapClick"
                         class="max-w-full max-h-[70vh] block cursor-crosshair select-none" />

                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" 
                         class="absolute top-0 left-0 w-full h-full pointer-events-none">
                        
                        <polygon v-for="room in rooms" :key="room.id"
                                 :points="room.coordinates.map(p => `${p.x},${p.y}`).join(' ')"
                                 :fill="activeRoom?.id === room.id ? hexToRgba(room.color, 0.2) : 'rgba(0,0,0,0.02)'"
                                 :stroke="activeRoom?.id === room.id ? room.color : 'rgba(0,0,0,0.1)'"
                                 stroke-width="0.2" />
                    </svg>

                    <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
    
                        <div v-for="asset in p3ks.filter(a => a.location_data)" 
                            :key="asset.id"
                            class="absolute -translate-x-1/2 -translate-y-1/2 flex items-center justify-center"
                            :style="{ 
                                left: (asset.id === selectedAsset?.id && form.location_data ? form.location_data.x : asset.location_data.x) + '%', 
                                top: (asset.id === selectedAsset?.id && form.location_data ? form.location_data.y : asset.location_data.y) + '%' 
                            }">
                            
                            <div :class="[
                                'absolute w-8 h-8 transition-all duration-500 opacity-60',
                                asset.status === 'safe' ? 'bg-green-400' : 
                                asset.status === 'warning' ? 'bg-yellow-400' : 
                                asset.status === 'critical' ? 'bg-red-500' : 
                                'bg-gray-400'
                            ]"></div>

                            <img src="/icon-p3k.png" 
                                :class="[
                                    'w-5 h-5 object-contain relative z-10 transition-all',
                                    asset.id === selectedAsset?.id ? 'scale-110 drop-shadow-xl' : 'opacity-90 drop-shadow-sm'
                                ]" />
                            
                            <span class="absolute top-full mt-1 bg-black/60 text-white text-[8px] px-1.2 rounded whitespace-nowrap">
                                {{ asset.code }}
                            </span>
                        </div>
                    </div>

                    <div v-if="selectedAsset" class="absolute top-4 right-4 bg-black/70 text-white px-3 py-1.5 rounded-full text-[11px] backdrop-blur-md flex items-center gap-2">
                        <MapPin class="w-3 h-3 text-indigo-400" />
                        Memetakan: <span class="font-bold text-indigo-300">{{ selectedAsset.code }}</span>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
.cursor-crosshair { cursor: crosshair; }
</style>