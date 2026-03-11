<script setup>
import { ref, watch, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Plus, Pencil, Trash2, Search, Filter, Check,
    CheckCircle2, Type, ToggleLeft, List, ListFilter,
    Hash, AlignLeft, Calendar1,
} from 'lucide-vue-next';

import MainLayout from '@/Layouts/MainLayout.vue';
import Card from '@/Components/Card.vue';
import DataTable from '@/Components/DataTable.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Modal from '@/Components/Modal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    parameters: {
        type: Array,
        default: () => []
    },
    assetTypes: {
        type: Array,
        default: () => []
    },
    currentType: String,
});

const search = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const showOptionsInput = ref(false);
const tempOption = ref('');
const labelInput = ref(null); // Ref untuk input fokus di modal
const filterWrapper = ref(null);
const showFilters = ref(false);

const handleOutsideClick = (e) => {
    // No longer parsing showFilters logic since the dropdown manages its own state
};

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', handleOutsideClick);
});

const filteredParameters = computed(() => {
    const data = props.parameters || [];
    if (!search.value) return data;
    
    const lower = search.value.toLowerCase();
    return data.filter(p => 
        (p.label && p.label.toLowerCase().includes(lower)) || 
        (p.input_type && p.input_type.toLowerCase().includes(lower))
    );
});

const currentTypeLabel = computed(() => {
    const found = props.assetTypes.find(t => t.value === props.currentType);
    return found ? found.label : 'Pilih Aset';
});

const form = useForm({
    id: null,
    asset_type: props.currentType,
    label: '',
    input_type: 'boolean', 
    options: [],
    standard_value: '',
    order_index: 1,
});

const switchType = (type) => {
    router.get(route('checklist-parameters.index'), { type }, { preserveState: true });
    form.asset_type = type; 
};

watch(() => form.input_type, (newVal) => {
    showOptionsInput.value = ['radio', 'select'].includes(newVal);
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.asset_type = props.currentType;
    const lastOrder = props.parameters.length > 0 
        ? Math.max(...props.parameters.map(p => p.order_index)) 
        : 0;
    form.order_index = lastOrder + 1;
    showModal.value = true;

    nextTick(() => {
        if (labelInput.value) {
            labelInput.value.focus();
        }
    });
};

const openEditModal = (item) => {
    isEditing.value = true;
    form.id = item.id;
    form.asset_type = item.asset_type;
    form.label = item.label;
    form.input_type = item.input_type;
    form.options = item.options || [];
    form.standard_value = item.standard_value;
    form.order_index = item.order_index;
    showModal.value = true;

    nextTick(() => {
        if (labelInput.value) {
            labelInput.value.focus();
        }
    });
};

const addOption = () => {
    if (tempOption.value.trim()) {
        form.options.push(tempOption.value.trim());
        tempOption.value = '';
    }
};
const removeOption = (index) => form.options.splice(index, 1);

const submit = () => {
    const url = isEditing.value 
        ? route('checklist-parameters.update', form.id)
        : route('checklist-parameters.store');
    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            showModal.value = false;
            Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Data tersimpan', timer: 1500, showConfirmButton: false });
        }
    });
};

const deleteParam = (id) => {
    Swal.fire({
        title: 'Hapus Parameter?',
        text: "Pertanyaan ini tidak akan muncul lagi di form inspeksi.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('checklist-parameters.destroy', id));
        }
    });
};

const columns = [
    { label: '#', key: 'order', class: 'w-16 text-center' },
    { label: 'Pertanyaan & Opsi', key: 'label' },
    { label: 'Tipe Input', key: 'type', class: 'w-40 text-center' },
    { label: 'Standar Nilai', key: 'standard', class: 'w-48 text-center' },
    { label: 'Aksi', key: 'actions', class: 'w-24 text-center' },
];

const getInputBadge = (type) => {
    const styles = {
        boolean: { class: 'bg-success/20 text-success border-success/30', icon: ToggleLeft, label: 'Ya/Tidak' },
        radio:   { class: 'bg-primary/10 text-primary border-primary', icon: List, label: 'Pilihan Ganda' },
        select:  { class: 'bg-purple-100 text-purple-700 border-purple-200', icon: ListFilter, label: 'Dropdown' },
        text:    { class: 'bg-ghost text-ink dark:text-ink-dark/90 border-ghost-hover', icon: Type, label: 'Teks' },
        number:  { class: 'bg-warning/20 text-warning border-warning/30', icon: Hash, label: 'Angka' },
        textarea:{ class: 'bg-ghost text-ink-light border-ghost-hover', icon: AlignLeft, label: 'Catatan' },
        date    :{ class: 'bg-ghost text-ink-light border-ghost-hover', icon: Calendar1, label: 'Tanggal' },
    };
    return styles[type] || styles.text;
};
</script>

<template>
    <Head title="Manajemen Parameter Checklist" />

    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-ink leading-tight">
                    Manajemen Parameter Checklist
                </h2>
            </div>
        </template>

        <div class="space-y-4">
            
            <Card no-padding class="p-4 overflow-visible" overflow-visible>
                <div class="flex flex-row md:flex-row-reverse gap-2 justify-between items-center w-full">
                    
                    <div class="flex-1 min-w-0 md:flex-none md:w-64">
                        <SearchInput v-model="search" placeholder="Cari pertanyaan..." />
                    </div>

                    <div class="flex shrink-0 gap-2 items-center">
                        <div class="relative w-32 md:w-48">
                            <Dropdown align="right" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-[13px] rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors cursor-pointer font-medium h-[38px]">
                                        <span class="truncate">{{ currentTypeLabel }}</span>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1">
                                         <button v-for="t in assetTypes" :key="t.value" @click="switchType(t.value)" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': currentType === t.value }">
                                            {{ t.label }}
                                        </button>
                                    </div>
                                </template>
                            </Dropdown>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-ink-light h-[38px]">
                                <Filter class="w-4 h-4" />
                            </div>
                        </div>

                        <button @click="openCreateModal" class="p-2 bg-primary hover:bg-primary-hover text-white rounded-md text-sm font-bold flex items-center justify-center transition-colors shadow-sm md:px-4 shrink-0 h-[38px] md:gap-2 w-[38px] md:w-auto">
                            <Plus class="w-5 h-5 md:w-4 md:h-4" />
                            <span class="hidden md:inline">Tambah</span>
                        </button>
                    </div>

                </div>
            </Card>

            <Card no-padding>
                <DataTable :items="filteredParameters" :columns="columns">
                    
                    <template #cell-order="{ item }">
                        <span class="font-mono text-ink-light text-xs">#{{ item.order_index }}</span>
                    </template>

                    <template #cell-label="{ item }">
                        <div class="py-1">
                            <div class="font-bold text-ink text-sm mb-1">{{ item.label }}</div>
                            <div v-if="item.options && item.options.length" class="flex flex-wrap gap-1">
                                <span v-for="opt in item.options" :key="opt" class="text-[10px] bg-ghost text-ink-light px-1.5 py-0.5 rounded border border-ghost-hover">
                                    {{ opt }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <template #cell-type="{ item }">
                        <span :class="['px-2.5 py-1 rounded-full text-xs font-bold border flex items-center gap-1.5 w-fit', getInputBadge(item.input_type).class]">
                            <component :is="getInputBadge(item.input_type).icon" class="w-3.5 h-3.5" />
                            {{ getInputBadge(item.input_type).label }}
                        </span>
                    </template>

                    <template #cell-standard="{ item }">
                        <div v-if="item.standard_value" class="flex items-center gap-1.5 text-xs font-medium text-ink dark:text-ink-dark/90">
                            <CheckCircle2 class="w-3.5 h-3.5 text-success" />
                            <span>
                                {{ item.standard_value === 'true' ? 'Ya / Ada' : (item.standard_value === 'false' ? 'Tidak / Hilang' : item.standard_value) }}
                            </span>
                        </div>
                        <span v-else class="text-xs text-ink-light italic">Tidak ada standar</span>
                    </template>

                    <template #cell-actions="{ item }">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="openEditModal(item)" class="p-1.5 text-primary hover:bg-primary/10 rounded-md transition-colors" title="Edit">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="deleteParam(item.id)" class="p-1.5 text-danger hover:bg-danger/10 rounded-md transition-colors" title="Hapus">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </template>

                </DataTable>
            </Card>

        </div>

        <Modal :show="showModal" @close="showModal = false" overflow-visible>
            <div class="p-4">
                <h3 class="text-lg font-bold text-ink mb-4 border-b pb-3 flex items-center gap-2">
                    <component :is="isEditing ? Pencil : Plus" class="w-5 h-5 text-primary" />
                    {{ isEditing ? 'Edit Parameter' : 'Tambah Parameter Baru' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    
                    <div class="grid grid-cols-4 gap-4">
                        <div class="col-span-3">
                            <label class="block text-sm font-bold text-ink dark:text-ink-dark/90 mb-1">Pertanyaan / Label</label>
                            <input 
                                ref="labelInput" 
                                v-model="form.label" 
                                type="text" 
                                required 
                                class="w-full rounded-md border-ghost-hover focus:border-primary focus:ring-primary text-sm" 
                                placeholder="Contoh: Tekanan Tabung"
                            >
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-ink dark:text-ink-dark/90 mb-1">Urutan</label>
                            <input v-model="form.order_index" type="number" class="w-full rounded-md border-ghost-hover focus:border-primary focus:ring-primary text-sm">
                        </div>
                    </div>

                    <div class="z-50 border border-transparent">
                        <label class="block text-sm font-bold text-ink dark:text-ink-dark/90 mb-1">Tipe Input</label>
                        <div class="relative">
                            <Dropdown align="left" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none bg-ghost border border-ghost-hover hover:border-primary text-ink dark:text-ink-dark/90 text-sm rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors cursor-pointer">
                                        <span class="truncate">{{ {
                                            'boolean': 'Ya / Tidak (Switch)',
                                            'radio': 'Pilihan Ganda (Radio Button)',
                                            'select': 'Dropdown Menu',
                                            'text': 'Teks Singkat',
                                            'number': 'Angka',
                                            'textarea': 'Catatan Panjang',
                                            'date': 'Tanggal'
                                        }[form.input_type] }}</span>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1">
                                        <button type="button" @click="form.input_type = 'boolean'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.input_type === 'boolean' }">Ya / Tidak (Switch)</button>
                                        <button type="button" @click="form.input_type = 'radio'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.input_type === 'radio' }">Pilihan Ganda (Radio Button)</button>
                                        <button type="button" @click="form.input_type = 'select'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.input_type === 'select' }">Dropdown Menu</button>
                                        <button type="button" @click="form.input_type = 'text'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.input_type === 'text' }">Teks Singkat</button>
                                        <button type="button" @click="form.input_type = 'number'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.input_type === 'number' }">Angka</button>
                                        <button type="button" @click="form.input_type = 'textarea'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.input_type === 'textarea' }">Catatan Panjang</button>
                                        <button type="button" @click="form.input_type = 'date'" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.input_type === 'date' }">Tanggal</button>
                                    </div>
                                </template>
                            </Dropdown>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-ink-light">
                                <List class="w-4 h-4" />
                            </div>
                        </div>
                    </div>

                    <div v-if="showOptionsInput" class="bg-ghost p-4 rounded-md border border-dashed border-ghost-hover">
                        <label class="block text-xs font-bold text-ink-light uppercase tracking-wider mb-2">Opsi Jawaban</label>
                        
                        <div class="flex gap-2 mb-3">
                            <input v-model="tempOption" @keyup.enter.prevent="addOption" type="text" placeholder="Ketik opsi lalu Enter..." class="flex-1 rounded-md border-ghost-hover focus:border-primary focus:ring-primary text-sm">
                            <button type="button" @click="addOption" class="bg-surface border border-ghost-hover text-ink dark:text-ink-dark/90 hover:bg-ghost px-3 py-2 rounded-md text-sm font-bold shadow-sm">
                                <Plus class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span v-for="(opt, idx) in form.options" :key="idx" class="bg-surface border px-3 py-1 rounded-full text-xs font-medium flex items-center gap-2 shadow-sm text-ink dark:text-ink-dark/90">
                                {{ opt }}
                                <button type="button" @click="removeOption(idx)" class="text-red-400 hover:text-danger hover:bg-danger/10 rounded-full p-0.5">
                                    <Trash2 class="w-3 h-3" />
                                </button>
                            </span>
                        </div>
                        <p v-if="form.options.length === 0" class="text-[10px] text-danger mt-2">* Minimal masukkan 1 opsi jawaban.</p>
                    </div>

                    <div class="bg-primary/10 p-4 rounded-md border border-primary/20">
                        <label class="block text-sm font-bold text-indigo-900 mb-1">
                            Kunci Jawaban (Normal) <span class="text-danger">*</span>
                        </label>
                        <p class="text-xs text-primary mb-3 opacity-80">Jika jawaban petugas beda dengan ini, status jadi <span class="font-bold text-danger">ISSUE</span>.</p>

                        <div v-if="form.input_type === 'boolean'" class="flex gap-4">
                            <label class="flex items-center gap-2 text-sm bg-surface px-3 py-2 rounded border border-primary cursor-pointer hover:border-indigo-400 transition-colors">
                                <input type="radio" v-model="form.standard_value" value="true" required class="text-primary focus:ring-primary"> 
                                <span class="font-medium text-ink dark:text-ink-dark/90">Ya / Ada</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm bg-surface px-3 py-2 rounded border border-primary cursor-pointer hover:border-indigo-400 transition-colors">
                                <input type="radio" v-model="form.standard_value" value="false" required class="text-primary focus:ring-primary"> 
                                <span class="font-medium text-ink dark:text-ink-dark/90">Tidak / Hilang</span>
                            </label>
                        </div>

                        <div v-else-if="showOptionsInput" class="relative z-40 border border-transparent">
                            <Dropdown align="left" width="full">
                                <template #trigger>
                                    <button type="button" class="appearance-none bg-surface border border-primary hover:border-indigo-400 text-ink dark:text-ink-dark/90 text-sm rounded-md focus:ring-primary focus:border-primary block w-full pl-3 pr-8 py-2 text-left flex justify-between items-center transition-colors cursor-pointer">
                                        <span class="truncate">{{ form.standard_value || '-- Pilih Standar Nilai --' }}</span>
                                    </button>
                                </template>
                                <template #content>
                                    <div class="py-1">
                                        <button type="button" @click="form.standard_value = ''" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.standard_value === '' }">-- Pilih Standar Nilai --</button>
                                        <button type="button" v-for="opt in form.options" :key="opt" @click="form.standard_value = opt" class="block w-full text-left px-4 py-2 text-sm hover:bg-primary/10 hover:text-primary transition-colors cursor-pointer" :class="{ 'bg-primary/10 text-primary font-bold': form.standard_value === opt }">{{ opt }}</button>
                                    </div>
                                </template>
                            </Dropdown>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-ink-light">
                                <List class="w-4 h-4" />
                            </div>
                        </div>

                        <input v-else-if="form.input_type !== 'date'" v-model="form.standard_value" type="text" required class="w-full rounded-md border-primary focus:border-primary focus:ring-primary text-sm" placeholder="Nilai yang diharapkan...">                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-surface border border-ghost-hover text-ink dark:text-ink-dark/90 rounded-md text-sm font-bold hover:bg-ghost transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-md text-sm font-bold hover:bg-primary-hover shadow-sm transition-colors disabled:opacity-50" :disabled="form.processing">
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </MainLayout>
</template>
