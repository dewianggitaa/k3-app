<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    ChevronLeft, Undo2, RotateCcw, Box, CheckCircle2, 
    Save, ZoomIn, ZoomOut, Maximize 
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import MainLayout from '@/Layouts/MainLayout.vue';
import MappingNavigation from '@/Components/MappingNavigation.vue';

const props = defineProps({
    floor: Object,
});

// --- State Utama ---
const selectedRoom = ref(null);
const isClosed = ref(false);
const zoomLevel = ref(1);

const form = useForm({
    room_id: '',
    coordinates: [],
});

// --- State Zoom & Pan (Drag) ---
const isDragging = ref(false);
const offset = ref({ x: 0, y: 0 });
const startPan = ref({ x: 0, y: 0 });

// --- Logic Drag (Geser) ---
const handleMouseDown = (event) => {
    // Tombol 0 = Kiri, Tombol 1 = Tengah (Scroll)
    // Bisa drag jika klik tengah, ATAU klik kiri tapi tidak sedang pilih ruangan
    if (event.button === 1 || (event.button === 0 && !selectedRoom.value)) {
        isDragging.value = true;
        startPan.value = { 
            x: event.clientX - offset.value.x, 
            y: event.clientY - offset.value.y 
        };
        document.body.style.cursor = 'grabbing';
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
    if (isDragging.value) {
        isDragging.value = false;
        document.body.style.cursor = 'default';
    }
};

// Pastikan drag berhenti jika mouse lepas di luar jendela browser
onMounted(() => {
    window.addEventListener('mouseup', stopDragging);
});
onUnmounted(() => {
    window.removeEventListener('mouseup', stopDragging);
});

// --- Logic Zoom ---
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

// --- Logic Mapping (Klik Titik) ---
const handleMapClick = (event) => {
    // Jika sedang drag, jangan anggap sebagai klik mapping
    if (isDragging.value || !selectedRoom.value || isClosed.value) return;

    const rect = event.currentTarget.getBoundingClientRect();
    
    // Hitung posisi klik relatif terhadap ukuran gambar saat ini
    const xPercent = ((event.clientX - rect.left) / rect.width) * 100;
    const yPercent = ((event.clientY - rect.top) / rect.height) * 100;

    // Cek jika klik dekat dengan titik awal untuk menutup bidang
    if (form.coordinates.length >= 3) {
        const firstPoint = form.coordinates[0];
        const distance = Math.sqrt(
            Math.pow(xPercent - firstPoint.x, 2) + 
            Math.pow(yPercent - firstPoint.y, 2)
        );

        if (distance < 2 / zoomLevel.value) {
            isClosed.value = true;
            return;
        }
    }

    form.coordinates.push({
        x: parseFloat(xPercent.toFixed(2)),
        y: parseFloat(yPercent.toFixed(2))
    });
};

// --- CRUD & UI Helpers ---
const selectRoom = (room) => {
    selectedRoom.value = room;
    form.room_id = room.id;
    if (room.coordinates && Array.isArray(room.coordinates) && room.coordinates.length > 0) {
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
    form.post(route('rooms.update-coordinates'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedRoom.value = null;
            isClosed.value = false;
            form.reset();
            Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Area berhasil disimpan', timer: 1500, showConfirmButton: false });
        }
    });
};

const hexToRgba = (hex, opacity) => {
    if (!hex) return `rgba(79, 70, 229, ${opacity})`;
    let r = parseInt(hex.slice(1, 3), 16), g = parseInt(hex.slice(3, 5), 16), b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};
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
                    <h2 class="font-bold text-lg text-gray-800 leading-tight">Pemetaan Area</h2>
                    <p class="text-xs text-gray-500">Lantai: {{ floor.name }}</p>
                </div>
            </div>
        </template>

        <template #header-nav>
            <div class="w-full bg-white border-b border-gray-100 px-6"> 
                <MappingNavigation :floorId="floor.id" />
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-160px)] p-2">
            <div class="w-full lg:w-72 bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
                <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 flex items-center gap-2">
                    <Box class="w-4 h-4 text-indigo-500" /> Daftar Ruangan
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <button 
                        v-for="room in floor.rooms" :key="room.id" @click="selectRoom(room)"
                        :class="['w-full text-left px-4 py-3 rounded-xl text-sm border flex justify-between items-center transition-all shadow-sm',
                                  selectedRoom?.id === room.id ? 'bg-indigo-50 border-indigo-200 text-indigo-700 ring-2 ring-indigo-500/10' : 'bg-white border-gray-100 text-gray-600 hover:border-indigo-200']"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: room.color }"></div>
                            <span class="font-medium">{{ room.name }}</span>
                        </div>
                        <CheckCircle2 v-if="room.coordinates?.length > 0" class="w-4 h-4 text-green-500" />
                    </button>
                </div>

                <div v-if="selectedRoom" class="p-4 bg-gray-50 border-t space-y-3">
                    <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-gray-400">
                        <span>Status:</span>
                        <span :class="isClosed ? 'text-green-600' : 'text-orange-500'">{{ isClosed ? 'Terkunci' : 'Proses Gambar' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="undoLastPoint" class="flex-1 bg-white border border-gray-200 p-2 rounded-lg text-xs font-semibold hover:bg-gray-100 flex items-center justify-center gap-1">
                            <Undo2 class="w-3 h-3"/> Undo
                        </button>
                        <button @click="resetPoints" class="flex-1 bg-white border border-red-100 text-red-500 p-2 rounded-lg text-xs font-semibold hover:bg-red-50 flex items-center justify-center gap-1">
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
                    <button @click="zoomLevel = Math.min(zoomLevel + 0.5, 5)" class="p-3 bg-white rounded-xl shadow-xl hover:bg-gray-50 transition-colors text-gray-600"><ZoomIn/></button>
                    <button @click="resetZoom" class="p-3 bg-white rounded-xl shadow-xl hover:bg-gray-50 transition-colors text-gray-600"><Maximize/></button>
                    <button @click="zoomLevel = Math.max(zoomLevel - 0.5, 0.4)" class="p-3 bg-white rounded-xl shadow-xl hover:bg-gray-50 transition-colors text-gray-600"><ZoomOut/></button>
                </div>

                <div 
                    class="relative inline-block bg-white shadow-2xl rounded-lg overflow-hidden select-none"
                    :style="{ 
                        transform: `translate(${offset.x}px, ${offset.y}px) scale(${zoomLevel})`,
                        transition: isDragging ? 'none' : 'transform 0.15s ease-out',
                        cursor: isDragging ? 'grabbing' : (selectedRoom ? 'crosshair' : 'grab')
                    }"
                >
                    <div class="relative flex" @click="handleMapClick">
                        <img 
                            :src="'/storage/floors/' + floor.map_image_path" 
                            class="max-w-none h-[75vh] w-auto block pointer-events-none" 
                            draggable="false"
                        />
                        
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute top-0 left-0 w-full h-full pointer-events-none">
                            <g v-for="room in floor.rooms" :key="'room-'+room.id">
                                <polygon 
                                    v-if="room.coordinates?.length > 0 && selectedRoom?.id !== room.id"
                                    :points="room.coordinates.map(p => `${p.x},${p.y}`).join(' ')"
                                    :fill="hexToRgba(room.color, 0.2)" 
                                    :stroke="room.color" 
                                    stroke-width="0.15" 
                                />
                            </g>

                            <g v-if="form.coordinates.length > 0">
                                <component 
                                    :is="isClosed ? 'polygon' : 'polyline'"
                                    :points="form.coordinates.map(p => `${p.x},${p.y}`).join(' ')"
                                    :fill="isClosed ? hexToRgba(selectedRoom?.color, 0.4) : 'transparent'"
                                    :stroke="selectedRoom?.color" 
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
/* Menghilangkan seleksi teks/gambar saat dragging */
.select-none {
    user-select: none;
    -webkit-user-drag: none;
}

/* Memastikan transisi smooth tapi tidak mengganggu dragging */
svg {
    display: block;
    will-change: transform;
}
</style>