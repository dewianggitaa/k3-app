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
        ? 'inline-flex items-center px-1 pb-2 border-b-2 border-indigo-500 text-xs text-primary font-semibold text-gray-900 focus:outline-none transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pb-2 border-b-2 border-transparent text-xs font-medium text-ink hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
