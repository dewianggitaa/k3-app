<script setup>
import { Search, X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: String, // Data v-model dari parent
    placeholder: {
        type: String,
        default: 'Cari data...'
    }
});

const emit = defineEmits(['update:modelValue', 'reset']);

// Fungsi hapus text pas tombol X diklik
const clear = () => {
    emit('update:modelValue', ''); // Kosongkan value
    emit('reset'); // Emit event reset (opsional, siapa tau parent butuh)
};
</script>

<template>
    <div class="relative group">
        <div class="flex items-center bg-ghost dark:bg-gray-800 rounded-md px-3 py-1.5 transition-colors focus-within:ring-1 focus-within:ring-primary/50 focus-within:bg-white dark:focus-within:bg-gray-900 border border-transparent focus-within:border-primary/30">
            
            <Search class="w-3.5 h-3.5 text-gray-400 group-focus-within:text-primary transition-colors shrink-0 mr-2" />
            
            <input 
                type="text" 
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                class="bg-transparent border-none p-0 text-xs w-full text-ink dark:text-ink-dark placeholder-gray-400 focus:ring-0"
            />

            <button 
                v-if="modelValue" 
                @click="clear"
                class="ml-2 text-gray-400 hover:text-danger transition-colors focus:outline-none"
                title="Hapus pencarian"
            >
                <X class="w-3.5 h-3.5" />
            </button>
        </div>
    </div>
</template>