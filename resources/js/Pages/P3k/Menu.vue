<script setup>
import { Link } from '@inertiajs/vue3';
import { 
    ClipboardCheck, 
    History, 
    ArrowLeft,
    AlertCircle,
    PackageMinus, // Icon baru
    PackagePlus   // Icon baru
} from 'lucide-vue-next';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    asset: Object,
    inspection: Object
});
</script>

<template>
    <Head title="Menu Pelaporan P3K"/>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-200 flex items-center justify-center p-4">
        
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden relative">
            
            <div class="bg-indigo-600 p-8 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/medical-icons.png')]"></div>
                
                <div class="w-20 h-20 mx-auto bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mb-4 shadow-inner ring-1 ring-white/30">
                    <span class="text-4xl">⛑️</span>
                </div>

                <h1 class="text-2xl font-bold text-white tracking-tight">
                    {{ asset.code }}
                </h1>
                <p class="text-indigo-200 text-sm mt-1">{{ asset.room?.name || 'Lokasi tidak diset' }}</p>
            </div>

            <div class="p-6 space-y-4 -mt-4 relative z-10">
                
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Status Aset</p>
                        <p class="font-bold text-gray-800 capitalize flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" 
                                :class="asset.status === 'safe' ? 'bg-green-500' : 'bg-red-500'"></span>
                            {{ asset.status }}
                        </p>
                    </div>
                    <div class="text-right">
                         <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">Tipe</p>
                         <p class="font-bold text-gray-800">{{ asset.p3k_type?.name || 'Standard' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    
                    <Link :href="route('p3k.usage', asset.id)" 
                        class="group relative flex items-center p-4 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md hover:border-red-300 transition-all active:scale-[0.98]">
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-xl flex items-center justify-center mr-4 group-hover:bg-red-600 group-hover:text-white transition-colors">
                            <PackageMinus class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Lapor Pemakaian</h3>
                            <p class="text-xs text-gray-500">Ambil obat / lapor barang rusak</p>
                        </div>
                    </Link>

                    <Link :href="route('p3k.restock', asset.id)" 
                        class="group relative flex items-center p-4 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md hover:border-blue-300 transition-all active:scale-[0.98]">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mr-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <PackagePlus class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Penambahan Stok</h3>
                            <p class="text-xs text-gray-500">Khusus Tim K3 (Restock)</p>
                        </div>
                    </Link>

                    <Link v-if="inspection" 
                        :href="route('inspections.execute', inspection.id)"
                        class="group relative flex items-center p-4 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-300 transition-all active:scale-[0.98]">
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center mr-4 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                            <ClipboardCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">Inspeksi Rutin</h3>
                            <p class="text-xs text-gray-500">Cek kondisi fisik & kelengkapan</p>
                        </div>
                    </Link>

                    <div v-else class="flex items-center p-4 bg-gray-50 border border-gray-100 rounded-2xl opacity-75 cursor-not-allowed">
                        <div class="w-12 h-12 bg-gray-200 text-gray-400 rounded-xl flex items-center justify-center mr-4">
                            <ClipboardCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-400">Inspeksi Rutin</h3>
                            <p class="text-xs text-gray-400">Tidak ada jadwal aktif saat ini</p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-center">
                <Link :href="route('dashboard')" class="text-sm text-gray-500 hover:text-gray-800 font-medium flex items-center gap-2">
                    <ArrowLeft class="w-4 h-4" /> Kembali
                </Link>
            </div>

        </div>
    </div>
</template>