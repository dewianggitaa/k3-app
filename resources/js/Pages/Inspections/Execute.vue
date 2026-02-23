<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { 
    ChevronLeft, ChevronRight, CheckCircle, 
    AlertTriangle, Camera, X 
} from 'lucide-vue-next';

// --- PROPS ---
const props = defineProps({
    inspection: Object,
    asset: Object,
    parameters: Array,
    existingAnswers: Object
});

// --- FORM STATE ---
const form = useForm({
    answers_map: {},   // { param_id: 'ok' / 'not_ok' / '5' }
    notes_map: {},     // { param_id: 'catatan...' }
    photo_map: {},     // { param_id: File } (Untuk masa depan)
    quantities_map: {}, 
});

// --- NAVIGATION STATE ---
const currentStep = ref(0);
const direction = ref('forward'); 

const totalSteps = computed(() => props.parameters ? props.parameters.length : 0);
const progressPercent = computed(() => totalSteps.value > 0 ? ((currentStep.value + 1) / totalSteps.value) * 100 : 0);
const isLastStep = computed(() => currentStep.value === totalSteps.value - 1);

// FIXED: Tambahkan Optional Chaining (?.) untuk mencegah error jika parameters kosong
const currentParam = computed(() => {
    if (!props.parameters || props.parameters.length === 0) return null;
    return props.parameters[currentStep.value];
});

// --- INIT DATA ---
onMounted(() => {
    if (!props.parameters || props.parameters.length === 0) {
        Swal.fire('Error', 'Tidak ada parameter checklist untuk aset ini.', 'error');
        return;
    }

    // 1. Load Jawaban Lama (Draft/Revisi)
    if (props.existingAnswers) {
        Object.values(props.existingAnswers).forEach(ans => {
            if (ans && ans.checklist_parameter_id) {
                form.answers_map[ans.checklist_parameter_id] = ans.response;
                form.notes_map[ans.checklist_parameter_id] = ans.notes;
            }
        });
    }

    // 2. Default Value untuk Angka (P3K)
    props.parameters.forEach(param => {
        if (param.input_type === 'number') {
            if (form.answers_map[param.id] === undefined) {
                form.answers_map[param.id] = param.standard_qty || 0;
            }
        }
    });
});

// --- ACTIONS ---

const nextStep = async () => {
    // FIXED: Cek keberadaan currentParam sebelum akses .id
    if (!currentParam.value) return;

    if (currentStep.value < totalSteps.value - 1) {
        // Validasi Sederhana: Harus diisi (kecuali notes)
        // Cek apakah ada di answers_map DAN nilainya tidak null/undefined/string kosong
        const answer = form.answers_map[currentParam.value.id];
        
        if (answer === undefined || answer === null || answer === '') {
            Swal.fire({
                toast: true,
                position: 'top',
                icon: 'warning',
                title: 'Isi jawaban terlebih dahulu!',
                showConfirmButton: false,
                timer: 1500
            });
            return;
        }

        direction.value = 'forward';
        currentStep.value++;
        await nextTick();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const prevStep = async () => {
    if (currentStep.value > 0) {
        direction.value = 'backward';
        currentStep.value--;
        await nextTick();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const setAnswer = (val) => {
    if (!currentParam.value) return;
    
    form.answers_map[currentParam.value.id] = val;
    
    if (currentParam.value.input_type === 'radio') {
        setTimeout(() => nextStep(), 300);
    }
};

const adjustNumber = (delta) => {
    if (!currentParam.value) return;

    const current = parseInt(form.answers_map[currentParam.value.id] || 0);
    const newVal = Math.max(0, current + delta);
    form.answers_map[currentParam.value.id] = newVal;
};

const submit = () => {
    console.log("🚀 Tombol Submit Ditekan!"); 

    form.transform((data) => {
        let payload = {
            answers: {},
            quantities: []
        };

        if (props.parameters) {
            props.parameters.forEach(param => {
                const userResponse = data.answers_map[param.id];
                const userNote = data.notes_map[param.id];

                payload.answers[param.id] = {
                    response: userResponse,
                    notes: userNote
                };

                if (param.input_type === 'number') {
                    payload.quantities.push({
                        item_id: param.related_item_id || param.id,
                        current_qty: parseInt(userResponse || 0)
                    });
                }
            });
        }

        return payload;

    }).put(route('inspections.update-execution', props.inspection.id), {
        onBefore: () => {
            console.log("⏳ Mengirim request ke server...");
        },
        onSuccess: (page) => {
            console.log("✅ Server response: SUKSES", page);
            Swal.fire({
                title: 'Selesai!',
                text: 'Laporan berhasil dikirim.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            console.error("❌ Server response: ERROR", errors);
            Swal.fire('Gagal', 'Ada kesalahan pengisian. Cek Console.', 'error');
        }
    });
};
</script>

<template>
    <Head title="Pengerjaan Inspeksi" />

    <div class="min-h-screen bg-gray-50 flex flex-col relative overflow-hidden max-w-md mx-auto shadow-2xl">
        
        <div class="bg-white px-6 py-4 shadow-sm z-10">
            <div class="flex justify-between items-center mb-2">
                <Link :href="route('inspections.my-tasks')" class="text-gray-400 hover:text-gray-600">
                    <X class="w-6 h-6" />
                </Link>
                <div class="text-xs font-bold uppercase tracking-widest text-gray-500">
                    {{ asset?.code || 'ASSET' }}
                </div>
                <div class="w-6"></div> 
            </div>
            
            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div 
                    class="bg-indigo-600 h-full transition-all duration-500 ease-out"
                    :style="{ width: progressPercent + '%' }"
                ></div>
            </div>
            <div class="flex justify-between mt-1 text-xs text-gray-400 font-medium">
                <span>Soal {{ currentStep + 1 }}</span>
                <span>{{ totalSteps }} Total</span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-8 pb-32">
            
            <transition v-if="currentParam" :name="direction === 'forward' ? 'slide-left' : 'slide-right'" mode="out-in">
                
                <div :key="currentParam.id" class="w-full">
                    
                    <h2 class="text-2xl font-bold text-gray-900 leading-tight mb-2">
                        {{ currentParam.label }}
                    </h2>
                    
                    <p v-if="currentParam.input_type === 'number'" class="text-sm text-gray-500 mb-8">
                        Pastikan jumlah fisik sesuai dengan standar.
                    </p>
                    <p v-else class="text-sm text-gray-500 mb-8">
                        Periksa kondisi fisik komponen ini.
                    </p>


                    <div v-if="currentParam.input_type === 'radio'" class="space-y-4">
                        <template v-for="(option, idx) in (typeof currentParam.options === 'string' ? JSON.parse(currentParam.options) : currentParam.options)" :key="idx">
                            
                            <button 
                                @click="setAnswer(option)"
                                class="w-full p-5 rounded-2xl border-2 text-left transition-all duration-200 flex items-center justify-between group active:scale-[0.98]"
                                :class="form.answers_map[currentParam.id] === option 
                                    ? 'border-indigo-600 bg-indigo-50 shadow-md' 
                                    : 'border-gray-200 bg-white hover:border-indigo-300'"
                            >
                                <span class="text-lg font-medium capitalize"
                                    :class="form.answers_map[currentParam.id] === option ? 'text-indigo-700' : 'text-gray-700'"
                                >
                                    {{ option }}
                                </span>

                                <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors"
                                    :class="form.answers_map[currentParam.id] === option 
                                        ? 'border-indigo-600 bg-indigo-600' 
                                        : 'border-gray-300'"
                                >
                                    <CheckCircle v-if="form.answers_map[currentParam.id] === option" class="w-4 h-4 text-white" />
                                </div>
                            </button>

                        </template>
                    </div>


                    <div v-else-if="currentParam.input_type === 'number'" class="flex flex-col items-center">
                        
                        <div class="mb-8 flex flex-col items-center">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Standar Minimal</span>
                            <div class="text-4xl font-black text-gray-300">
                                {{ currentParam.standard_qty }}
                            </div>
                        </div>

                        <div class="flex items-center gap-6 mb-8">
                            <button 
                                @click="adjustNumber(-1)"
                                class="w-16 h-16 rounded-full bg-white border-2 border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-600 flex items-center justify-center text-4xl font-light shadow-sm active:scale-90 transition"
                            >
                                -
                            </button>

                            <div class="flex flex-col items-center w-24">
                                <span class="text-6xl font-black text-gray-800 tabular-nums">
                                    {{ form.answers_map[currentParam.id] }}
                                </span>
                                <span class="text-xs font-bold text-gray-400 uppercase mt-1">Stok Fisik</span>
                            </div>

                            <button 
                                @click="adjustNumber(1)"
                                class="w-16 h-16 rounded-full bg-indigo-600 text-white flex items-center justify-center text-4xl font-light shadow-lg shadow-indigo-200 active:scale-90 transition hover:bg-indigo-700"
                            >
                                +
                            </button>
                        </div>

                        <div v-if="parseInt(form.answers_map[currentParam.id]) < currentParam.standard_qty" 
                             class="flex items-center gap-2 text-orange-600 bg-orange-50 px-4 py-3 rounded-lg border border-orange-100 animate-pulse">
                            <AlertTriangle class="w-5 h-5" />
                            <span class="font-bold text-sm">Perlu Restock!</span>
                        </div>
                    </div>


                    <div class="mt-8 pt-8 border-t border-dashed border-gray-200">
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                            Catatan / Temuan (Opsional)
                        </label>
                        <textarea 
                            v-model="form.notes_map[currentParam.id]" 
                            rows="3" 
                            class="w-full bg-gray-50 border-0 rounded-xl p-4 text-gray-700 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition resize-none text-sm"
                            placeholder="Tuliskan jika ada kerusakan atau keterangan tambahan..."
                        ></textarea>
                    </div>

                </div>
            </transition>
            
            <div v-else class="text-center py-20 text-gray-400">
                <AlertTriangle class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                <p>Data checklist tidak ditemukan.</p>
            </div>

        </div>

        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 max-w-md mx-auto z-20 flex gap-3">
            
            <button 
                v-if="currentStep > 0"
                @click="prevStep" 
                class="px-4 py-3 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center"
            >
                <ChevronLeft class="w-6 h-6" />
            </button>
            <div v-else class="w-[58px]"></div> 

            <button 
                v-if="!isLastStep"
                @click="nextStep" 
                class="flex-1 bg-gray-900 text-white rounded-xl font-bold text-lg shadow-xl shadow-gray-200 hover:bg-gray-800 active:scale-[0.98] transition py-3 flex items-center justify-center gap-2"
            >
                Lanjut
                <ChevronRight class="w-5 h-5 opacity-70" />
            </button>

            <button 
                v-else
                @click="submit" 
                :disabled="form.processing"
                class="flex-1 bg-indigo-600 text-white rounded-xl font-bold text-lg shadow-xl shadow-indigo-200 hover:bg-indigo-700 active:scale-[0.98] transition py-3 flex items-center justify-center gap-2"
            >
                <span v-if="form.processing" class="animate-spin">⏳</span>
                <span v-else>Kirim Laporan</span>
            </button>

        </div>

    </div>
</template>

<style scoped>
/* Slide Animation */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  transition: all 0.3s ease-out;
}

.slide-left-enter-from {
  opacity: 0;
  transform: translateX(20px);
}
.slide-left-leave-to {
  opacity: 0;
  transform: translateX(-20px);
}

.slide-right-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}
.slide-right-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>