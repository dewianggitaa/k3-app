<script setup>
import { CheckCircle2, X } from 'lucide-vue-next';

defineProps({
    rooms: {
        type: Array,
        required: true
    },
    selectedRoom: {
        type: Object,
        default: null
    }
});

defineEmits(['select']);
</script>

<template>
    <div class="space-y-2">
        <button 
            v-for="room in rooms" :key="room.id" @click="$emit('select', room)"
            :class="['w-full text-left px-4 py-3 rounded-md text-sm border flex justify-between items-center transition-all shadow-sm',
                      selectedRoom?.id === room.id ? 'bg-primary/10 border-primary text-primary ring-2 ring-primary/10' : 'bg-surface border-ghost-hover text-ink-light hover:border-primary']"
        >
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-primary flex-shrink-0"></div>
                <span class="font-medium truncate">{{ room.name }}</span>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <CheckCircle2 v-if="room.coordinates?.length > 0" class="w-4 h-4 text-success" />
                <X v-if="selectedRoom?.id === room.id" class="w-4 h-4 text-primary-dark" />
            </div>
        </button>
    </div>
</template>
