<script setup>
import { useTheme } from '@/Composables/useTheme';
import { Sun, Moon } from 'lucide-vue-next';

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button 
        @click="toggleTheme" 
        class="p-2 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-primary/50"
        :class="[ isDark ? 'bg-surface-dark text-yellow-400 hover:bg-gray-700' : 'bg-white text-gray-500 hover:bg-gray-100 shadow-sm' ]"
        title="Ganti Tema"
    >
        <Sun v-if="isDark" class="w-5 h-5" />
        
        <Moon v-else class="w-5 h-5 text-primary" />
    </button>
</template>