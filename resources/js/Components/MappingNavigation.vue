<script setup>
import NavLink from '@/Components/NavLink.vue';

const props = defineProps({
    floorId: [String, Number]
});
</script>

<template>
    <nav class="flex gap-6 w-full pt-2">
        <NavLink 
            :href="route('floors.mapping', floorId)" 
            :active="route().current('floors.mapping')"
            class="pb-3"
        >
            Area Ruangan
        </NavLink>

        <NavLink 
            :href="route('assets.mapping', floorId)" 
            :active="route().current('assets.mapping')"
            class="pb-3"
        >
            Titik Aset (P3K)
        </NavLink>
    </nav>
</template>