<script setup>
import { Box, CheckCircle2 } from 'lucide-vue-next';

defineProps({
    assets: {
        type: Array,
        required: true
    },
    selectedAsset: {
        type: Object,
        default: null
    }
});

defineEmits(['select']);
</script>

<template>
    <div class="space-y-1">
        <button v-for="asset in assets" :key="asset.id" @click="$emit('select', asset)"
            :class="[
                'w-full text-left p-3 rounded-md border transition-all flex justify-between items-start',
                selectedAsset?.id === asset.id ? 'bg-primary/10 border-primary ring-2 ring-primary/10' : 'bg-surface border-transparent hover:bg-ghost'
            ]"
        >
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <Box class="w-3.5 h-3.5 text-ink-light flex-shrink-0" />
                    <span :class="['font-bold text-sm', selectedAsset?.id === asset.id ? 'text-primary' : 'text-ink dark:text-ink-dark/90']">
                        {{ asset.code }}
                        <span v-if="asset.is_double" class="ml-1.5 text-[9px] bg-primary/10 text-primary px-1.5 py-0.5 rounded border border-primary uppercase">
                            2 Tabung
                        </span>
                    </span>
                </div>
                <p class="text-[11px] text-ink-light">Ruang: {{ asset.room?.name || 'N/A' }}</p>
            </div>
            <div v-if="asset.location_data" class="bg-success/20 text-success p-1 rounded-full flex-shrink-0">
                <CheckCircle2 class="w-3.5 h-3.5" />
            </div>
        </button>
    </div>
</template>
