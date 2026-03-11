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
        
        <div class="bg-surface w-full max-w-md rounded-md shadow-2xl overflow-hidden relative">
            
            <div class="bg-primary p-4 text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/medical-icons.png')]"></div>
                
                <div class="w-20 h-20 mx-auto bg-surface/20 backdrop-blur-sm rounded-md flex items-center justify-center mb-4 shadow-inner ring-1 ring-white/30">
                    <span class="text-4xl">⛑️</span>
                </div>

                <h1 class="text-2xl font-bold text-white tracking-tight">
                    {{ asset.code }}
                </h1>
                <p class="text-indigo-200 text-sm mt-1">{{ asset.room?.name || 'Lokasi tidak diset' }}</p>
            </div>

            <div class="p-4 space-y-4 -mt-4 relative z-10">
                
                <div class="bg-surface rounded-md p-4 shadow-sm border border-ghost-hover flex items-center justify-between">
                    <div>
                        <p class="text-xs text-ink-light uppercase font-bold tracking-wider">Status Aset</p>
                        <p class="font-bold text-ink capitalize flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full" 
                                :class="asset.status === 'safe' ? 'bg-success' : 'bg-danger'"></span>
                            {{ asset.status }}
                        </p>
                    </div>
                    <div class="text-right">
                         <p class="text-xs text-ink-light uppercase font-bold tracking-wider">Tipe</p>
                         <p class="font-bold text-ink">{{ asset.p3k_type?.name || 'Standard' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    
                    <Link :href="route('p3k.usage', asset.id)" 
                        class="group relative flex items-center p-4 bg-surface border border-ghost-hover rounded-md shadow-sm hover:shadow-md hover:border-danger/30 transition-all active:scale-[0.98]">
                        <div class="w-12 h-12 bg-danger/10 text-danger rounded-md flex items-center justify-center mr-4 group-hover:bg-danger group-hover:text-white transition-colors">
                            <PackageMinus class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-ink">Lapor Pemakaian</h3>
                            <p class="text-xs text-ink-light">Ambil obat / lapor barang rusak</p>
                        </div>
                    </Link>

                    <Link :href="route('p3k.restock', asset.id)" 
                        class="group relative flex items-center p-4 bg-surface border border-ghost-hover rounded-md shadow-sm hover:shadow-md hover:border-primary transition-all active:scale-[0.98]">
                        <div class="w-12 h-12 bg-primary/10 text-primary rounded-md flex items-center justify-center mr-4 group-hover:bg-primary group-hover:text-white transition-colors">
                            <PackagePlus class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-ink">Penambahan Stok</h3>
                            <p class="text-xs text-ink-light">Khusus Tim K3 (Restock)</p>
                        </div>
                    </Link>

                    <Link v-if="inspection" 
                        :href="route('p3k.execute-pending', asset.id)"
                        class="group relative flex items-center p-4 bg-surface border border-ghost-hover rounded-md shadow-sm hover:shadow-md hover:border-warning/30 transition-all active:scale-[0.98]">
                        <div class="w-12 h-12 bg-warning/10 text-warning rounded-md flex items-center justify-center mr-4 group-hover:bg-warning group-hover:text-white transition-colors">
                            <ClipboardCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-ink">Inspeksi Rutin</h3>
                            <p class="text-xs text-ink-light">Cek kondisi fisik & kelengkapan</p>
                        </div>
                    </Link>

                    <div v-else class="flex items-center p-4 bg-ghost border border-ghost-hover rounded-md opacity-75 cursor-not-allowed">
                        <div class="w-12 h-12 bg-gray-200 text-ink-light rounded-md flex items-center justify-center mr-4">
                            <ClipboardCheck class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-ink-light">Inspeksi Rutin</h3>
                            <p class="text-xs text-ink-light">Tidak ada jadwal aktif saat ini</p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="p-4 bg-ghost border-t border-ghost-hover flex justify-center">
                <Link :href="route('dashboard')" class="text-sm text-ink-light hover:text-ink font-medium flex items-center gap-2">
                    <ArrowLeft class="w-4 h-4" /> Kembali
                </Link>
            </div>

        </div>
    </div>
</template>