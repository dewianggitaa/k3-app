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

// HELPER: Normalisasi Data
// Kalau items itu Array, pake langsung. Kalau Object pagination, ambil .data-nya.
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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th v-for="col in columns" :key="col.key" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" :class="col.class">
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    
                    <tr v-if="tableData.length === 0">
                        <td :colspan="columns.length" class="px-6 py-10 text-center text-gray-500">
                            Belum ada data parameter.
                        </td>
                    </tr>

                    <tr v-else v-for="(item, index) in tableData" :key="item.id || index">
                        <td v-for="col in columns" :key="col.key" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" :class="col.rowClass">
                            <slot :name="`cell-${col.key}`" :item="item" :index="index">
                                {{ item[col.key] }}
                            </slot>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <div v-if="!Array.isArray(items) && items.links" class="border-t border-gray-200 px-4 py-3">
            <Pagination :links="items.links" />
        </div>
    </div>
</template>