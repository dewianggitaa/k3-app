<script setup>
import NavLink from '@/Components/NavLink.vue';

const props = defineProps({
    floorId: [String, Number]
});
</script>

<template>
    <nav class="flex gap-4 w-full pt-2">
        <NavLink 
            :href="route('floors.mapping', floorId)" 
            :active="route().current('floors.mapping')"
            class="pb-3"
        >
            Mapping Area
        </NavLink>

        <NavLink 
            :href="route('assets.mapping', floorId)" 
            :active="route().current('assets.mapping')"
            class="pb-3"
        >
            Mapping Asset
        </NavLink>
    </nav>
</template>