<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Plus, Pencil, Trash2, Search, Filter, Check,
    CheckCircle2, Type, ToggleLeft, List, ListFilter,
    Hash, AlignLeft
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

// Modal Actions with Focus Handling
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.asset_type = props.currentType;
    const lastOrder = props.parameters.length > 0 
        ? Math.max(...props.parameters.map(p => p.order_index)) 
        : 0;
    form.order_index = lastOrder + 1;
    showModal.value = true;

    // Pindahkan fokus ke input label agar tidak error aria-hidden
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

// Helper Opsi
const addOption = () => {
    if (tempOption.value.trim()) {
        form.options.push(tempOption.value.trim());
        tempOption.value = '';
    }
};
const removeOption = (index) => form.options.splice(index, 1);

// Submit
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

// --- TABLE CONFIG ---
const columns = [
    { label: '#', key: 'order', class: 'w-16 text-center' },
    { label: 'Pertanyaan & Opsi', key: 'label' },
    { label: 'Tipe Input', key: 'type', class: 'w-40' },
    { label: 'Standar Nilai', key: 'standard', class: 'w-48' },
    { label: '', key: 'actions', class: 'w-24 text-right' },
];

// Helper Style Badge
const getInputBadge = (type) => {
    const styles = {
        boolean: { class: 'bg-green-100 text-green-700 border-green-200', icon: ToggleLeft, label: 'Ya/Tidak' },
        radio:   { class: 'bg-blue-100 text-blue-700 border-blue-200', icon: List, label: 'Pilihan Ganda' },
        select:  { class: 'bg-purple-100 text-purple-700 border-purple-200', icon: ListFilter, label: 'Dropdown' },
        text:    { class: 'bg-gray-100 text-gray-700 border-gray-200', icon: Type, label: 'Teks' },
        number:  { class: 'bg-orange-100 text-orange-700 border-orange-200', icon: Hash, label: 'Angka' },
        textarea:{ class: 'bg-gray-100 text-gray-600 border-gray-200', icon: AlignLeft, label: 'Catatan' },
    };
    return styles[type] || styles.text;
};
</script>

<template>
    <Head title="Manajemen Parameter Checklist" />

    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-gray-800 leading-tight">
                    Manajemen Parameter Checklist
                </h2>
            </div>
        </template>

        <div class="space-y-4">
            
            <Card no-padding class="p-4 !overflow-visible relative z-10">
                <div class="flex flex-col md:flex-row gap-4 justify-between items-center">
                    
                    <div class="w-full md:w-1/3">
                        <SearchInput v-model="search" placeholder="Cari pertanyaan..." />
                    </div>

                    <div class="flex flex-wrap gap-2 w-full md:w-auto items-center">
                        
                        <div class="relative">
                            <select :value="currentType" @change="switchType($event.target.value)" 
                                class="appearance-none bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-8 py-2 cursor-pointer font-medium">
                                <option v-for="t in assetTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <Filter class="w-4 h-4" />
                            </div>
                        </div>

                        <button @click="openCreateModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors shadow-sm whitespace-nowrap">
                            <Plus class="w-4 h-4" />
                            <span class="hidden sm:inline">Tambah</span>
                        </button>

                    </div>
                </div>
            </Card>

            <Card no-padding>
                <DataTable :items="filteredParameters" :columns="columns">
                    
                    <template #cell-order="{ item }">
                        <span class="font-mono text-gray-400 text-xs">#{{ item.order_index }}</span>
                    </template>

                    <template #cell-label="{ item }">
                        <div class="py-1">
                            <div class="font-bold text-gray-800 text-sm mb-1">{{ item.label }}</div>
                            <div v-if="item.options && item.options.length" class="flex flex-wrap gap-1">
                                <span v-for="opt in item.options" :key="opt" class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded border border-gray-200">
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
                        <div v-if="item.standard_value" class="flex items-center gap-1.5 text-xs font-medium text-gray-700">
                            <CheckCircle2 class="w-3.5 h-3.5 text-green-600" />
                            <span>
                                {{ item.standard_value === 'true' ? 'Ya / Ada' : (item.standard_value === 'false' ? 'Tidak / Hilang' : item.standard_value) }}
                            </span>
                        </div>
                        <span v-else class="text-xs text-gray-400 italic">Tidak ada standar</span>
                    </template>

                    <template #cell-actions="{ item }">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="openEditModal(item)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="deleteParam(item.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </template>

                </DataTable>
            </Card>

        </div>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-3 flex items-center gap-2">
                    <component :is="isEditing ? Pencil : Plus" class="w-5 h-5 text-indigo-600" />
                    {{ isEditing ? 'Edit Parameter' : 'Tambah Parameter Baru' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    
                    <div class="grid grid-cols-4 gap-4">
                        <div class="col-span-3">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Pertanyaan / Label</label>
                            <input 
                                ref="labelInput" 
                                v-model="form.label" 
                                type="text" 
                                required 
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                                placeholder="Contoh: Tekanan Tabung"
                            >
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Urutan</label>
                            <input v-model="form.order_index" type="number" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Tipe Input</label>
                        <div class="relative">
                            <select v-model="form.input_type" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm appearance-none">
                                <option value="boolean">Ya / Tidak (Switch)</option>
                                <option value="radio">Pilihan Ganda (Radio Button)</option>
                                <option value="select">Dropdown Menu</option>
                                <option value="text">Teks Singkat</option>
                                <option value="number">Angka</option>
                                <option value="textarea">Catatan Panjang</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <List class="w-4 h-4" />
                            </div>
                        </div>
                    </div>

                    <div v-if="showOptionsInput" class="bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Opsi Jawaban</label>
                        
                        <div class="flex gap-2 mb-3">
                            <input v-model="tempOption" @keyup.enter.prevent="addOption" type="text" placeholder="Ketik opsi lalu Enter..." class="flex-1 rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <button type="button" @click="addOption" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-3 py-2 rounded-md text-sm font-bold shadow-sm">
                                <Plus class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span v-for="(opt, idx) in form.options" :key="idx" class="bg-white border px-3 py-1 rounded-full text-xs font-medium flex items-center gap-2 shadow-sm text-gray-700">
                                {{ opt }}
                                <button type="button" @click="removeOption(idx)" class="text-red-400 hover:text-red-600 hover:bg-red-50 rounded-full p-0.5">
                                    <Trash2 class="w-3 h-3" />
                                </button>
                            </span>
                        </div>
                        <p v-if="form.options.length === 0" class="text-[10px] text-red-500 mt-2">* Minimal masukkan 1 opsi jawaban.</p>
                    </div>

                    <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                        <label class="block text-sm font-bold text-indigo-900 mb-1">
                            Kunci Jawaban (Normal) <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-indigo-600 mb-3 opacity-80">Jika jawaban petugas beda dengan ini, status jadi <span class="font-bold text-red-600">ISSUE</span>.</p>

                        <div v-if="form.input_type === 'boolean'" class="flex gap-4">
                            <label class="flex items-center gap-2 text-sm bg-white px-3 py-2 rounded border border-indigo-200 cursor-pointer hover:border-indigo-400 transition-colors">
                                <input type="radio" v-model="form.standard_value" value="true" required class="text-indigo-600 focus:ring-indigo-500"> 
                                <span class="font-medium text-gray-700">Ya / Ada</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm bg-white px-3 py-2 rounded border border-indigo-200 cursor-pointer hover:border-indigo-400 transition-colors">
                                <input type="radio" v-model="form.standard_value" value="false" required class="text-indigo-600 focus:ring-indigo-500"> 
                                <span class="font-medium text-gray-700">Tidak / Hilang</span>
                            </label>
                        </div>

                        <select v-else-if="showOptionsInput" v-model="form.standard_value" required class="w-full rounded-lg border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">-- Pilih Standar Nilai --</option>
                            <option v-for="opt in form.options" :key="opt" :value="opt">{{ opt }}</option>
                        </select>

                        <input v-else v-model="form.standard_value" type="text" required class="w-full rounded-lg border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Nilai yang diharapkan...">
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-sm transition-colors disabled:opacity-50" :disabled="form.processing">
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </MainLayout>
</template>