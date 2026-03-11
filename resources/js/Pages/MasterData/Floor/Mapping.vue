<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    ChevronLeft, Undo2, RotateCcw, Box, CheckCircle2, 
    Save, ZoomIn, ZoomOut, Maximize, X 
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import MainLayout from '@/Layouts/MainLayout.vue';
import MappingNavigation from '@/Components/MappingNavigation.vue';
import MappingRoomList from '@/Components/MappingRoomList.vue';

const props = defineProps({
    floor: Object,
    can: Object,
});

const selectedRoom = ref(null);
const isClosed = ref(false);
const zoomLevel = ref(1);
const isDragging = ref(false);
const offset = ref({ x: 0, y: 0 });
const startPan = ref({ x: 0, y: 0 });
const isBottomSheetOpen = ref(false);
const startPinchDist = ref(0);
const startZoomLevel = ref(1);
const hasDragged = ref(false);

const form = useForm({
    room_id: '',
    coordinates: [],
});

const mapStyle = computed(() => {

    const blackCrosshair = `url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23000000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><line x1='12' y1='1' x2='12' y2='23'></line><line x1='1' y1='12' x2='23' y2='12'></line></svg>") 12 12, crosshair`;

    let cursorStyle = 'grab';
    if (isDragging.value) {
        cursorStyle = 'grabbing';
    } else if (selectedRoom.value && !isClosed.value && props.can?.manage) {
        cursorStyle = blackCrosshair;
    }

    return {
        transform: `translate(${offset.value.x}px, ${offset.value.y}px) scale(${zoomLevel.value})`,
        cursor: cursorStyle
    };
});

const handleMouseDown = (event) => {

    if (event.button === 1 || (event.button === 0 && (!selectedRoom.value || !props.can?.manage))) {
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

const stopDragging = () => { isDragging.value = false; };

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

const handleMapClick = (event) => {
    if (hasDragged.value) {
        hasDragged.value = false;
        return;
    }
    if (!props.can?.manage) return;
    if (isDragging.value || !selectedRoom.value || isClosed.value) return;

    const rect = event.currentTarget.getBoundingClientRect();
    const xPercent = ((event.clientX - rect.left) / rect.width) * 100;
    const yPercent = ((event.clientY - rect.top) / rect.height) * 100;

    if (form.coordinates.length >= 3) {
        const firstPoint = form.coordinates[0];
        const distance = Math.sqrt(Math.pow(xPercent - firstPoint.x, 2) + Math.pow(yPercent - firstPoint.y, 2));

        if (distance < (2 / zoomLevel.value)) {
            isClosed.value = true;
            return;
        }
    }

    form.coordinates.push({
        x: parseFloat(xPercent.toFixed(2)),
        y: parseFloat(yPercent.toFixed(2))
    });
};

const selectRoom = (room) => {
    isBottomSheetOpen.value = false;

    if (selectedRoom.value?.id === room.id) {
        selectedRoom.value = null;
        form.reset();
        isClosed.value = false;
        return;
    }

    selectedRoom.value = room;
    form.room_id = room.id;
    if (room.coordinates?.length > 0) {
        form.coordinates = JSON.parse(JSON.stringify(room.coordinates));
        isClosed.value = true;
    } else {
        form.coordinates = [];
        isClosed.value = false;
    }
};

const undoLastPoint = () => isClosed.value ? isClosed.value = false : form.coordinates.pop();
const resetPoints = () => { form.coordinates = []; isClosed.value = false; };

const submitMapping = () => {
    if (!isClosed.value || form.coordinates.length < 3) return;
    form.put(route('rooms.update-coordinates'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedRoom.value = null;
            isClosed.value = false;
            form.reset();
            Swal.fire({ icon: 'success', title: 'Tersimpan!', timer: 1000, showConfirmButton: false });
        }
    });
};

const hexToRgba = (hex, opacity) => {
    if (!hex) return `rgba(59, 130, 246, ${opacity})`; // Default Blue
    let r = parseInt(hex.slice(1, 3), 16), g = parseInt(hex.slice(3, 5), 16), b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

onMounted(() => {
    window.addEventListener('mouseup', stopDragging);

    const params = new URLSearchParams(window.location.search);
    const highlightRoomId = params.get('highlight_room');

    if (highlightRoomId && props.floor.map_image_path) {
        const targetRoom = props.floor.rooms.find(r => r.id == highlightRoomId);
        if (targetRoom) {
            selectRoom(targetRoom);

        }
    }
});

onUnmounted(() => window.removeEventListener('mouseup', stopDragging));
</script>

<template>
    <Head :title="`Mapping - ${floor.name}`" />

    <MainLayout>
        <template #header-title>
            <div class="flex items-center gap-4">
                <Link :href="route('floors.index')" class="p-2 hover:bg-ghost rounded-full transition-colors">
                    <ChevronLeft class="w-5 h-5 text-ink" />
                </Link>
                <div>
                    <h2 class="font-bold text-lg text-ink leading-tight">Pemetaan Area Ruangan</h2>
                    <p class="text-xs text-ink-light">Lantai: {{ floor.name }}</p>
                </div>
            </div>
        </template>

        <template #header-nav>
            <div class="w-full bg-surface border-b border-ghost-hover px-4"> 
                <MappingNavigation :floorId="floor.id" />
            </div>
        </template>

        <div v-if="!floor.map_image_path" class="p-4 m-4 bg-warning/10 border border-warning/30 rounded-md flex items-center gap-4">
            <div class="p-3 bg-warning/20 rounded-full">
                <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-yellow-800">Denah Belum Diupload</h3>
                <p class="text-sm text-warning">Lantai ini belum memiliki gambar denah. Silakan tambahkan file gambar denah di halaman Master Data Lantai terlebih dahulu agar bisa melakukan pemetaan ruangan.</p>
            </div>
        </div>

        <div v-else class="flex flex-col lg:flex-row h-full lg:h-[calc(100vh-180px)] p-2 gap-4 relative">
            <div class="hidden lg:flex w-full lg:w-72 bg-surface rounded-md shadow-sm border border-ghost-hover flex-col overflow-hidden">
                <div class="p-4 bg-ghost border-b font-bold text-ink dark:text-ink-dark/90 flex items-center gap-2 shrink-0">
                    <Box class="w-4 h-4 text-primary" /> Daftar Ruangan
                </div>
                
                <div class="flex-1 overflow-y-auto p-3">
                    <MappingRoomList :rooms="floor.rooms" :selectedRoom="selectedRoom" @select="selectRoom" />
                </div>

                <div v-if="selectedRoom" class="p-4 bg-ghost border-t space-y-3 shrink-0">
                    <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-ink-light">
                        <span>Status:</span>
                        <span :class="isClosed ? 'text-success' : 'text-warning'">{{ isClosed ? 'Area Terkunci' : (can?.manage ? 'Proses Gambar' : 'Hanya Lihat') }}</span>
                    </div>
                    <template v-if="can?.manage">
                        <div class="flex gap-2">
                            <button @click="undoLastPoint" class="flex-1 bg-surface border border-ghost-hover p-2 rounded-md text-xs font-semibold hover:bg-ghost flex items-center justify-center gap-1 transition-colors">
                                <Undo2 class="w-3 h-3"/> Undo
                            </button>
                            <button @click="resetPoints" class="flex-1 bg-surface border border-red-100 text-danger p-2 rounded-md text-xs font-semibold hover:bg-danger/10 flex items-center justify-center gap-1 transition-colors">
                                <RotateCcw class="w-3 h-3"/> Reset
                            </button>
                        </div>
                        <button @click="submitMapping" :disabled="!isClosed || form.processing"
                            class="w-full bg-primary text-white p-3 rounded-md text-sm font-bold disabled:bg-gray-300 shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-2">
                            <Save class="w-4 h-4" /> Simpan Pemetaan
                        </button>
                    </template>
                </div>
            </div>

            <div 
                class="flex-1 bg-slate-200 rounded-md relative overflow-hidden flex items-center justify-center border-2 border-ghost-hover shadow-inner w-full min-h-[60vh] lg:min-h-0 touch-none"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @wheel="handleWheel"
                @touchstart.passive="handleTouchStart"
                @touchmove="handleTouchMove"
                @touchend="handleTouchEnd"
                @touchcancel="handleTouchEnd"
            >
                <div class="hidden lg:flex absolute top-6 right-6 z-50 flex-col gap-2">
                    <button @click="zoomLevel = Math.min(zoomLevel + 0.5, 5)" class="p-3 bg-surface rounded-md shadow-xl hover:bg-ghost text-ink-light transition-transform active:scale-95"><ZoomIn/></button>
                    <button @click="resetZoom" class="p-3 bg-surface rounded-md shadow-xl hover:bg-ghost text-ink-light transition-transform active:scale-95"><Maximize/></button>
                    <button @click="zoomLevel = Math.max(zoomLevel - 0.5, 0.4)" class="p-3 bg-surface rounded-md shadow-xl hover:bg-ghost text-ink-light transition-transform active:scale-95"><ZoomOut/></button>
                </div>

                <div 
                    class="relative inline-block bg-surface shadow-2xl rounded-md overflow-hidden select-none"
                    :style="mapStyle"
                >
                    <div class="relative flex" @click="handleMapClick">
                        <img 
                            :src="'/storage/floors/' + floor.map_image_path" 
                            class="max-w-full lg:max-w-none lg:h-[75vh] w-auto md:w-full lg:w-auto block pointer-events-none" 
                            draggable="false"
                        />
                        
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute top-0 left-0 w-full h-full pointer-events-none">
                            
                            <polygon 
                                v-for="room in floor.rooms" :key="'room-'+room.id"
                                v-show="room.coordinates?.length > 0 && selectedRoom?.id !== room.id"
                                :points="room.coordinates?.map(p => `${p.x},${p.y}`).join(' ')"
                                fill="rgba(156, 163, 175, 0.15)" 
                                stroke="rgba(156, 163, 175, 0.5)" 
                                :stroke-width="0.15 / zoomLevel" 
                            />

                            <g v-if="form.coordinates.length > 0">
                                <component 
                                    :is="isClosed ? 'polygon' : 'polyline'"
                                    :points="form.coordinates.map(p => `${p.x},${p.y}`).join(' ')"
                                    :fill="isClosed ? hexToRgba('#3b82f6', 0.5) : 'transparent'"
                                    :stroke="'#3b82f6'" 
                                    :stroke-width="0.4 / zoomLevel"
                                    :stroke-dasharray="isClosed ? '0' : '1, 1'"
                                />
                                <circle 
                                    v-for="(p, i) in form.coordinates" :key="'point-'+i" 
                                    :cx="p.x" :cy="p.y" 
                                    :r="(i === 0 ? 1.2 : 0.7) / zoomLevel" 
                                    :class="i === 0 ? 'fill-orange-500 stroke-white animate-pulse' : 'fill-indigo-600 stroke-white'"
                                    :stroke-width="0.2 / zoomLevel"
                                />
                            </g>
                        </svg>
                    </div>
                </div>
                
                <div class="absolute bottom-6 left-6 lg:left-6 bg-black/60 text-white px-4 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest backdrop-blur-md pointer-events-none border border-white/20 z-10 transition-transform" :class="isBottomSheetOpen ? '-translate-y-24' : ''">
                    <span class="text-indigo-300">{{ selectedRoom ? selectedRoom.name : 'View Mode' }}</span>
                    <span class="mx-2 opacity-30">|</span>
                    Zoom: {{ Math.round(zoomLevel * 100) }}%
                </div>

                <button 
                    @click="isBottomSheetOpen = true"
                    class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 bg-primary text-white px-6 py-2.5 rounded-full shadow-lg z-20 font-bold text-sm flex items-center gap-2"
                >
                    <Box class="w-4 h-4" /> Daftar Ruangan
                </button>
            </div>

            <!-- Bottom Sheet -->
            <div v-if="isBottomSheetOpen" class="lg:hidden fixed inset-0 z-[100] flex flex-col justify-end">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="isBottomSheetOpen = false"></div>
                <div class="relative bg-surface w-full rounded-t-2xl shadow-xl flex flex-col max-h-[80vh]">
                    <div class="p-4 border-b flex justify-between items-center bg-ghost rounded-t-2xl shrink-0">
                        <div class="font-bold text-ink flex items-center gap-2">
                            <Box class="w-4 h-4 text-primary" /> Daftar Ruangan
                        </div>
                        <button @click="isBottomSheetOpen = false" class="p-1 rounded-full text-ink-light hover:bg-surface border border-transparent">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-4 shrink-1">
                        <MappingRoomList :rooms="floor.rooms" :selectedRoom="selectedRoom" @select="selectRoom" />
                    </div>

                    <div v-if="selectedRoom" class="p-4 bg-ghost border-t space-y-3 shrink-0">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-ink-light">
                            <span>Status:</span>
                            <span :class="isClosed ? 'text-success' : 'text-warning'">{{ isClosed ? 'Area Terkunci' : (can?.manage ? 'Proses Gambar' : 'Hanya Lihat') }}</span>
                        </div>
                        <template v-if="can?.manage">
                            <div class="flex gap-2">
                                <button @click="undoLastPoint" class="flex-1 bg-surface border border-ghost-hover p-2 rounded-md text-xs font-semibold hover:bg-ghost flex items-center justify-center gap-1 transition-colors">
                                    <Undo2 class="w-3 h-3"/> Undo
                                </button>
                                <button @click="resetPoints" class="flex-1 bg-surface border border-red-100 text-danger p-2 rounded-md text-xs font-semibold hover:bg-danger/10 flex items-center justify-center gap-1 transition-colors">
                                    <RotateCcw class="w-3 h-3"/> Reset
                                </button>
                            </div>
                            <button @click="submitMapping" :disabled="!isClosed || form.processing"
                                class="w-full bg-primary text-white p-3 rounded-md text-sm font-bold disabled:bg-gray-300 shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-2">
                                <Save class="w-4 h-4" /> Simpan Pemetaan
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
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
