<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { ZoomIn, ZoomOut, Maximize, X, MapPin, Droplets, Flame, ClipboardList } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
});

const zoomLevel = ref(1);
const offset = ref({ x: 0, y: 0 });
const startPan = ref({ x: 0, y: 0 });
const hasDragged = ref(false);
const startPinchDist = ref(0);
const startZoomLevel = ref(1);
const isDragging = ref(false);
const isLoadingDetail = ref(false);
const isSlideoverOpen = ref(false);
const detailAsset = ref(null);

const openDetail = async(item) => {
    detailAsset.value = { ...item, checklist_results: [] }; 
    isSlideoverOpen.value = true; 
    activeInfoId.value = null; 
    isLoadingDetail.value = true;

    try {
        const type = getAssetType(item);
        const response = await axios.get(`/assets/${type}/${item.id}/latest-inspection`);
        
        detailAsset.value.checklist_results = response.data.results;
        console.log("Data inspeksi berhasil diambil:", response.data);

    } catch (error) {
        console.error("Gagal mengambil data inspeksi:", error);
        
        const errorMessage = error.response?.data?.message || 'Gagal memuat detail inspeksi. Coba lagi ya.';
        toast.fire({ 
            icon: 'error', 
            title: errorMessage 
        });

    } finally {
        isLoadingDetail.value = false; 
    }   
};

const closeDetail = () => {
    isSlideoverOpen.value = false;
    setTimeout(() => { 
        detailAsset.value = null; 
    }, 300);
};

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
    const newX = event.clientX - startPan.value.x;
    const newY = event.clientY - startPan.value.y;
    if (Math.abs(newX - offset.value.x) > 5 || Math.abs(newY - offset.value.y) > 5) {
        hasDragged.value = true;
    }
    offset.value = { x: newX, y: newY };
};

const handleTouchStart = (event) => {
    if (event.touches.length === 1) {
        isDragging.value = true;
        startPan.value = { 
            x: event.touches[0].clientX - offset.value.x, 
            y: event.touches[0].clientY - offset.value.y 
        };
    } else if (event.touches.length === 2) {
        isDragging.value = false;
        const dist = Math.hypot(
            event.touches[0].clientX - event.touches[1].clientX, 
            event.touches[0].clientY - event.touches[1].clientY
        );
        startPinchDist.value = dist;
        startZoomLevel.value = zoomLevel.value;
    }
};

const handleTouchMove = (event) => {
    if (event.touches.length === 1 && isDragging.value) {
        const newX = event.touches[0].clientX - startPan.value.x;
        const newY = event.touches[0].clientY - startPan.value.y;
        if (Math.abs(newX - offset.value.x) > 5 || Math.abs(newY - offset.value.y) > 5) {
            hasDragged.value = true;
        }
        offset.value = { x: newX, y: newY };
    } else if (event.touches.length === 2) {
        hasDragged.value = true;
        event.preventDefault();
        const dist = Math.hypot(
            event.touches[0].clientX - event.touches[1].clientX, 
            event.touches[0].clientY - event.touches[1].clientY
        );
        if (startPinchDist.value > 0) {
            const scaleChange = dist / startPinchDist.value;
            let newZoom = startZoomLevel.value * scaleChange;
            zoomLevel.value = Math.min(Math.max(newZoom, 0.4), 5);
        }
    }
};

const handleTouchEnd = () => {
    isDragging.value = false;
    startPinchDist.value = 0;
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
    if (hasDragged.value) {
        hasDragged.value = false;
        return;
    }
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
const infoPopupStyle = ref({});
const infoArrowDir = ref('bottom'); // 'top' | 'bottom' | 'left' | 'right'
const mapContainerRef = ref(null);
const POPUP_W = 240; // w-60 = 240px
const POPUP_H = 280; // estimated max height
const OFFSET = 14;  // gap from icon

const toggleInfo = (id, event) => {
    if (activeInfoId.value === id) {
        activeInfoId.value = null;
        infoPopupStyle.value = {};
        return;
    }
    
    activeInfoId.value = id;

    const iconEl = event?.currentTarget;
    if (!iconEl || !mapContainerRef.value) return;
    
    const iconRect = iconEl.getBoundingClientRect();
    const containerRect = mapContainerRef.value.getBoundingClientRect();

    const spaceAbove = iconRect.top - containerRect.top;
    const spaceBelow = containerRect.bottom - iconRect.bottom;
    const spaceLeft  = iconRect.left - containerRect.left;
    const spaceRight = containerRect.right - iconRect.right;

    const max = Math.max(spaceAbove, spaceBelow, spaceLeft, spaceRight);
    
    let style = { position: 'fixed', zIndex: 999, width: POPUP_W + 'px' };
    
    if (max === spaceBelow) {

        style.top = (iconRect.bottom + OFFSET) + 'px';
        style.left = clamp(iconRect.left + iconRect.width / 2 - POPUP_W / 2, containerRect.left + 8, containerRect.right - POPUP_W - 8) + 'px';
        infoArrowDir.value = 'top';
    } else if (max === spaceAbove) {

        style.top = (iconRect.top - POPUP_H - OFFSET) + 'px';
        style.left = clamp(iconRect.left + iconRect.width / 2 - POPUP_W / 2, containerRect.left + 8, containerRect.right - POPUP_W - 8) + 'px';
        infoArrowDir.value = 'bottom';
    } else if (max === spaceRight) {

        style.left = (iconRect.right + OFFSET) + 'px';
        style.top = clamp(iconRect.top + iconRect.height / 2 - POPUP_H / 2, containerRect.top + 8, containerRect.bottom - POPUP_H - 8) + 'px';
        infoArrowDir.value = 'left';
    } else {

        style.left = (iconRect.left - POPUP_W - OFFSET) + 'px';
        style.top = clamp(iconRect.top + iconRect.height / 2 - POPUP_H / 2, containerRect.top + 8, containerRect.bottom - POPUP_H - 8) + 'px';
        infoArrowDir.value = 'right';
    }
    
    infoPopupStyle.value = style;
};

const clamp = (val, min, max) => Math.min(Math.max(val, min), max);

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
    <div class="relative w-full h-full overflow-hidden bg-slate-200 rounded-md flex items-center justify-center border border-ghost-hover shadow-inner touch-none"
        ref="mapContainerRef"
        @mousedown="handleMouseDown"
        @mousemove="handleMouseMove"
        @wheel="handleWheel"
        @touchstart.passive="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        @touchcancel="handleTouchEnd">
        
        <div class="hidden lg:flex absolute top-3 right-3 z-50 flex-col gap-1.5">
            <button @click="zoomLevel = Math.min(zoomLevel + 0.5, 5)" class="p-2 bg-surface/90 backdrop-blur rounded-md shadow border hover:bg-surface text-ink-light"><ZoomIn class="w-4 h-4"/></button>
            <button @click="resetZoom" class="p-2 bg-surface/90 backdrop-blur rounded-md shadow border hover:bg-surface text-ink-light"><Maximize class="w-4 h-4"/></button>
            <button @click="zoomLevel = Math.max(zoomLevel - 0.5, 0.4)" class="p-2 bg-surface/90 backdrop-blur rounded-md shadow border hover:bg-surface text-ink-light"><ZoomOut class="w-4 h-4"/></button>
        </div>

        <div 
            class="relative inline-block bg-surface shadow-2xl rounded-md overflow-hidden select-none"
            :class="{ 'transition-transform duration-150 ease-out': !isDragging }"
            :style="mapStyle"
        >
            <div class="relative flex" @click="handleMapClick">
                <img :src="'/storage/floors/' + floor.map_image_path" 
                     class="max-w-full lg:max-w-none lg:h-[80vh] w-auto md:w-full lg:w-auto block select-none pointer-events-none" 
                     draggable="false" />

                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute top-0 left-0 w-full h-full pointer-events-none">
                    <polygon v-for="room in rooms" :key="'room-' + room.id"
                        v-show="room.coordinates?.length > 0"
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
                            @click.stop="activeInfoId = null; infoPopupStyle.value = {}; infoArrowDir.value = 'bottom'"
                            class="fixed inset-0 z-[998] bg-transparent cursor-default">
                        </div>

                        <Teleport to="body">
                            <div v-if="activeInfoId === asset.id" 
                                :style="infoPopupStyle"
                                class="bg-surface shadow-2xl border border-ghost-hover rounded-md pointer-events-auto overflow-hidden">

                                <div v-if="infoArrowDir === 'bottom'" class="absolute top-full left-1/2 -translate-x-1/2 border-[6px] border-transparent border-t-white drop-shadow-sm"></div>
                                
                                <div v-if="infoArrowDir === 'top'" class="absolute bottom-full left-1/2 -translate-x-1/2 border-[6px] border-transparent border-b-white drop-shadow-sm"></div>
                                
                                <div v-if="infoArrowDir === 'right'" class="absolute top-1/2 left-full -translate-y-1/2 border-[6px] border-transparent border-l-white drop-shadow-sm"></div>
                                
                                <div v-if="infoArrowDir === 'left'" class="absolute top-1/2 right-full -translate-y-1/2 border-[6px] border-transparent border-r-white drop-shadow-sm"></div>
                            <div class="p-2 text-white flex justify-between items-center bg-primary">
                                <div>
                                    <p class="text-[11px] font-bold truncate uppercase">{{ asset.is_double ? 'KOTAK' : '' }} {{ asset.code }}</p>
                                    <p class="text-[8px] opacity-90">{{ asset.room?.name || 'N/A' }}</p>
                                </div>
                            </div>

                            <div class="p-3 space-t-3">
                                <div v-for="item in (asset.is_double ? asset.original_items : [asset])" :key="item.id" 
                                     class="border-b border-ghost-hover last:border-0 pb-3 last:pb-0">
                                    
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[10px] font-black text-ink uppercase">
                                            {{ asset.is_double ? 'TABUNG ' + item.code.split('-').pop() : 'INFO ASET' }}
                                        </span>
                                        <span :class="[
                                            'text-[8px] px-2 py-0.5 rounded-full font-black border uppercase tracking-wider',
                                            item.status?.toLowerCase() === 'safe' ? 'bg-success/10 text-success border-success/30' : (item.status?.toLowerCase() === 'kritis' || item.status?.toLowerCase() === 'critical' ? 'bg-danger/10 text-danger border-danger/30' : 'bg-ghost text-ink-light border-ghost-hover')
                                        ]">
                                            {{ item.status || 'BELUM CEK' }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-y-1.5 text-[9px]">
                                        
                                        <span class="text-ink-light">Tipe Aset:</span>
                                        <span class="text-ink font-bold text-right truncate">
                                            {{ item.type?.name }}
                                        </span>

                                        <template v-if="getAssetType(asset) === 'apar'">
                                            <span class="text-ink-light">Berat (Weight):</span>
                                            <span class="text-ink font-bold text-right">{{ item.weight ? item.weight + ' Kg' : '-' }}</span>
                                            
                                            <span class="text-ink-light">Terakhir Diisi:</span>
                                            <span class="text-ink font-bold text-right">{{ formatDate(item.last_refilled_at) }}</span>
                                            
                                            <span class="text-ink-light">Kadaluarsa:</span>
                                            <span class="text-ink font-bold text-right">{{ formatDate(item.expired_at) }}</span>
                                        </template>
                                    </div>

                                    <div v-if="item.status?.toLowerCase() === 'critical' || item.status?.toLowerCase() === 'kritis'" 
                                         class="mt-2 text-[9px] text-danger bg-danger/10 p-2 rounded-md border border-red-100 leading-snug">
                                        <strong class="block mb-0.5 uppercase text-[8px] tracking-wider">Detail Kerusakan:</strong>
                                        {{ item.last_finding || item.critical_details || 'Hubungi petugas untuk detail lebih lanjut.' }}
                                    </div>

                                    <div class="pt-4">
                                        <button @click.stop="openDetail(item)" 
                                            class="block w-full text-center py-2 bg-ghost hover:bg-ghost-hover text-slate-700 rounded-md text-[9px] font-bold transition">
                                            DETAIL FISIK
                                        </button>
                                    </div>
                                </div>

                                <div class="">
                                    <Link :href="route('reports.index', { tab: getAssetType(asset), asset_code: asset.code })" 
                                          class="block w-full text-center py-2 bg-ghost hover:bg-ghost-hover text-slate-700 rounded-md text-[9px] font-bold transition">
                                        RIWAYAT INSPEKSI
                                    </Link>
                                </div>
                            </div>

                            </div>
                        </Teleport>

                        <div class="relative flex items-center justify-center">
                            <img 
                                :src="asset.is_double ? '/icon-apar2.png' : iconPath" 
                             @click.stop="toggleInfo(asset.id, $event)"
                                class="w-6 h-6 relative z-10 drop-shadow-md cursor-pointer transition-all duration-200" 
                                :class="{'scale-[1.7] brightness-125 z-20': asset.id === selectedAsset?.id || activeInfoId === asset.id}" 
                            />
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="isSlideoverOpen" 
         @click="closeDetail"
         class="fixed inset-0 bg-black/30 z-[1000] transition-opacity cursor-pointer">
    </div>

    <div :class="[
            'fixed top-0 right-0 h-screen w-72 sm:w-80 bg-ghost shadow-2xl z-[1001] transform transition-transform duration-300 ease-in-out flex flex-col',
            isSlideoverOpen ? 'translate-x-0' : 'translate-x-full'
         ]">
         
        <div class="bg-surface p-4 border-b border-ghost-hover shrink-0 shadow-sm z-10">
            <button @click="closeDetail" class="absolute top-4 right-4 text-ink-light hover:text-rose-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <h3 class="font-bold text-ink text-sm pr-6">{{ detailAsset?.code || 'Memuat...' }}</h3>
            <p class="text-[10px] text-ink-light flex items-center gap-1 mt-1 font-medium">
                <svg class="w-3 h-3 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                {{ detailAsset?.room?.name || 'Lokasi tidak terdata' }}
            </p>
        </div>

        <div class="flex-1 overflow-y-auto p-4 relative">
            
            <h4 class="text-xs font-bold text-slate-700 mb-3 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                Parameter Fisik
            </h4>

            <div class="space-y-1.5 relative min-h-[100px]">
                
                <div v-if="isLoadingDetail" class="absolute inset-0 flex flex-col items-center justify-center bg-ghost/80 z-10 rounded-md">
                    <div class="w-5 h-5 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-[10px] text-ink-light mt-2 font-bold animate-pulse">Menyiapkan data...</p>
                </div>

                <template v-else-if="detailAsset?.checklist_results?.length > 0">
                    <div v-for="(param, index) in detailAsset.checklist_results" :key="index" 
                         class="flex justify-between items-center py-1.5 px-2.5 bg-surface rounded-md border border-ghost-hover shadow-sm hover:border-primary transition-all duration-200">
                        
                        <span class="text-[10px] text-ink-light font-medium leading-tight pr-2">
                            {{ param.question }}
                        </span>
                        
                        <span :class="[
                            'text-[9px] px-2 py-0.5 rounded font-bold text-center whitespace-nowrap min-w-[65px]',
                            param.is_safe 
                                ? 'bg-success/20 text-success border border-success/30' 
                                : 'bg-rose-100 text-rose-700 border border-rose-200'
                        ]">
                            {{ param.answer }}
                        </span>
                    </div>
                </template>

                <div v-else class="flex flex-col items-center justify-center py-4 px-2 border-2 border-dashed border-ghost-hover rounded-md text-ink-light bg-surface shadow-sm">
                    <svg class="w-5 h-5 mb-1 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="text-[9px] font-medium">Belum ada data parameter.</p>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.select-none {
    user-select: none;
    -webkit-user-drag: none;
}
svg {
    display: block;
    will-change: transform;
}
</style>