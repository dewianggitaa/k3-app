<script setup>
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    items: Object,
    columns: Array,
});
</script>

<template>
    <div class="w-full">
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ghost dark:divide-gray-700">
                
                <thead class="bg-ghost/50 dark:bg-gray-800/50">
                    <tr>
                        <th 
                            v-for="col in columns" 
                            :key="col.key"
                            class="px-5 py-3 text-left text-[11px] font-bold text-ink/70 dark:text-ink-dark/70 uppercase tracking-wider font-sans"
                            :class="col.class"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-ghost dark:divide-gray-700 bg-surface dark:bg-surface-dark">
                    
                    <tr v-if="items.data.length === 0">
                        <td :colspan="columns.length" class="px-5 py-10 text-center">
                            <div class="flex flex-col items-center justify-center text-ink/50 dark:text-ink-dark/50">
                                <span class="text-sm font-medium italic">Belum ada data ditemukan.</span>
                            </div>
                        </td>
                    </tr>

                    <tr 
                        v-else
                        v-for="(item, index) in items.data" 
                        :key="item.id || index" 
                        class="hover:bg-ghost/30 dark:hover:bg-white/5 transition-colors duration-150 group"
                    >
                        <td 
                            v-for="col in columns" 
                            :key="col.key" 
                            class="px-5 py-2.5 text-xs text-ink dark:text-ink-dark align-middle"
                            :class="col.rowClass" 
                        >
                            <slot 
                                :name="`cell-${col.key}`" 
                                :item="item" 
                                :index="index"
                            >
                                {{ item[col.key] }}
                            </slot>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <div v-if="items.data.length > 0" class="border-t border-ghost dark:border-gray-700 px-5 py-3 bg-surface dark:bg-surface-dark rounded-b-lg">
            <Pagination :links="items.links" :meta="items" />
        </div>

    </div>
</template>