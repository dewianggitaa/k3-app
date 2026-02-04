<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
    meta: Object,
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 w-full">
        
        <div v-if="meta" class="text-[11px] text-ink/60 dark:text-ink-dark/60 font-medium order-2 sm:order-1">
            Menampilkan <span class="font-bold text-ink dark:text-ink-dark">{{ meta.from || 0 }}</span> 
            sampai <span class="font-bold text-ink dark:text-ink-dark">{{ meta.to || 0 }}</span> 
            dari <span class="font-bold text-ink dark:text-ink-dark">{{ meta.total || 0 }}</span> data
        </div>
        <div v-else class="order-2 sm:order-1"></div> <div class="flex flex-wrap gap-1 order-1 sm:order-2">
            <template v-for="(link, key) in links" :key="key">
                
                <div 
                    v-if="link.url === null" 
                    class="px-2 py-1 text-[11px] text-gray-300 dark:text-gray-600 border border-transparent rounded cursor-default select-none"
                    v-html="link.label"
                />
                
                <Link 
                    v-else 
                    :href="link.url"
                    class="px-2.5 py-1 text-[11px] border rounded-md transition-all duration-200 font-medium"
                    :class="[
                        link.active 
                        /* Style Aktif: Solid Primary */
                        ? 'bg-primary border-primary text-white shadow-sm' 
                        /* Style Inaktif: Putih + Border Ghost */
                        : 'bg-surface dark:bg-gray-800 text-ink dark:text-ink-dark border-ghost dark:border-gray-600 hover:bg-ghost hover:text-primary dark:hover:bg-gray-700'
                    ]"
                >
                    <span v-html="link.label"></span>
                </Link>

            </template>
        </div>
    </div>
</template>