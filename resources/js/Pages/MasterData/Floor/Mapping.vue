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

const props = defineProps({
    floor: Object,
});

// --- State Management ---
const selectedRoom = ref(null);
const isClosed = ref(false);
const zoomLevel = ref(1);
const isDragging = ref(false);
const offset = ref({ x: 0, y: 0 });
const startPan = ref({ x: 0, y: 0 });

const form = useForm({
    room_id: '',
    coordinates: [],
});

const mapStyle = computed(() => {
    // Kursor Crosshair saat mode edit
    const blackCrosshair = `url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23000000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><line x1='12' y1='1' x2='12' y2='23'></line><line x1='1' y1='12' x2='23' y2='12'></line></svg>") 12 12, crosshair`;

    return {
        transform: `translate(${offset.value.x}px, ${offset.value.y}px) scale(${zoomLevel.value})`,
        cursor: isDragging.value 
            ? 'grabbing' 
            : (selectedRoom.value && !isClosed.value ? blackCrosshair : 'grab')
    };
});

// --- Mouse & Zoom Logic ---
const handleMouseDown = (event) => {
    if (event.button === 1 || (event.button === 0 && !selectedRoom.value)) {
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

// --- Mapping Logic ---
const handleMapClick = (event) => {
    if (isDragging.value || !selectedRoom.value || isClosed.value) return;

    const rect = event.currentTarget.getBoundingClientRect();
    const xPercent = ((event.clientX - rect.left) / rect.width) * 100;
    const yPercent = ((event.clientY - rect.top) / rect.height) * 100;

    // Auto-close polygon
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
    // Unselect logic
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

// --- Lifecycle ---
onMounted(() => {
    window.addEventListener('mouseup', stopDragging);

    // Auto-Select dari URL
    const params = new URLSearchParams(window.location.search);
    const highlightRoomId = params.get('highlight_room');

    if (highlightRoomId) {
        const targetRoom = props.floor.rooms.find(r => r.id == highlightRoomId);
        if (targetRoom) {
            selectRoom(targetRoom);
            // NOTIFIKASI DIHAPUS SESUAI REQUEST
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
                <Link :href="route('floors.index')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <ChevronLeft class="w-5 h-5 text-black" />
                </Link>
                <div>
                    <h2 class="font-bold text-lg text-gray-800 leading-tight">Pemetaan Area Ruangan</h2>
                    <p class="text-xs text-gray-500">Lantai: {{ floor.name }}</p>
                </div>
            </div>
        </template>

        <template #header-nav>
            <div class="w-full bg-white border-b border-gray-100 px-6"> 
                <MappingNavigation :floorId="floor.id" />
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-180px)] p-2">
            <div class="w-full lg:w-72 bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
                <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 flex items-center gap-2">
                    <Box class="w-4 h-4 text-indigo-500" /> Daftar Ruangan
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <button 
                        v-for="room in floor.rooms" :key="room.id" @click="selectRoom(room)"
                        :class="['w-full text-left px-4 py-3 rounded-xl text-sm border flex justify-between items-center transition-all shadow-sm',
                                  selectedRoom?.id === room.id ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-2 ring-indigo-500/10' : 'bg-white border-gray-100 text-gray-600 hover:border-indigo-200']"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: room.color || '#ccc' }"></div>
                            <span class="font-medium">{{ room.name }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <CheckCircle2 v-if="room.coordinates?.length > 0" class="w-4 h-4 text-green-500" />
                            <X v-if="selectedRoom?.id === room.id" class="w-4 h-4 text-indigo-400" />
                        </div>
                    </button>
                </div>

                <div v-if="selectedRoom" class="p-4 bg-gray-50 border-t space-y-3">
                    <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-gray-400">
                        <span>Status:</span>
                        <span :class="isClosed ? 'text-green-600' : 'text-orange-500'">{{ isClosed ? 'Area Terkunci' : 'Proses Gambar' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="undoLastPoint" class="flex-1 bg-white border border-gray-200 p-2 rounded-lg text-xs font-semibold hover:bg-gray-100 flex items-center justify-center gap-1 transition-colors">
                            <Undo2 class="w-3 h-3"/> Undo
                        </button>
                        <button @click="resetPoints" class="flex-1 bg-white border border-red-100 text-red-500 p-2 rounded-lg text-xs font-semibold hover:bg-red-50 flex items-center justify-center gap-1 transition-colors">
                            <RotateCcw class="w-3 h-3"/> Reset
                        </button>
                    </div>
                    <button @click="submitMapping" :disabled="!isClosed || form.processing"
                        class="w-full bg-indigo-600 text-white p-3 rounded-xl text-sm font-bold disabled:bg-gray-300 shadow-lg shadow-indigo-200 transition-all flex items-center justify-center gap-2">
                        <Save class="w-4 h-4" /> Simpan Pemetaan
                    </button>
                </div>
            </div>

            <div 
                class="flex-1 bg-slate-200 rounded-2xl relative overflow-hidden flex items-center justify-center border-2 border-slate-300 shadow-inner"
                @mousedown="handleMouseDown"
                @mousemove="handleMouseMove"
                @wheel="handleWheel"
            >
                <div class="absolute top-6 right-6 z-50 flex flex-col gap-2">
                    <button @click="zoomLevel = Math.min(zoomLevel + 0.5, 5)" class="p-3 bg-white rounded-xl shadow-xl hover:bg-gray-50 text-gray-600 transition-transform active:scale-95"><ZoomIn/></button>
                    <button @click="resetZoom" class="p-3 bg-white rounded-xl shadow-xl hover:bg-gray-50 text-gray-600 transition-transform active:scale-95"><Maximize/></button>
                    <button @click="zoomLevel = Math.max(zoomLevel - 0.5, 0.4)" class="p-3 bg-white rounded-xl shadow-xl hover:bg-gray-50 text-gray-600 transition-transform active:scale-95"><ZoomOut/></button>
                </div>

                <div 
                    class="relative inline-block bg-white shadow-2xl rounded-sm overflow-hidden select-none"
                    :style="mapStyle"
                >
                    <div class="relative flex" @click="handleMapClick">
                        <img 
                            :src="'/storage/floors/' + floor.map_image_path" 
                            class="max-w-none h-[75vh] w-auto block pointer-events-none" 
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
                                    :fill="isClosed ? hexToRgba(selectedRoom?.color || '#3b82f6', 0.5) : 'transparent'"
                                    :stroke="selectedRoom?.color || '#3b82f6'" 
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
                
                <div class="absolute bottom-6 left-6 bg-black/60 text-white px-4 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest backdrop-blur-md pointer-events-none border border-white/20">
                    <span class="text-indigo-300">{{ selectedRoom ? selectedRoom.name : 'View Mode' }}</span>
                    <span class="mx-2 opacity-30">|</span>
                    Zoom: {{ Math.round(zoomLevel * 100) }}%
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