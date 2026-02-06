<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Pencil, Trash2, Plus, Layers, X, Map } from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    floors: Object,
    buildings: Array,
    filters: Object,
});

const showModal = ref(false);
const editMode = ref(false);
const targetId = ref(null);

const form = useForm({
    building_id: '',
    name: '',
    map_image: null,
});

const openCreate = () => {
    editMode.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (item) => {
    editMode.value = true;
    targetId.value = item.id;
    form.clearErrors();
    form.building_id = item.building_id;
    form.name = item.name;
    form.map_image = null;
    showModal.value = true;
};

const submit = () => {
    if (editMode.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put', 
        })).post(route('floors.update', targetId.value), {
            forceFormData: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
            onError: (err) => {
                console.error("VALIDATION ERROR:", err); // Cek console browser pas klik simpan
                alert("Gagal simpan! Cek console.");
            }
        });
    } else {
        form.post(route('floors.store'), {
            forceFormData: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            }
        });
    }
};

const search = ref(props.filters.search || '');
watch(search, debounce((value) => {
    router.get(
        route('floors.index'), 
        { search: value }, 
        { preserveState: true, replace: true }
    );
}, 300));

const columns = [
    { label: 'No', key: 'no', class: 'w-12 text-center' },
    { label: 'Gedung', key: 'building_name', class: 'w-48' },
    { label: 'Nama Lantai', key: 'name', class: 'font-medium' },
    { label: 'Gambar Denah', key: 'map_url', class: 'min-w-[150px]' },
    { label: 'Tinggi (px)', key: 'map_height', class: 'w-24 text-center' },
    { label: 'Lebar (px)', key: 'map_width', class: 'w-24 text-center' },
    { label: '', key: 'action', class: 'w-24 text-right' },
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

                    <button 
                        @click="openCreate"
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold px-3 py-2 rounded-md shadow-sm flex items-center gap-2 transition-all"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Lantai</span>
                    </button>
                </div>
            </template>

            <DataTable :items="floors || []" :columns="columns">
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
                    <div v-if="item.map_image_path" class="flex items-center">
                        <a 
                            :href="'/storage/floors/' + item.map_image_path" 
                            target="_blank" 
                            class="text-[10px] bg-ghost dark:bg-gray-800 text-primary px-2 py-0.5 rounded border border-primary/20 hover:bg-primary/10 transition-all font-medium"
                        >
                            Lihat Denah
                        </a>
                    </div>
                    <span v-else class="text-[10px] text-gray-400 italic">No Image</span>
                </template>

                <template #cell-action="{ item }">
                    <div class="flex justify-end items-center gap-2">
                        <Link 
                            :href="route('floors.mapping', item.id)" 
                            class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors"
                            title="Mapping Ruangan"
                        >
                            <Map class="w-3.5 h-3.5" />
                        </Link>
                        <button @click="openEdit(item)" class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary/10 rounded-md transition-colors">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>

                        <button @click="deleteFloor(item.id, item.name)" class="p-1.5 text-gray-400 hover:text-danger hover:bg-danger/10 rounded-md transition-colors">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </Card>

        <Modal :show="showModal" @close="showModal = false" maxWidth="md">
            <form @submit.prevent="submit" class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-ink">{{ editMode ? 'Edit Lantai' : 'Tambah Lantai' }}</h2>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel value="Pilih Gedung" />
                        <select v-model="form.building_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm">
                            <option value="" disabled>Pilih Gedung</option>
                            <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <InputError :message="form.errors.building_id" />
                    </div>

                    <div>
                        <InputLabel value="Nama Lantai" />
                        <TextInput v-model="form.name" type="text" class="mt-1 block w-full" placeholder="Masukan nama lantai..." />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel value="Upload Denah (PNG/JPG)" />
                        <input 
                            type="file" 
                            @input="form.map_image = $event.target.files[0]"
                            class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer"
                        />
                        <p class="text-[10px] text-gray-400 mt-1 italic">*Kosongkan jika tidak ingin mengubah denah</p>
                        <InputError :message="form.errors.map_image" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-xs font-medium text-gray-600 hover:text-gray-800 border rounded-md">Batal</button>
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>