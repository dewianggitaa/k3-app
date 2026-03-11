<script setup>
import { computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    items: {
        type: [Array, Object], // Bisa Array (Client side) atau Object (Server side pagination)
        default: () => [],
    },
    columns: {
        type: Array,
        default: () => [],
    },
});

const tableData = computed(() => {
    if (Array.isArray(props.items)) {
        return props.items;
    } else if (props.items && props.items.data) {
        return props.items.data;
    }
    return [];
});
</script>

<template>
    <div class="w-full">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ghost-hover dark:divide-ghost-dark transition-colors duration-300 shadow-lg rounded-md">
                <thead class="bg-ghost dark:bg-ghost-dark">
                    <tr>
                        <th v-for="col in columns" :key="col.key" class="px-3 py-2 text-left text-[11px] font-bold text-ink-light dark:text-ink-dark/70 uppercase tracking-wider" :class="col.class">
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-surface dark:bg-surface-dark divide-y divide-ghost-hover dark:divide-ghost-dark">
                    
                    <tr v-if="tableData.length === 0">
                        <td :colspan="columns.length" class="px-3 py-4 text-center text-[12px] text-ink-light dark:text-ink-dark/70">
                            Belum ada data parameter.
                        </td>
                    </tr>

                    <tr v-else v-for="(item, index) in tableData" :key="item.id || index" class="hover:bg-ghost/50 dark:hover:bg-ghost-dark/50 transition-colors duration-150">
                        <td v-for="col in columns" :key="col.key" class="px-3 py-2 whitespace-nowrap text-[12px] text-ink dark:text-ink-dark" :class="col.rowClass">
                            <slot :name="`cell-${col.key}`" :item="item" :index="index">
                                {{ item[col.key] }}
                            </slot>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <div v-if="!Array.isArray(items) && items.links" class="border-t border-ghost-hover px-4 py-3">
            <Pagination :links="items.links" />
        </div>
    </div>
</template>