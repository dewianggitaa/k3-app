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

const showInfoModal = ref(false);
const activeInfoId = ref(null);
const selectedAssetInfo = ref(null);

const toggleInfo = (id) => {
    activeInfoId.value = activeInfoId.value === id ? null : id;
};

const openAssetInfo = (asset) => {
    // Kita tebak tipenya dari kode atau props yang ada
    let type = 'apar';
    if (asset.code?.includes('HYD')) type = 'hydrant'; // Sesuaikan logic prefix kode kamu
    if (asset.code?.includes('P3K')) type = 'p3k';

    selectedAssetInfo.value = {
        ...asset,
        assetType: type
    };
    showInfoModal.value = true;
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
                        :fill="activeRoom?.id === room.id ? hexToRgba(room.color, 0.3) : 'rgba(0,0,0,0.03)'"
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
                            class="absolute bottom-full mb-3 left-1/2 -translate-x-1/2 z-[999] bg-white shadow-xl border border-gray-200 rounded-lg p-3 w-44 pointer-events-auto">
                            
                            <div class="flex flex-col gap-0.5">
                                <p class="text-[11px] font-bold text-gray-900 truncate">{{ asset.code }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="w-2 h-2 rounded-full" :class="asset.status === 'safe' ? 'bg-green-500' : 'bg-red-500'"></span>
                                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-wider">{{ asset.status || 'No Status' }}</p>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-1 border-t pt-1">{{ asset.room?.name }}</p>
                            </div>

                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-[6px] border-transparent border-t-white text-white drop-shadow-sm"></div>
                        </div>

                        <img 
                            :src="iconPath" 
                            @click.stop="toggleInfo(asset.id)"
                            class="w-5 h-5 relative z-10 drop-shadow-md cursor-pointer transition-all duration-200" 
                            :class="{'scale-[1.7] brightness-125 z-20': asset.id === selectedAsset?.id || activeInfoId === asset.id}" 
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>



    
</template>