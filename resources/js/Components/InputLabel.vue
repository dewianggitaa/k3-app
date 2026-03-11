<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-sm font-medium text-ink dark:text-ink-dark/90">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
