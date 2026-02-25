<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { 
    ChevronLeft, ChevronRight, CheckCircle, 
    AlertTriangle, Camera, X, FileText 
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
    global_notes: '',  // PERBAIKAN: Hanya 1 catatan global
    photo_map: {},     // { param_id: File } (Untuk masa depan)
    quantities_map: {}, 
});

// --- NAVIGATION STATE ---
const currentStep = ref(0);
const direction = ref('forward'); 

// PERBAIKAN: Tambah 1 langkah ekstra di akhir khusus untuk Catatan
const totalSteps = computed(() => props.parameters ? props.parameters.length + 1 : 1);
const progressPercent = computed(() => totalSteps.value > 0 ? ((currentStep.value + 1) / totalSteps.value) * 100 : 0);
const isLastStep = computed(() => currentStep.value === totalSteps.value - 1);
const isNotesStep = computed(() => currentStep.value === totalSteps.value - 1);

const currentParam = computed(() => {
    if (!props.parameters || isNotesStep.value) return null;
    return props.parameters[currentStep.value];
});

// --- INIT DATA ---
onMounted(() => {
    if (!props.parameters || props.parameters.length === 0) {
        Swal.fire('Error', 'Tidak ada parameter checklist untuk aset ini.', 'error');
        return;
    }

    // 1. Load Jawaban Lama & Catatan Global
    form.global_notes = props.inspection?.notes || '';
    
    if (props.existingAnswers) {
        Object.values(props.existingAnswers).forEach(ans => {
            if (ans && ans.checklist_parameter_id) {
                form.answers_map[ans.checklist_parameter_id] = ans.response;
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

const nextStep = async () => {
    if (isNotesStep.value) return; 

    if (currentParam.value) {
        const answer = form.answers_map[currentParam.value.id];
        
        if ((answer === undefined || answer === null || answer === '') && currentParam.value.input_type !== 'date') {
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
    }

    direction.value = 'forward';
    currentStep.value++;
    await nextTick();
    window.scrollTo({ top: 0, behavior: 'smooth' });
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
    form.transform((data) => {
        let payload = {
            answers: {},
            quantities: [],
            notes: data.global_notes // PERBAIKAN: Catatan dikirim sebagai atribut global
        };

        if (props.parameters) {
            props.parameters.forEach(param => {
                const userResponse = data.answers_map[param.id];

                payload.answers[param.id] = {
                    response: userResponse
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
        onSuccess: () => {
            Swal.fire({
                title: 'Selesai!',
                text: 'Laporan berhasil dikirim.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: () => {
            Swal.fire('Gagal', 'Ada kesalahan saat menyimpan data.', 'error');
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
                <span>{{ isNotesStep ? 'Catatan Akhir' : 'Soal ' + (currentStep + 1) }}</span>
                <span>{{ totalSteps }} Total</span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-8 pb-32">
            
            <transition :name="direction === 'forward' ? 'slide-left' : 'slide-right'" mode="out-in">
                
                <div :key="'step-' + currentStep" class="w-full">
                    
                    <template v-if="!isNotesStep && currentParam">
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
                        <div v-else-if="currentParam.input_type === 'date'" class="w-full mt-4">
                            <div class="text-sm font-medium text-amber-600 bg-amber-50 p-3 rounded-xl mb-3 border border-amber-100 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span>* Hanya isi jika melakukan pergantian/pengisian ulang APAR</span>
                            </div>

                            <input 
                                type="date" 
                                v-model="form.answers_map[currentParam.id]"
                                class="w-full p-5 rounded-2xl border-2 border-gray-200 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 transition-all text-xl text-gray-700 font-medium bg-white"
                            >
                        </div>
                    </template>

                    <template v-else-if="isNotesStep">
                        <div class="flex flex-col items-center text-center mb-6 mt-4">
                            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mb-4 border border-indigo-100 shadow-sm">
                                <FileText class="w-8 h-8" />
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 leading-tight mb-2">
                                Catatan Akhir
                            </h2>
                            <p class="text-sm text-gray-500 px-4">
                                Jelaskan kelainan yang asset disini....
                            </p>
                        </div>

                        <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden shadow-sm hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-200">
                            <textarea 
                                v-model="form.global_notes" 
                                rows="5" 
                                class="w-full border-0 p-4 text-gray-700 placeholder-gray-400 focus:ring-0 resize-none"
                                placeholder="Ketik catatan di sini (opsional)..."
                            ></textarea>
                        </div>
                    </template>

                </div>
            </transition>
        </div>

        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 max-w-md mx-auto z-20 flex gap-3">
            
            <button 
                v-if="currentStep > 0"
                @click="prevStep" 
                class="px-4 py-3 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50 transition flex items-center justify-center active:scale-[0.98]"
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