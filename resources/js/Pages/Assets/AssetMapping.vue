<script setup>
import { onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    floor: Object,
    initialAssets: Array, // Data P3K dari Controller
});

const mapContainer = ref(null);
let map = null;

// Konfigurasi Icon Custom P3K
const p3kIcon = L.icon({
    iconUrl: '/images/icon-p3k.png', // Pastikan file ini ada di public/images/
    iconSize: [32, 32], 
    iconAnchor: [16, 32], 
    popupAnchor: [0, -32]
});

// Fungsi Simpan Posisi ke Database
const savePosition = (assetId, newCoords) => {
    router.put(route('mapping.update-position', assetId), {
        location_data: newCoords
    }, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`P3K ${assetId} berhasil dipindah ke:`, newCoords);
        }
    });
};

onMounted(() => {
    // 1. Inisialisasi Map (Mode 2D/Simple)
    map = L.map(mapContainer.value, {
        crs: L.CRS.Simple,
        minZoom: -1,
        maxZoom: 2,
    });

    // 2. Tentukan Gambar Denah & Ukurannya
    const imageUrl = props.floor.map_image 
        ? `/storage/${props.floor.map_image}` 
        : 'https://placehold.co/1200x800?text=Denah+Belum+Diunggah';
    
    // Kita asumsikan skala 1000x1000 agar koordinatnya konsisten (persentase)
    const bounds = [[0, 0], [1000, 1000]]; 
    L.imageOverlay(imageUrl, bounds).addTo(map);
    map.fitBounds(bounds);

    // 3. Looping Render Aset P3K
    props.initialAssets.forEach(asset => {
        // Ambil koordinat dari JSON atau taruh di tengah (500,500) jika kosong
        const x = asset.location_data?.x || 500;
        const y = asset.location_data?.y || 500;

        const marker = L.marker([y, x], { 
            icon: p3kIcon,
            draggable: true 
        }).addTo(map);

        // Tooltip Informasi
        marker.bindPopup(`
            <div class="text-center">
                <p class="font-bold">${asset.code}</p>
                <p class="text-xs text-gray-500">${asset.type?.name || 'P3K'}</p>
                <hr class="my-1">
                <p class="text-[10px] text-blue-500">Geser untuk ubah lokasi</p>
            </div>
        `);

        // 4. Event pas selesai di-drag
        marker.on('dragend', function (event) {
            const { lat, lng } = event.target.getLatLng();
            const newCoords = { 
                x: parseFloat(lng.toFixed(2)), 
                y: parseFloat(lat.toFixed(2)) 
            };
            
            savePosition(asset.id, newCoords);
        });
    });
});
</script>

<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Asset Mapping</h1>
                    <p class="text-slate-500">Lantai: {{ floor.name }}</p>
                </div>
                <div class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg text-sm font-medium">
                    Mode: Interaktif (Drag & Drop Aktif)
                </div>
            </div>

            <div class="bg-white p-2 rounded-2xl shadow-xl border border-slate-200">
                <div 
                    ref="mapContainer" 
                    class="h-[750px] w-full rounded-xl z-10 bg-slate-100"
                ></div>
            </div>
            
            <div class="mt-4 flex gap-4">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                    <span>Klik icon untuk info</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span class="w-3 h-3 bg-orange-500 rounded-full"></span>
                    <span>Tahan & Geser untuk atur posisi</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Hilangkan background abu-abu bawaan Leaflet */
.leaflet-container {
    background: #f1f5f9 !important;
    outline: none;
}

/* Styling Popup agar lebih cantik */
.leaflet-popup-content-wrapper {
    border-radius: 12px;
    padding: 5px;
}
</style>