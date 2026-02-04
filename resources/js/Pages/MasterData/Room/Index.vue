<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Pencil, Trash2, Plus, MapPin, Hash } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';

const props = defineProps({
    rooms: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, debounce((value) => {
    router.get(
        route('rooms.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300));

const columns = [
    { label: 'No', key: 'no', class: 'w-12 text-center' },
    { label: 'Lokasi (Gedung - Lantai)', key: 'location', class: 'w-64' },
    { label: 'Nama Ruangan', key: 'name', class: 'font-medium' },
    { label: 'Warna', key: 'color', class: 'w-24 text-center' },
    { label: 'Koordinat', key: 'coordinates', class: 'w-48 text-center' }, // Kasih lebar dikit karena biasanya angka koordinat panjang
    { label: '', key: 'action', class: 'w-24 text-right' },
];

const deleteRoom = (id, name) => {
    if (confirm(`Hapus ruangan ${name}?`)) {
        router.delete(route('rooms.destroy', id));
    }
};
</script>

<template>
    <Head title="Master Data Ruangan" />

    <MainLayout>
        <template #header>Master Data Ruangan</template>
        
        <Card no-padding className="h-full">
            <template #header>
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                    <div class="w-full sm:w-64">
                        <SearchInput v-model="search" placeholder="Cari ruangan, lantai, atau gedung..." />
                    </div>

                    <Link 
                        :href="route('rooms.create')" 
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold px-3 py-2 rounded-md shadow-sm flex items-center gap-2 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Ruangan</span>
                    </Link>
                </div>
            </template>

            <DataTable :items="rooms" :columns="columns">
                <template #cell-no="{ index }">
                    <span class="text-gray-400 font-mono text-[10px]">
                        {{ (rooms.current_page - 1) * rooms.per_page + index + 1 }}
                    </span>
                </template>

                <template #cell-location="{ item }">
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-primary uppercase tracking-tighter">
                            {{ item.floor?.building?.name }}
                        </span>
                        <span class="text-[10px] text-gray-500 flex items-center gap-1">
                            <MapPin class="w-2.5 h-2.5" /> {{ item.floor?.name }}
                        </span>
                    </div>
                </template>

                <template #cell-name="{ item }">
                    <span class="text-ink dark:text-ink-dark">{{ item.name }}</span>
                </template>

                <template #cell-color="{ item }">
                    <div class="flex justify-center items-center">
                        <div 
                            v-if="item.color"
                            class="w-4 h-4 rounded-full border border-gray-200 shadow-sm"
                            :style="{ backgroundColor: item.color }"
                            :title="item.color"
                        ></div>
                        <span v-else class="text-gray-300">-</span>
                    </div>
                </template>

                <template #cell-coordinates="{ item }">
                    <div v-if="item.coordinates" class="flex justify-center">
                        <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-ghost dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                            <Hash class="w-3 h-3 text-gray-400" />
                            <span class="text-[10px] font-mono tracking-tight">{{ item.coordinates }}</span>
                        </div>
                    </div>
                    <span v-else class="text-gray-300">-</span>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end items-center gap-2">
                        <Link 
                            :href="route('rooms.edit', item.id)" 
                            class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary/10 rounded-md transition-colors"
                        >
                            <Pencil class="w-3.5 h-3.5" />
                        </Link>

                        <button 
                            @click="deleteRoom(item.id, item.name)"
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