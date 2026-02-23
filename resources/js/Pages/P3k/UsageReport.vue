<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Trash2, Plus, Save, User } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({ 
    box: Object, 
    medicines: Array,
    departments: Array,
    mode: String // Menerima 'in' (penambahan) atau 'out' (pemakaian)
});

// Ambil data user yang sedang login
const user = usePage().props.auth?.user;

const form = useForm({
    type: props.mode, 
    reporter_name: '', 
    department_id: '',
    notes: '', 
    items: [
        { id: '', qty: 1 }
    ]
});

const addItem = () => {
    form.items.push({ id: '', qty: 1 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    if(form.items.some(i => !i.id)) {
        Swal.fire('Error', 'Pilih nama barang terlebih dahulu.', 'error');
        return;
    }

    // Tentukan route tujuan berdasarkan mode
    const postRoute = props.mode === 'out' 
        ? route('p3k.store-usage', props.box.id) 
        : route('p3k.store-restock', props.box.id);

    form.post(postRoute, {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data berhasil disimpan.',
                timer: 1500,
                showConfirmButton: false
            });
        },
        onError: () => {
             Swal.fire('Error', 'Gagal menyimpan data. Pastikan form diisi dengan benar.', 'error');
        }
    });
};
</script>

<template>
    <Head :title="mode === 'out' ? 'Lapor Pemakaian' : 'Penambahan Stok'" />

    <div class="min-h-screen bg-gray-50 flex flex-col">
        
        <div class="bg-white px-4 py-3 shadow-sm flex items-center gap-3 sticky top-0 z-10">
            <Link :href="route('p3k.menu', box.id)" class="p-2 rounded-full hover:bg-gray-100 text-gray-600">
                <ArrowLeft class="w-6 h-6" />
            </Link>
            <div>
                <h1 class="font-bold text-gray-800 leading-tight">
                    {{ mode === 'out' ? 'Lapor Pemakaian' : 'Penambahan Stok' }}
                </h1>
                <p class="text-xs text-gray-500">{{ box.code }}</p>
            </div>
        </div>

        <div class="flex-1 p-4 max-w-lg mx-auto w-full pb-24">
            
            <div class="mb-6 p-3 rounded-xl text-center font-bold border"
                :class="mode === 'out' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-blue-50 text-blue-600 border-blue-200'">
                {{ mode === 'out' ? 'Mode: Pencatatan Pemakaian' : 'Mode: Restock K3' }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-xs font-bold text-gray-500 uppercase mb-3 flex items-center gap-2">
                        <User class="w-4 h-4" /> Data Pelapor
                    </h3>

                    <div v-if="mode === 'out'" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap *</label>
                            <input type="text" v-model="form.reporter_name" required
                                class="w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500"
                                placeholder="Masukkan nama Anda...">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Departemen *</label>
                            <select v-model="form.department_id" required
                                class="w-full rounded-lg border-gray-300 focus:ring-red-500 focus:border-red-500 bg-white">
                                <option value="" disabled>-- Pilih --</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-else class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg">
                            {{ user?.name?.charAt(0) || 'K' }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ user?.name || 'Admin K3' }}</p>
                            <p class="text-xs text-blue-600 font-medium">Tim K3 (Terverifikasi)</p>
                        </div>
                    </div>

                </div>

                <div v-for="(item, index) in form.items" :key="index" 
                    class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 relative">
                    
                    <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" 
                        class="absolute top-3 right-3 text-gray-300 hover:text-red-500 p-1">
                        <Trash2 class="w-4 h-4" />
                    </button>

                    <div class="pr-8">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Nama Barang *</label>
                        <select v-model="item.id" required
                            class="w-full rounded-lg border-gray-200 bg-gray-50 focus:bg-white"
                            :class="mode === 'out' ? 'focus:ring-red-500' : 'focus:ring-blue-500'">
                            <option value="" disabled>-- Pilih Item --</option>
                            <option v-for="med in medicines" :key="med.id" :value="med.id">
                                {{ med.name }}
                            </option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">
                            Jumlah {{ mode === 'out' ? 'Keluar' : 'Masuk' }} *
                        </label>
                        <input type="number" v-model="item.qty" min="1" required
                            class="w-full rounded-lg border-gray-200 font-bold text-lg"
                            :class="mode === 'out' ? 'text-red-600 focus:ring-red-500' : 'text-blue-600 focus:ring-blue-500'">
                    </div>
                </div>

                <button type="button" @click="addItem" 
                    class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-gray-500 font-bold hover:bg-white transition flex items-center justify-center gap-2">
                    <Plus class="w-5 h-5" /> Tambah Item Lain
                </button>

                <div class="mt-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Catatan (Opsional)</label>
                    <textarea v-model="form.notes" rows="2" 
                        class="w-full rounded-xl border-gray-200"
                        :class="mode === 'out' ? 'focus:ring-red-500 focus:border-red-500' : 'focus:ring-blue-500 focus:border-blue-500'"
                        placeholder="Contoh: Terkena pisau / Restock bulan Februari..."></textarea>
                </div>

            </form>
        </div>

        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100 z-20">
            <div class="max-w-lg mx-auto">
                <button @click="submit" :disabled="form.processing"
                    class="w-full py-4 rounded-xl text-white font-bold text-lg shadow-lg flex items-center justify-center gap-2 transition"
                    :class="mode === 'out' ? 'bg-red-600 hover:bg-red-700 shadow-red-200' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-200'">
                    <Save class="w-5 h-5" />
                    {{ form.processing ? 'Menyimpan...' : (mode === 'out' ? 'Simpan Laporan' : 'Simpan Restock') }}
                </button>
            </div>
        </div>

    </div>
</template>