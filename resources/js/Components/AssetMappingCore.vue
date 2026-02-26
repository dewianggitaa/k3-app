<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { ZoomIn, ZoomOut, Maximize, X, MapPin, Droplets, Flame, ClipboardList } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

const mapStyle = computed(() => {
    const blackCrosshair = `url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23000000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><line x1='12' y1='1' x2='12' y2='23'></line><line x1='1' y1='12' x2='23' y2='12'></line></svg>") 12 12, crosshair`;

    return {
        transform: `translate(${offset.value.x}px, ${offset.value.y}px) scale(${zoomLevel.value})`,
        cursor: isDragging.value 
            ? 'grabbing' 
            : (props.selectedAsset ? blackCrosshair : 'grab')
    };
});

const props = defineProps({
    floor: Object,
    rooms: Array,
    assets: Array,
    selectedAsset: Object,
    modelValue: Object,
    iconPath: String,
});

const emit = defineEmits(['update:modelValue', 'onAreaError']);

const zoomLevel = ref(1);
const offset = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const startPan = ref({ x: 0, y: 0 });

const handleMouseDown = (event) => {
    if (event.button === 1 || (event.button === 0 && !props.selectedAsset)) {
        isDragging.value = true;
        startPan.value = { 
            x: event.clientX - offset.value.x, 
            y: event.clientY - offset.value.y 
        };
        event.preventDefault();
    }
};

const handleMouseMove = (event) => {
    if (!isDragging.value) return;
    offset.value = {
        x: event.clientX - startPan.value.x,
        y: event.clientY - startPan.value.y
    };
};

const stopDragging = () => {
    isDragging.value = false;
};

const handleWheel = (event) => {
    event.preventDefault();
    const zoomSpeed = 0.1;
    if (event.deltaY < 0) {
        if (zoomLevel.value < 5) zoomLevel.value += zoomSpeed;
    } else {
        if (zoomLevel.value > 0.4) zoomLevel.value -= zoomSpeed;
    }
};

const resetZoom = () => {
    zoomLevel.value = 1;
    offset.value = { x: 0, y: 0 };
};

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

const activeRoom = computed(() => {
    if (!props.selectedAsset) return null;
    return props.rooms.find(r => r.id === props.selectedAsset.room_id);
});

const handleMapClick = (event) => {
    if (isDragging.value || !props.selectedAsset) return;

    const room = activeRoom.value;
    if (!room || !room.coordinates) return;

    const rect = event.currentTarget.getBoundingClientRect();
    const x = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
    const y = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));

    if (isPointInPolygon({ x, y }, room.coordinates)) {
        emit('update:modelValue', { x, y });
    } else {
        emit('onAreaError', room.name);
    }
};

const hexToRgba = (hex, opacity) => {
    if (!hex) return `rgba(79, 70, 229, ${opacity})`;
    let r = parseInt(hex.slice(1, 3), 16), g = parseInt(hex.slice(3, 5), 16), b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

onMounted(() => window.addEventListener('mouseup', stopDragging));
onUnmounted(() => window.removeEventListener('mouseup', stopDragging));

const activeInfoId = ref(null);

const toggleInfo = (id) => {
    activeInfoId.value = activeInfoId.value === id ? null : id;
};

// Deteksi otomatis tipe aset dari kodenya
const getAssetType = (asset) => {
    if (!asset || !asset.code) return 'apar';
    const code = asset.code.toLowerCase();
    if (code.includes('hyd')) return 'hydrant';
    if (code.includes('p3k')) return 'p3k';
    return 'apar';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>

<template>
    <div class="relative w-full h-full overflow-hidden bg-slate-200 rounded-xl flex items-center justify-center border border-slate-300 shadow-inner"
        @mousedown="handleMouseDown"
        @mousemove="handleMouseMove"
        @wheel="handleWheel">
        
        <div class="absolute top-3 right-3 z-50 flex flex-col gap-1.5">
            <button @click="zoomLevel = Math.min(zoomLevel + 0.5, 5)" class="p-2 bg-white/90 backdrop-blur rounded-lg shadow border hover:bg-white text-gray-600"><ZoomIn class="w-4 h-4"/></button>
            <button @click="resetZoom" class="p-2 bg-white/90 backdrop-blur rounded-lg shadow border hover:bg-white text-gray-600"><Maximize class="w-4 h-4"/></button>
            <button @click="zoomLevel = Math.max(zoomLevel - 0.5, 0.4)" class="p-2 bg-white/90 backdrop-blur rounded-lg shadow border hover:bg-white text-gray-600"><ZoomOut class="w-4 h-4"/></button>
        </div>

        <div 
            class="relative inline-block bg-white shadow-sm transition-transform duration-150 ease-out"
           :style="mapStyle"
        >
            <div class="relative flex" @click="handleMapClick">
                <img :src="'/storage/floors/' + floor.map_image_path" 
                     class="max-w-none h-[80vh] w-auto block select-none pointer-events-none" 
                     draggable="false" />

                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute top-0 left-0 w-full h-full pointer-events-none">
                    <polygon v-for="room in rooms" :key="room.id"
                        :points="room.coordinates?.map(p => `${p.x},${p.y}`).join(' ')"
                        :fill="activeRoom?.id === room.id ? hexToRgba(room.color, 0.1) : 'rgba(0,0,0,0.01)'"
                        :stroke="activeRoom?.id === room.id ? room.color : 'rgba(0,0,0,0.1)'"
                        stroke-width="0.2" />
                </svg>

                <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
                    <div v-for="asset in assets" :key="asset.id"
                        v-show="asset.location_data || (asset.id === selectedAsset?.id && modelValue)"
                        class="absolute -translate-x-1/2 -translate-y-1/2 pointer-events-auto"
                        :style="{ 
                            left: (asset.id === selectedAsset?.id && modelValue ? modelValue.x : asset.location_data?.x) + '%', 
                            top: (asset.id === selectedAsset?.id && modelValue ? modelValue.y : asset.location_data?.y) + '%' 
                        }">
                        
                        <div v-if="activeInfoId === asset.id" 
                            @click="activeInfoId = null"
                            class="fixed inset-0 z-[998] bg-transparent cursor-default">
                        </div>

                        <div v-if="activeInfoId === asset.id" 
                            class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 z-[999] bg-white shadow-2xl border border-gray-200 rounded-xl w-60 pointer-events-auto overflow-hidden">
                            
                            <div class="p-2 text-white flex justify-between items-center bg-primary">
                                <div>
                                    <p class="text-[11px] font-bold truncate uppercase">{{ asset.is_double ? 'KOTAK' : getAssetType(asset) }} {{ asset.code }}</p>
                                    <p class="text-[8px] opacity-90">{{ asset.room?.name || 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="p-3 space-y-3">
                                <div v-for="item in (asset.is_double ? asset.original_items : [asset])" :key="item.id" 
                                     class="border-b border-gray-100 last:border-0 pb-3 last:pb-0">
                                    
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[10px] font-black text-gray-800 uppercase">
                                            {{ asset.is_double ? 'TABUNG ' + item.code.split('-').pop() : 'INFO ASET' }}
                                        </span>
                                        <span :class="[
                                            'text-[8px] px-2 py-0.5 rounded-full font-black border uppercase tracking-wider',
                                            item.status?.toLowerCase() === 'safe' ? 'bg-green-50 text-green-700 border-green-200' : (item.status?.toLowerCase() === 'kritis' || item.status?.toLowerCase() === 'critical' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-100 text-gray-500 border-gray-200')
                                        ]">
                                            {{ item.status || 'BELUM CEK' }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-y-1.5 text-[9px]">
                                        
                                        <span class="text-gray-400">Tipe Aset:</span>
                                        <span class="text-gray-900 font-bold text-right truncate">
                                            {{ item.type?.name || item.apar_type?.name || item.p3k_type?.name || item.hydrant_type?.name || '-' }}
                                        </span>

                                        <template v-if="getAssetType(asset) === 'apar'">
                                            <span class="text-gray-400">Berat (Weight):</span>
                                            <span class="text-gray-900 font-bold text-right">{{ item.weight ? item.weight + ' Kg' : '-' }}</span>
                                            
                                            <span class="text-gray-400">Terakhir Diisi:</span>
                                            <span class="text-gray-900 font-bold text-right">{{ formatDate(item.last_refilled_at) }}</span>
                                            
                                            <span class="text-gray-400">Kadaluarsa:</span>
                                            <span class="text-gray-900 font-bold text-right">{{ formatDate(item.expired_at) }}</span>
                                        </template>
                                    </div>

                                    <div v-if="item.status?.toLowerCase() === 'critical' || item.status?.toLowerCase() === 'kritis'" 
                                         class="mt-2 text-[9px] text-red-700 bg-red-50 p-2 rounded-lg border border-red-100 leading-snug">
                                        <strong class="block mb-0.5 uppercase text-[8px] tracking-wider">Detail Kerusakan:</strong>
                                        {{ item.last_finding || item.critical_details || 'Hubungi petugas untuk detail lebih lanjut.' }}
                                    </div>
                                </div>

                                <div class="pt-1">
                                    <Link :href="route('reports.index', { tab: getAssetType(asset), asset_code: asset.code })" 
                                          class="block w-full text-center py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[9px] font-bold transition">
                                        LIHAT RIWAYAT DETAIL
                                    </Link>
                                </div>
                            </div>

                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-[6px] border-transparent border-t-white"></div>
                        </div>

                        <div class="relative flex items-center justify-center">
                            <img 
                                :src="asset.is_double ? '/icon-apar2.png' : iconPath" 
                                @click.stop="toggleInfo(asset.id)"
                                class="w-6 h-6 relative z-10 drop-shadow-md cursor-pointer transition-all duration-200" 
                                :class="{'scale-[1.7] brightness-125 z-20': asset.id === selectedAsset?.id || activeInfoId === asset.id}" 
                            />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>