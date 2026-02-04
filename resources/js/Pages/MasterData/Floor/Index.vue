<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Pencil, Trash2, Plus, Layers } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';

const props = defineProps({
    floors: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

// Logic search buat lantai
watch(search, debounce((value) => {
    router.get(
        route('floors.index'), // Pastikan route-nya bener ke floors
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300));

const columns = [
    { 
        label: 'No', 
        key: 'no', 
        class: 'w-12 text-center' 
    },
    { 
        label: 'Gedung', 
        key: 'building_name', 
        class: 'w-48' 
    },
    { 
        label: 'Nama Lantai', 
        key: 'name', 
        class: 'font-medium' 
    },
    { 
        label: 'Gambar Denah', 
        key: 'map_url', 
        class: 'min-w-[150px]' // Kita kasih min-width biar gak sempit
    },
    { 
        label: 'Tinggi (px)', 
        key: 'map_height', 
        class: 'w-24 text-center', 
        rowClass: 'text-center font-mono' // Pakai font mono biar rapi angkanya
    },
    { 
        label: 'Lebar (px)', 
        key: 'map_width', 
        class: 'w-24 text-center', 
        rowClass: 'text-center font-mono' 
    },
    { 
        label: '', 
        key: 'action', 
        class: 'w-24 text-right' 
    },
];

const deleteFloor = (id, name) => {
    if (confirm(`Hapus ${name}? Semua data ruangan di lantai ini juga bakal dihapus.`)) {
        router.delete(route('floors.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Data Lantai" />

    <MainLayout>
        <template #header>Master Data Lantai</template>
        
        <Card no-padding className="h-full">
            <template #header>
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                    <div class="w-full sm:w-64">
                        <SearchInput v-model="search" placeholder="Cari lantai atau gedung..." />
                    </div>

                    <Link 
                        :href="route('floors.create')" 
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold px-3 py-2 rounded-md shadow-sm flex items-center gap-2 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Lantai</span>
                    </Link>
                </div>
            </template>

            <DataTable :items="floors" :columns="columns">
                <template #cell-no="{ index }">
                    <span class="text-gray-400 font-mono text-[10px]">
                        {{ (floors.current_page - 1) * floors.per_page + index + 1 }}
                    </span>
                </template>

                <template #cell-building_name="{ item }">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-primary/40"></div>
                        <span class="font-semibold text-ink/80 dark:text-ink-dark/80 italic">
                            {{ item.building?.name }}
                        </span>
                    </div>
                </template>

                <template #cell-name="{ item }">
                    <div class="flex items-center gap-2">
                        <Layers class="w-3 h-3 text-gray-400" />
                        <span>{{ item.name }}</span>
                    </div>
                </template>

                <template #cell-map_url="{ item }">
                    <div v-if="item.map_url" class="flex items-center">
                        <a 
                            :href="item.map_url" 
                            target="_blank" 
                            class="text-[10px] bg-ghost dark:bg-gray-800 text-primary px-2 py-0.5 rounded border border-primary/20 hover:bg-primary/10 transition-all font-medium"
                        >
                            Buka Link
                        </a>
                    </div>
                    <span v-else class="text-[10px] text-gray-400 italic">No Image</span>
                </template>

                <template #cell-map_height="{ item }">
                    <span v-if="item.map_height" class="text-[10px]">{{ item.map_height }}px</span>
                    <span v-else class="text-gray-300">-</span>
                </template>

                <template #cell-map_width="{ item }">
                    <span v-if="item.map_width" class="text-[10px]">{{ item.map_width }}px</span>
                    <span v-else class="text-gray-300">-</span>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end items-center gap-2">
                        <Link 
                            :href="route('floors.edit', item.id)" 
                            class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary/10 rounded-md transition-colors"
                        >
                            <Pencil class="w-3.5 h-3.5" />
                        </Link>

                        <button 
                            @click="deleteFloor(item.id, item.name)"
                            class="p-1.5 text-gray-400 hover:text-danger hover:bg-danger/10 rounded-md transition-colors"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </Card>
    </MainLayout>
</template>