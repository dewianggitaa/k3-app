<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash'; // Buat delay search biar gak spam request
import { Pencil, Trash2, Plus } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';

const props = defineProps({
    buildings: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(
        route('buildings.index'), 
        { search: value }, 
        { preserveState: true, replace: true } // Biar gak refresh halaman total
    );
}, 300));

const columns = [
    { 
        label: 'No', 
        key: 'no', 
        class: 'w-12 text-center' 
    },
    { 
        label: 'Nama Gedung', 
        key: 'name', 
        class: 'font-medium min-w-[200px]' 
    },
    { 
        label: '', 
        key: 'action', 
        class: 'w-24 text-right' 
    },
];

const deleteBuilding = (id, name) => {
    if (confirm(`Yakin hapus gedung "${name}"? Data lantai & ruangan di dalamnya juga bakal ilang lho.`)) {
        router.delete(route('buildings.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Data Gedung" />

    <MainLayout>
        <template #header>Master Data Gedung</template>
        
        <Card no-padding className="h-full">
            
            <template #header>
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                    
                    <div class="w-full sm:w-64">
                        <SearchInput v-model="search" placeholder="Cari nama gedung..." />
                    </div>

                    <Link 
                        :href="route('buildings.create')" 
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold px-3 py-2 rounded-md shadow-sm flex items-center gap-2 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Gedung</span>
                    </Link>
                </div>
            </template>

            <DataTable :items="buildings" :columns="columns">
                
                <template #cell-no="{ index }">
                    <span class="text-gray-400 font-mono text-[10px]">
                        {{ (buildings.current_page - 1) * buildings.per_page + index + 1 }}
                    </span>
                </template>

                <template #cell-code="{ item }">
                    <span class="px-2 py-1 rounded-md text-[10px] font-mono bg-ghost dark:bg-gray-800 text-ink dark:text-ink-dark border border-gray-200 dark:border-gray-700 tracking-wide">
                        {{ item.code }}
                    </span>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end items-center gap-2">
                        
                        <Link 
                            :href="route('buildings.edit', item.id)" 
                            class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary/10 rounded-md transition-colors"
                            title="Edit Data"
                        >
                            <Pencil class="w-3.5 h-3.5" />
                        </Link>

                        <button 
                            @click="deleteBuilding(item.id, item.name)"
                            class="p-1.5 text-gray-400 hover:text-danger hover:bg-danger/10 rounded-md transition-colors"
                            title="Hapus Data"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>

                    </div>
                </template>

            </DataTable>

        </Card>
    </MainLayout>
</template>