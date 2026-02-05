<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, MousePointer2, Save, Undo2, RotateCcw, Box, CheckCircle2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    floor: Object, // Berisi data floor dan floor.rooms
});

const selectedRoom = ref(null);
const isClosed = ref(false);

const form = useForm({
    room_id: '',
    coordinates: [],
});

// Toast Helper
const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

const selectRoom = (room) => {
    selectedRoom.value = room;
    form.room_id = room.id;

    // Logika parsing koordinat
    if (room.coordinates && Array.isArray(room.coordinates) && room.coordinates.length > 0) {
        form.coordinates = JSON.parse(JSON.stringify(room.coordinates));
        isClosed.value = true;
    } 
    else if (typeof room.coordinates === 'string') {
        try {
            const parsed = JSON.parse(room.coordinates);
            form.coordinates = Array.isArray(parsed) ? parsed : [];
            isClosed.value = form.coordinates.length > 0;
        } catch (e) {
            form.coordinates = [];
            isClosed.value = false;
        }
    }
    else {
        form.coordinates = [];
        isClosed.value = false;
    }
};

const handleMapClick = (event) => {
    if (!selectedRoom.value || isClosed.value) return;

    const rect = event.currentTarget.getBoundingClientRect();
    const xPercent = ((event.clientX - rect.left) / rect.width) * 100;
    const yPercent = ((event.clientY - rect.top) / rect.height) * 100;

    // Snapping Logic
    if (form.coordinates.length >= 3) {
        const firstPoint = form.coordinates[0];
        const distance = Math.sqrt(
            Math.pow(xPercent - firstPoint.x, 2) + 
            Math.pow(yPercent - firstPoint.y, 2)
        );

        if (distance < 2) { 
            isClosed.value = true;
            return;
        }
    }

    form.coordinates.push({
        x: parseFloat(xPercent.toFixed(2)),
        y: parseFloat(yPercent.toFixed(2))
    });
};

const undoLastPoint = () => {
    if (isClosed.value) {
        isClosed.value = false;
    } else {
        form.coordinates.pop();
    }
};

const resetPoints = () => {
    form.coordinates = [];
    isClosed.value = false;
};

const submitMapping = () => {
    if (!isClosed.value || form.coordinates.length < 3) {
        Swal.fire({
            icon: 'warning',
            title: 'Bidang belum tertutup',
            text: 'Klik kembali titik awal (warna oranye) sebelum menyimpan!',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    form.post(route('rooms.update-coordinates'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedRoom.value = null;
            isClosed.value = false;
            form.reset();
            toast.fire({
                icon: 'success',
                title: 'Mapping area berhasil disimpan'
            });
        },
        onError: () => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Simpan',
                text: 'Terjadi kesalahan saat menyimpan koordinat.'
            });
        }
    });
};

// Helper untuk konversi HEX ke RGBA (agar area transparan)
const hexToRgba = (hex, opacity) => {
    if (!hex) return `rgba(34, 197, 94, ${opacity})`; // Default hijau
    let c;
    if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
        c= hex.substring(1).split('');
        if(c.length== 3){
            c= [c[0], c[0], c[1], c[1], c[2], c[2]];
        }
        c= '0x'+c.join('');
        return 'rgba('+[(c>>16)&255, (c>>8)&255, c&255].join(',')+','+opacity+')';
    }
    return hex;
};
</script>

<template>
    <Head :title="`Mapping Area - ${floor.name}`" />

    <MainLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('floors.index')" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <ChevronLeft class="w-5 h-5 text-black" />
                </Link>
                <div>
                    <h2 class="font-bold text-xl text-gray-800">Pemetaan Area Ruangan</h2>
                    <p class="text-xs text-gray-500">Lantai: {{ floor.name }}</p>
                </div>
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-180px)]">
            
            <div class="w-full lg:w-80 bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col">
                <div class="p-4 border-b bg-gray-50 rounded-t-xl">
                    <h3 class="font-bold text-gray-700 flex items-center gap-2 text-sm">
                        <Box class="w-4 h-4 text-indigo-500" />
                        Pilih Ruangan
                    </h3>
                </div>
                
                <div class="flex-1 overflow-y-auto p-2 space-y-1">
                    <button 
                        v-for="room in floor.rooms" 
                        :key="room.id"
                        @click="selectRoom(room)"
                        :class="[
                            'w-full text-left px-4 py-3 rounded-lg text-sm transition-all border flex justify-between items-center',
                            selectedRoom?.id === room.id 
                                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold ring-2 ring-indigo-500/10' 
                                : 'bg-white border-transparent hover:bg-gray-50 text-gray-600'
                        ]"
                    >
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: room.color || '#ddd' }"></div>
                            <span>{{ room.name }}</span>
                        </div>
                        <div v-if="room.coordinates?.length > 0" class="flex items-center gap-1">
                            <CheckCircle2 class="w-3.5 h-3.5 text-green-500" />
                        </div>
                    </button>
                </div>

                <div v-if="selectedRoom" class="p-4 bg-slate-50 border-t space-y-3">
                    <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider">
                        <span class="text-gray-500">Status:</span>
                        <span v-if="isClosed" class="text-green-600">Area Terkunci</span>
                        <span v-else class="text-orange-500">Menggambar...</span>
                    </div>

                    <div v-if="!isClosed && form.coordinates.length >= 3" class="bg-blue-50 text-[10px] text-blue-700 p-2 rounded border border-blue-100 italic">
                        Klik titik oranye untuk menutup bidang.
                    </div>

                    <div class="flex gap-2">
                        <button @click="undoLastPoint" class="flex-1 bg-white border border-gray-300 py-2 rounded-lg text-xs font-medium hover:bg-gray-100 flex items-center justify-center gap-1">
                            <Undo2 class="w-3 h-3" /> Undo
                        </button>
                        <button @click="resetPoints" class="flex-1 bg-white border border-red-200 text-red-600 py-2 rounded-lg text-xs font-medium hover:bg-red-50 flex items-center justify-center gap-1">
                            <RotateCcw class="w-3 h-3" /> Reset
                        </button>
                    </div>

                    <button 
                        @click="submitMapping"
                        :disabled="!isClosed || form.processing"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white py-2.5 rounded-lg text-sm font-bold flex justify-center items-center gap-2 transition-all shadow-md"
                    >
                        <Save class="w-4 h-4" />
                        Simpan Area
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-gray-200 rounded-xl border-2 border-dashed border-gray-300 relative overflow-hidden flex items-center justify-center p-8">
                
                <div class="relative inline-block shadow-2xl rounded-lg overflow-hidden bg-white">
                    <img 
                        :src="'/storage/floors/' + floor.map_image_path" 
                        @click="handleMapClick"
                        class="max-w-full max-h-[70vh] block cursor-crosshair select-none"
                    />

                    <svg 
                        viewBox="0 0 100 100" 
                        preserveAspectRatio="none"
                        class="absolute top-0 left-0 w-full h-full pointer-events-none"
                    >
                        <g v-for="room in floor.rooms" :key="'saved-'+room.id">
                            <polygon 
                                v-if="Array.isArray(room.coordinates) && room.coordinates.length > 0 && selectedRoom?.id !== room.id"
                                :points="room.coordinates.map(p => `${p.x},${p.y}`).join(' ')"
                                :fill="hexToRgba(room.color, 0.2)"
                                :stroke="room.color || '#16a34a'"
                                stroke-width="0.2"
                            />
                        </g>

                        <g v-if="form.coordinates.length > 0">
                            <component 
                                :is="isClosed ? 'polygon' : 'polyline'"
                                :points="form.coordinates.map(p => `${p.x},${p.y}`).join(' ')"
                                :fill="isClosed ? hexToRgba(selectedRoom?.color, 0.4) : 'transparent'"
                                :stroke="selectedRoom?.color || '#4f46e5'"
                                stroke-width="0.5"
                                :stroke-dasharray="isClosed ? '0' : '1.5'"
                            />  
                            <circle 
                                v-for="(p, i) in form.coordinates" 
                                :key="'v-'+i"
                                :cx="p.x" :cy="p.y" 
                                :r="i === 0 ? 1.2 : 0.8" 
                                :class="[
                                    i === 0 ? 'fill-orange-500 animate-pulse stroke-white' : 'fill-indigo-600 stroke-white',
                                    'stroke-[0.2]'
                                ]"
                            />
                        </g>
                    </svg>

                    <div class="absolute bottom-2 left-2 bg-black/60 text-white text-[10px] px-2 py-1 rounded-md backdrop-blur-sm">
                        {{ selectedRoom ? 'Menggambar: ' + selectedRoom.name : 'Pilih ruangan di kiri' }}
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
.cursor-crosshair {
    cursor: crosshair;
}
svg {
    display: block;
}
</style>