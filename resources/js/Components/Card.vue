<script setup>
/**
 * CARD COMPONENT
 * Standar container buat aplikasi.
 * * Props:
 * - title: String (Judul card sederhana)
 * - noPadding: Boolean (True = Hilangkan padding body, khusus buat Tabel mentok pinggir)
 * - className: String (Buat nambah class custom kayak width/height)
 * * Slots:
 * - header: Buat custom header (search bar, tombol, dll)
 * - action: Buat tombol di pojok kanan header
 * - default: Isi konten
 * - footer: Bagian bawah (misal tombol simpan / pagination)
 */
defineProps({
    title: String,
    noPadding: {
        type: Boolean,
        default: false
    },
    className: {
        type: String,
        default: ''
    },
    overflowVisible: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <div 
        class="bg-surface dark:bg-surface-dark border border-ghost dark:border-ghost-dark rounded-md shadow-sm flex flex-col transition-colors duration-300 w-full"
        :class="className"
    >
        
        <div v-if="title || $slots.header" 
             class="px-3 py-3 border-b border-ghost dark:border-ghost-dark flex items-center justify-between shrink-0 min-h-[40px]">
             
            <div class="flex-1">
                <h3 v-if="title" class="font-bold text-[13px] text-ink dark:text-ink-dark tracking-wide">
                    {{ title }}
                </h3>
                
                <slot v-else name="header" />
            </div>
            
            <div v-if="$slots.action" class="ml-3 flex items-center gap-2">
                <slot name="action" />
            </div>
        </div>

        <div class="flex-1 custom-scrollbar" 
            :class="[ 
                noPadding ? 'p-0' : 'p-3',
                overflowVisible ? '!overflow-visible' : 'overflow-x-hidden overflow-y-auto'
            ]"
        >
            <slot />
        </div>

        <div v-if="$slots.footer" class="px-3 py-2.5 border-t border-ghost dark:border-ghost-dark bg-ghost/5 dark:bg-surface-dark/30 rounded-b-lg shrink-0">
            <slot name="footer" />
        </div>

    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 3px; height: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4b5563; }
</style>