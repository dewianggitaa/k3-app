<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';
import { 
    Pencil, Trash2, Plus, MapPin, Save, X, 
    BriefcaseMedical, ChevronLeft, ChevronDown, Filter, Building2, Layers, Box
} from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import NavLink from '@/Components/NavLink.vue';

const props = defineProps({
    p3ks: Object,
    p3k_types: Array,
    buildings: Array,
    floors: Array,
    rooms: Array,       
    filters: Object,
    can: Object,
});

const showFilters = ref(false);
const building = ref(props.filters?.building || 'all');
const floor = ref(props.filters?.floor || 'all');
const room = ref(props.filters?.room || 'all');
const search = ref(props.filters?.search || '');

const showModal = ref(false);
const isEditing = ref(false);

const filteredFloors = computed(() => {
    if (building.value === 'all') return props.floors;
    return props.floors.filter(f => f.building_id === building.value);
});

const filteredRooms = computed(() => {
    let result = props.rooms;
    if (building.value !== 'all') {
        result = result.filter(r => r.floor.building_id === building.value);
    }
    if (floor.value !== 'all') {
        result = result.filter(r => r.floor_id === floor.value);
    }
    return result;
});

watch(building, () => {
    floor.value = 'all';
    room.value = 'all';
});

watch(floor, () => {
    room.value = 'all';
});

const applyFilters = debounce(() => {
    router.get(
        route('p3ks.index'), 
        { 
            search: search.value || undefined,
            building: building.value !== 'all' ? building.value : undefined,
            floor: floor.value !== 'all' ? floor.value : undefined,
            room: room.value !== 'all' ? room.value : undefined,
        }, 
        { preserveState: true, replace: true }
    );
}, 300);

watch([search, building, floor, room], () => applyFilters());

const form = useForm({
    id: null,
    code: '',
    p3k_type_id: '',
    room_id: '',
    status: 'safe',
    location_data: null, 
});

const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.code = item.code;
    form.p3k_type_id = item.p3k_type_id;
    form.room_id = item.room_id;
    form.status = item.status;
    form.location_data = item.location_data;
    showModal.value = true;
};

const submit = () => {
    const action = isEditing.value 
        ? route('p3ks.update', form.id) 
        : route('p3ks.store');
    
    const method = isEditing.value ? 'put' : 'post';

    form[method](action, {
        onSuccess: () => {
            showModal.value = false;
            toast.fire({ 
                icon: 'success', 
                title: isEditing.value ? 'Data P3K diperbarui' : 'Data P3K ditambahkan' 
            });
        }
    });
};

const deleteP3k = (id, code) => {
    Swal.fire({
        title: 'Hapus Kotak P3K?',
        text: `P3K ${code} akan dihapus secara permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('p3ks.destroy', id), {
                onSuccess: () => toast.fire({ icon: 'success', title: 'Berhasil dihapus' })
            });
        }
    });
};

const columns = [
    { label: 'No', key: 'no', class: 'w-10 sm:w-12 text-center' },
    { label: 'Kode P3K', key: 'code', class: 'font-bold min-w-[120px]' },
    { label: 'Tipe', key: 'type', class: 'min-w-[100px]' },
    { label: 'Lokasi', key: 'location', class: 'min-w-[150px]' },
    { label: 'Status', key: 'status', class: 'min-w-[100px]' },
    ...(props.can?.manage ? [{ label: 'Aksi', key: 'action', class: 'w-24 text-center' }] : []),
];
</script>

<template>
    <Head title="Master Data P3K" />

    <MainLayout>
        <template #header-title>
            <div class="flex flex-col items-start w-full">
                <div class="flex items-center gap-4 mb-4 px-4"> 
                    <Link :href="route('dashboard')" class="p-2 -ml-2 hover:bg-ghost rounded-full transition-colors">
                        <ChevronLeft class="w-5 h-5 text-ink" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-lg text-ink leading-tight">
                             {{ route().current('apars.*') ? 'Data APAR' : (route().current('hydrants.*') ? 'Data Hydrant' : 'Data P3K') }}
                        </h2>
                    </div>
                </div>
            </div>
        </template>

        <template #header-nav>
            <nav class="flex items-center gap-4 px-4 overflow-x-auto custom-scrollbar pb-1"> 
                <NavLink 
                    :href="route('apars.index')" 
                    :active="route().current('apars.index')"
                    class="text-xs font-bold transition-all duration-200 whitespace-nowrap"
                >
                    APAR
                </NavLink>

                <NavLink 
                    :href="route('hydrants.index')" 
                    :active="route().current('hydrants.index')"
                    class="text-xs font-bold transition-all duration-200 whitespace-nowrap"
                >
                    Hydrant
                </NavLink>

                <NavLink 
                    :href="route('p3ks.index')" 
                    :active="route().current('p3ks.index')"
                    class="text-xs font-bold transition-all duration-200 whitespace-nowrap"
                >
                    P3K
                </NavLink>
            </nav>
        </template>

        <div class="space-y-4">
            
            <Card no-padding class="p-4 overflow-visible" overflow-visible>
                <div class="flex flex-col xl:flex-row-reverse gap-4 justify-between items-start xl:items-center w-full">
                    
                    <button 
                        v-if="can?.manage" 
                        @click="openCreateModal" 
                        class="bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-md shadow-sm flex items-center justify-center sm:gap-2 transition-all h-[38px] w-full xl:w-auto sm:px-4 shrink-0"
                    >
                        <Plus class="w-5 h-5 sm:w-4 sm:h-4" /> 
                        <span>Tambah P3K</span>
                    </button>

                    <div class="flex flex-col xl:flex-row gap-4 w-full xl:w-auto xl:flex-1 items-start xl:items-center">
                        <div class="flex w-full xl:w-auto gap-2">
                            <div class="flex-1 min-w-0 xl:flex-none xl:w-64">
                                <SearchInput v-model="search" placeholder="Cari kode atau tipe P3K..." />
                            </div>
                            <button @click="showFilters = !showFilters" class="xl:hidden p-2 bg-ghost border border-ghost-hover hover:border-primary text-ink-light rounded-md flex items-center justify-center h-[38px] w-[38px] shrink-0 transition-colors">
                                <Filter class="w-4 h-4" />
                            </button>
                        </div>

                        <div :class="[showFilters ? 'flex' : 'hidden', 'xl:flex flex-col xl:flex-row flex-wrap gap-2 w-full xl:w-auto items-start xl:items-center']">
                            <div class="flex flex-col sm:flex-row w-full xl:w-auto gap-2 items-center pt-1 xl:pt-0">
                                
                                <!-- Filter Gedung -->
                                <div class="w-full sm:w-40 flex-shrink-0">
                                    <Dropdown align="left" width="full">
                                        <template #trigger>
                                            <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[12px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors outline-none cursor-pointer">
                                                <div class="flex items-center gap-1.5 truncate whitespace-nowrap">
                                                    <Building2 class="w-3.5 h-3.5 text-ink-light shrink-0" />
                                                    <span class="truncate">{{ building === 'all' ? 'Sem. Gedung' : buildings.find(b => b.id === building)?.name }}</span>
                                                </div>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-ink-light">
                                                    <ChevronDown class="w-3.5 h-3.5" />
                                                </div>
                                            </button>
                                        </template>
                                        <template #content>
                                            <div class="py-1 max-h-60 overflow-y-auto">
                                                <button @click="building = 'all'" class="block w-full text-left px-4 py-2 text-xs hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': building === 'all' }">
                                                    Semua Gedung
                                                </button>
                                                <button v-for="b in buildings" :key="b.id" @click="building = b.id" class="block w-full text-left px-4 py-2 text-xs hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': building === b.id }">
                                                    {{ b.name }}
                                                </button>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- Filter Lantai -->
                                <div class="w-full sm:w-40 flex-shrink-0">
                                    <Dropdown align="left" width="full">
                                        <template #trigger>
                                            <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[12px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors outline-none cursor-pointer">
                                                <div class="flex items-center gap-1.5 truncate whitespace-nowrap">
                                                    <Layers class="w-3.5 h-3.5 text-ink-light shrink-0" />
                                                    <span class="truncate">
                                                        <template v-if="floor === 'all'">Sem. Lantai</template>
                                                        <template v-else>
                                                            <span class="font-semibold text-[10px] uppercase mr-1">{{ filteredFloors.find(f => f.id === floor)?.building?.name }}</span>
                                                            {{ filteredFloors.find(f => f.id === floor)?.name }}
                                                        </template>
                                                    </span>
                                                </div>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-ink-light">
                                                    <ChevronDown class="w-3.5 h-3.5" />
                                                </div>
                                            </button>
                                        </template>
                                        <template #content>
                                            <div class="py-1 max-h-60 overflow-y-auto">
                                                <button @click="floor = 'all'" class="block w-full text-left px-4 py-2 text-xs hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': floor === 'all' }">
                                                    Semua Lantai
                                                </button>
                                                <button v-for="f in filteredFloors" :key="f.id" @click="floor = f.id" class="block w-full text-left px-4 py-2 text-xs hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': floor === f.id }">
                                                    <span v-if="building === 'all'" class="font-bold text-[9px] uppercase block leading-none text-ink-light">{{ f.building?.name }}</span>
                                                    {{ f.name }}
                                                </button>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- Filter Ruangan -->
                                <div class="w-full sm:w-48 flex-shrink-0">
                                    <Dropdown align="left" width="full">
                                        <template #trigger>
                                            <button type="button" class="appearance-none h-[38px] bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[12px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors outline-none cursor-pointer">
                                                <div class="flex items-center gap-1.5 truncate whitespace-nowrap">
                                                    <Box class="w-3.5 h-3.5 text-ink-light shrink-0" />
                                                    <span class="truncate">
                                                        <template v-if="room === 'all'">Sem. Ruangan</template>
                                                        <template v-else>
                                                            <span class="font-semibold text-[10px] uppercase mr-1">{{ filteredRooms.find(r => r.id === room)?.floor?.name }}</span>
                                                            {{ filteredRooms.find(r => r.id === room)?.name }}
                                                        </template>
                                                    </span>
                                                </div>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-ink-light">
                                                    <ChevronDown class="w-3.5 h-3.5" />
                                                </div>
                                            </button>
                                        </template>
                                        <template #content>
                                            <div class="py-1 max-h-60 overflow-y-auto">
                                                <button @click="room = 'all'" class="block w-full text-left px-4 py-2 text-xs hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': room === 'all' }">
                                                    Semua Ruangan
                                                </button>
                                                <button v-for="r in filteredRooms" :key="r.id" @click="room = r.id" class="block w-full text-left px-4 py-2 text-xs hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': room === r.id }">
                                                    <span v-if="floor === 'all' || building === 'all'" class="font-bold text-[9px] uppercase block leading-none text-ink-light">{{ r.floor?.building?.name }} - {{ r.floor?.name }}</span>
                                                    {{ r.name }}
                                                </button>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <Card no-padding className="h-full">
                <DataTable :items="p3ks" :columns="columns">
                <template #cell-no="{ index }">
                    <span class="text-ink-light font-mono text-[10px]">
                        {{ (p3ks.current_page - 1) * p3ks.per_page + index + 1 }}
                    </span>
                </template>

                <template #cell-type="{ item }">
                    <span class="px-2 py-1 rounded-md bg-success/10 text-success text-[10px] font-bold border border-emerald-100 whitespace-nowrap">
                        {{ item.type?.name }}
                    </span>
                </template>

                <template #cell-location="{ item }">
                    <div class="text-[11px]">
                        <p class="font-bold text-ink dark:text-ink-dark/90 line-clamp-1">{{ item.room?.name || 'Belum diatur' }}</p>
                        <p class="text-ink-light line-clamp-1">
                            {{ item.room?.floor?.building?.name }} - {{ item.room?.floor?.name }}
                        </p>
                    </div>
                </template>

                <template #cell-status="{ item }">
                    <div :class="[
                        'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase border whitespace-nowrap',
                        item.status === 'safe' ? 'bg-success/10 text-success border-green-100' : 
                        item.status === 'warning' ? 'bg-warning/10 text-warning border-orange-100' : 
                        'bg-danger/10 text-danger border-red-100'
                    ]">
                        <span class="w-1.5 h-1.5 rounded-full fill-current shrink-0" :class="item.status === 'safe' ? 'bg-success' : item.status === 'warning' ? 'bg-warning' : 'bg-danger'"></span>
                        {{ item.status }}
                    </div>
                </template>

                <template #cell-action="{ item }">
                    <div v-if="can?.manage" class="flex justify-end gap-0.5 sm:gap-1">
                        <div v-if="item.room?.floor_id">
                            <Link 
                                :href="route('assets.mapping', { 
                                    floor: item.room.floor_id, 
                                    target_id: item.id, 
                                    target_type: 'p3k' 
                                })"
                                class="p-1.5 sm:p-2 text-primary hover:text-primary hover:bg-primary/10 rounded-md flex items-center justify-center transition-colors"
                                title="Atur Posisi di Peta"
                            >
                                <MapPin class="w-4 h-4" />
                            </Link>
                        </div>
                        <div v-else class="p-1.5 sm:p-2 text-gray-300 cursor-not-allowed flex items-center justify-center" title="Lokasi belum diatur">
                            <MapPin class="w-4 h-4" />
                        </div>

                        <button @click="openEditModal(item)" class="p-1.5 sm:p-2 text-ink-light hover:text-primary hover:bg-primary/10 rounded-md transition-colors">
                            <Pencil class="w-4 h-4" />
                        </button>
                        <button @click="deleteP3k(item.id, item.code)" class="p-1.5 sm:p-2 text-ink-light hover:text-danger hover:bg-danger/10 rounded-md transition-colors">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </Card>

        </div>

        <Modal :show="showModal" @close="showModal = false" max-width="md">
            <div class="p-4 sm:p-5">
                <div class="flex justify-between items-center mb-5 sm:mb-6 border-b pb-4">
                    <h2 class="text-base sm:text-lg font-bold flex items-center gap-2 text-ink">
                        <BriefcaseMedical class="w-5 h-5 text-success" />
                        {{ isEditing ? 'Edit Data P3K' : 'Tambah P3K Baru' }}
                    </h2>
                    <button @click="showModal = false" class="p-1.5 rounded-full hover:bg-ghost transition-colors">
                        <X class="w-4 h-4 sm:w-5 sm:h-5 text-ink-light" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                    <div>
                        <InputLabel value="Kode P3K" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <TextInput v-model="form.code" class="w-full text-sm py-2" placeholder="Misal: P3K-L1-001" required />
                        <InputError :message="form.errors.code" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                        <div>
                            <InputLabel value="Tipe Kotak" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                            <select v-model="form.p3k_type_id" class="w-full border-ghost-hover rounded-md text-sm py-2 px-3 focus:border-primary focus:ring-primary bg-surface dark:bg-page-dark text-ink dark:text-ink-dark/90" required>
                                <option value="" disabled>Pilih Tipe</option>
                                <option v-for="type in p3k_types" :key="type.id" :value="type.id">{{ type.name }}</option>
                            </select>
                            <InputError :message="form.errors.p3k_type_id" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Status" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                            <select v-model="form.status" class="w-full border-ghost-hover rounded-md text-sm py-2 px-3 focus:border-primary focus:ring-primary bg-surface dark:bg-page-dark text-ink dark:text-ink-dark/90" required>
                                <option value="safe">Safe</option>
                                <option value="warning">Warning</option>
                                <option value="critical">Critical</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Lokasi Ruangan" class="mb-1 text-[10px] uppercase tracking-widest text-ink-light" />
                        <select v-model="form.room_id" class="w-full border-ghost-hover rounded-md text-sm py-2 px-3 focus:border-primary focus:ring-primary bg-surface dark:bg-page-dark text-ink dark:text-ink-dark/90">
                            <option value="" disabled>Pilih Ruangan (Opsional)</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.floor?.building?.name }} - {{ room.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.room_id" class="mt-1" />
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t mt-6">
                        <button 
                            type="button" 
                            @click="showModal = false" 
                            class="w-full sm:w-auto px-4 py-2 sm:py-2.5 text-sm font-semibold text-ink-light hover:text-ink transition-colors bg-ghost sm:bg-transparent rounded-md"
                        >
                            Batal
                        </button>
                        <PrimaryButton 
                            class="w-full sm:w-auto justify-center bg-primary hover:bg-primary-hover shadow-md sm:shadow-lg shadow-indigo-200 py-2 sm:py-2.5"
                            :class="{ 'opacity-25': form.processing }" 
                            :disabled="form.processing"
                        >
                            <Save class="w-4 h-4 mr-2" />
                            {{ isEditing ? 'Update P3K' : 'Simpan Data' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </MainLayout>
</template>