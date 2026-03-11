<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center px-1 pb-2 border-b-2 border-primary text-[12px] font-bold text-primary dark:text-primary-dark focus:outline-none transition-all duration-200 ease-in-out'
        : 'inline-flex items-center px-1 pb-2 border-b-2 border-transparent text-[12px] font-medium text-ink-light dark:text-ink-dark/60 hover:text-primary dark:hover:text-primary-dark hover:border-ghost-hover dark:hover:border-ghost-dark focus:outline-none transition-all duration-200 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
