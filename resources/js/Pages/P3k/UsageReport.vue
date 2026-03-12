<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Trash2, Plus, Save, User, AlertTriangle } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    box: Object, 
    medicines: Array,
    departments: Array,
    deficient_items: Array,
    mode: String // Menerima 'in' (penambahan) atau 'out' (pemakaian)
});

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

    <div class="min-h-screen bg-ghost flex flex-col">
        
        <div class="bg-surface px-4 py-3 shadow-sm flex items-center gap-3 sticky top-0 z-10">
            <Link :href="route('p3k.menu', box.id)" class="p-2 rounded-full hover:bg-ghost text-ink-light">
                <ArrowLeft class="w-6 h-6" />
            </Link>
            <div>
                <h1 class="font-bold text-ink leading-tight">
                    {{ mode === 'out' ? 'Lapor Pemakaian' : 'Penambahan Stok' }}
                </h1>
                <p class="text-xs text-ink-light">{{ box.code }}</p>
            </div>
        </div>

        <div class="flex-1 p-4 max-w-lg mx-auto w-full pb-24">
            
            <div class="mb-6 p-3 rounded-md text-center font-bold border"
                :class="mode === 'out' ? 'bg-danger/10 text-danger border-danger/30' : 'bg-primary/10 text-primary border-primary'">
                {{ mode === 'out' ? 'Mode: Pencatatan Pemakaian' : 'Mode: Restock K3' }}
            </div>

            <!-- Warning for Deficient Items in Restock Mode -->
            <div v-if="mode === 'in' && deficient_items && deficient_items.length > 0" class="mb-6 bg-warning/10 border border-warning/30 rounded-md p-4">
                <div class="flex items-center gap-2 text-warning mb-2">
                    <AlertTriangle class="w-5 h-5 flex-shrink-0" />
                    <h3 class="font-bold text-sm">Daftar Item Perlu Restock:</h3>
                </div>
                <ul class="space-y-1 pl-7 text-xs text-ink dark:text-ink-dark/90">
                    <li v-for="item in deficient_items" :key="item.name" class="flex justify-between items-center border-b border-warning/20 pb-1 last:border-0 last:pb-0">
                        <span class="font-medium">{{ item.name }}</span>
                        <span class="text-warning font-bold">Kurang {{ item.deficit }}</span>
                    </li>
                </ul>
                <p class="text-[10px] text-ink-light mt-2 italic">* Data diambil dari selisih qty saat ini dan standar alat.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                
                <div class="bg-surface p-4 rounded-md shadow-sm border border-ghost-hover">
                    <h3 class="text-xs font-bold text-ink-light uppercase mb-3 flex items-center gap-2">
                        <User class="w-4 h-4" /> Data Pelapor
                    </h3>

                    <div v-if="mode === 'out'" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-ink dark:text-ink-dark/90 mb-1">Nama Lengkap *</label>
                            <input type="text" v-model="form.reporter_name" required
                                class="w-full rounded-md border-ghost-hover focus:ring-red-500 focus:border-danger/30"
                                placeholder="Masukkan nama Anda...">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-ink dark:text-ink-dark/90 mb-1">Departemen *</label>
                            <select v-model="form.department_id" required
                                class="w-full rounded-md border-ghost-hover focus:ring-red-500 focus:border-danger/30 bg-surface">
                                <option value="" disabled>-- Pilih --</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-else class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                            {{ user?.name?.charAt(0) || 'K' }}
                        </div>
                        <div>
                            <p class="font-bold text-ink">{{ user?.name || 'Admin K3' }}</p>
                            <p class="text-xs text-primary font-medium">Tim K3 (Terverifikasi)</p>
                        </div>
                    </div>

                </div>

                <div v-for="(item, index) in form.items" :key="index" 
                    class="bg-surface p-4 rounded-md shadow-sm border border-ghost-hover relative">
                    
                    <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" 
                        class="absolute top-3 right-3 text-gray-300 hover:text-danger p-1">
                        <Trash2 class="w-4 h-4" />
                    </button>

                    <div class="pr-8">
                        <label class="block text-xs font-bold text-ink-light uppercase mb-1">Nama Barang *</label>
                        <select v-model="item.id" required
                            class="w-full rounded-md border-ghost-hover bg-ghost focus:bg-surface"
                            :class="mode === 'out' ? 'focus:ring-red-500' : 'focus:ring-primary'">
                            <option value="" disabled>-- Pilih Item --</option>
                            <option v-for="med in medicines" :key="med.id" :value="med.id">
                                {{ med.name }}
                            </option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-bold text-ink-light uppercase mb-1">
                            Jumlah {{ mode === 'out' ? 'Keluar' : 'Masuk' }} *
                        </label>
                        <input type="number" v-model="item.qty" min="1" required
                            class="w-full rounded-md border-ghost-hover font-bold text-lg"
                            :class="mode === 'out' ? 'text-danger focus:ring-red-500' : 'text-primary focus:ring-primary'">
                    </div>
                </div>

                <button type="button" @click="addItem" 
                    class="w-full py-3 border-2 border-dashed border-ghost-hover rounded-md text-ink-light font-bold hover:bg-surface transition flex items-center justify-center gap-2">
                    <Plus class="w-5 h-5" /> Tambah Item Lain
                </button>

                <div class="mt-6">
                    <label class="block text-sm font-bold text-ink dark:text-ink-dark/90 mb-2">Catatan (Opsional)</label>
                    <textarea v-model="form.notes" rows="2" 
                        class="w-full rounded-md border-ghost-hover"
                        :class="mode === 'out' ? 'focus:ring-red-500 focus:border-danger/30' : 'focus:ring-primary focus:border-primary'"
                        placeholder="Contoh: Terkena pisau / Restock bulan Februari..."></textarea>
                </div>

            </form>
        </div>

        <div class="fixed bottom-0 left-0 right-0 p-4 bg-surface border-t border-ghost-hover z-20">
            <div class="max-w-lg mx-auto">
                <button @click="submit" :disabled="form.processing"
                    class="w-full py-4 rounded-md text-white font-bold text-lg shadow-lg flex items-center justify-center gap-2 transition"
                    :class="mode === 'out' ? 'bg-danger hover:bg-red-700 shadow-red-200' : 'bg-primary hover:bg-primary-hover shadow-blue-200'">
                    <Save class="w-5 h-5" />
                    {{ form.processing ? 'Menyimpan...' : (mode === 'out' ? 'Simpan Laporan' : 'Simpan Restock') }}
                </button>
            </div>
        </div>

    </div>
</template>