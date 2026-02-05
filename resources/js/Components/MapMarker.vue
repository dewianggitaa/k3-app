<script setup>
import { computed } from 'vue';

const props = defineProps({
    x: [Number, String],
    y: [Number, String],
    type: { type: String, default: 'p3k' },
    status: { type: String, default: 'safe' },
    size: { type: Number, default: 4 },
    ratio: { type: Number, default: 1 }
});

const color = computed(() => {
    const colors = {
        safe: '#10b981',
        warning: '#f59e0b',
        critical: '#ef4444'
    };
    return colors[props.status] || '#6366f1';
});

const iconUrl = computed(() => {
    const icons = {
        p3k: '/icon-p3k.png',
        apar: '/icon-apar.png',
        hydrant: '/icon-hydrant.png'
    };
    // Jika props.type p3k atau P3K, tetap arahkan ke file yang benar
    const typeKey = String(props.type).toLowerCase();
    return icons[typeKey] || '/icon-p3k.png';
});
</script>

<template>
    <g :transform="`translate(${x}, ${y})`">
        <g :transform="`scale(${ratio * (size * 0.08)}, ${size * 0.08})`">
            <path 
                d="M0 -24C-4.42 -24 -8 -20.42 -8 -16C-8 -10.5 0 0 0 0C0 0 8 -10.5 8 -16C8 -20.42 4.42 -24 0 -24Z" 
                :fill="color"
                stroke="white"
                stroke-width="1.5"
            />
            <circle cx="0" cy="-16" r="5" fill="white" />
            <image 
                :href="iconUrl"
                x="-3.5" y="-19.5" 
                width="7" height="7"
                preserveAspectRatio="xMidYMid meet"
            />
        </g>
    </g>
</template>