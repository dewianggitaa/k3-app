<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    LayoutDashboard, 
    Database, 
    LogOut, 
    ChevronRight,
    Building2,
    Layers,
    DoorOpen,
    Box,
    Menu,
    CalendarClock,
    ClipboardCheck, 
} from 'lucide-vue-next';

import ThemeToggle from '@/Components/ThemeToggle.vue'; 

const page = usePage();
const user = computed(() => page.props.auth.user);
const name = computed(() => user.value?.name?.split(' ')[0]);

const menuItems = [
    { 
        name: 'Dashboard', 
        icon: LayoutDashboard, 
        route: 'dashboard',
        active: 'dashboard'
    },
    { 
        name: 'Master Data', 
        icon: Database, 
        route: null,
        active: 'master.*', 
        children: [
            { name: 'Data Gedung', route: 'buildings.index', icon: Building2 }, 
            { name: 'Data Lantai', route: 'floors.index', icon: Layers }, 
            { name: 'Data Ruangan/Area', route: 'rooms.index', icon: DoorOpen },
            { name: 'Data Asset', route: 'apars.index', icon: Box },
        ]
    },
    {
        name: 'Manajemen Jadwal',
        icon: CalendarClock,
        route: 'schedules.index',
    },
    { 
        name: 'Logout', 
        icon: LogOut, 
        route: 'logout',
        method: 'post' 
    },
];

const isHovered = ref(false);
const expandedMenu = ref(null);

const isActive = (routeName) => route().current(routeName);
const isParentActive = (pattern) => route().current(pattern);

const toggleSubmenu = (menuName) => {
    if (!isHovered.value) isHovered.value = true;
    expandedMenu.value = expandedMenu.value === menuName ? null : menuName;
};
</script>

<template>
    <div class="min-h-screen flex bg-page dark:bg-page-dark transition-colors duration-300 font-sans">
        
        <aside 
            @mouseenter="isHovered=true"
            @mouseleave="isHovered=false; expandedMenu=null"
            class="fixed top-0 left-0 h-screen bg-surface dark:bg-surface-dark border-r border-ghost dark:border-gray-700 z-50 transition-all duration-300 ease-in-out flex flex-col overflow-hidden shadow-sm"
            :class="[ isHovered ? 'w-60 shadow-2xl' : 'w-14' ]" 
        >
            
            <div class="h-12 flex items-center justify-center flex-shrink-0 border-b border-ghost dark:border-gray-700 bg-surface dark:bg-surface-dark z-10">
                <div class="flex items-center justify-center w-8 h-8 transform transition-transform duration-300"
                     :class="[ isHovered ? 'scale-100' : 'scale-100' ]">
                    <span class="font-black text-xl text-primary dark:text-primary-dark tracking-tighter">
                        K3
                    </span>
                </div>
                
                <div class="overflow-hidden transition-all duration-300 origin-left" 
                     :class="[ isHovered ? 'w-auto opacity-100 ml-2' : 'w-0 opacity-0' ]">
                    <span class="font-bold text-xs text-ink dark:text-ink-dark tracking-tight uppercase">Maintenance</span>
                </div>
            </div>

            <div class="flex-1 py-2 overflow-y-auto custom-scrollbar px-1.5 space-y-0.5">
                <div v-for="(item, index) in menuItems" :key="index">
                    
                    <Link 
                        v-if="!item.children"
                        :href="route(item.route)"
                        :method="item.method || 'get'" 
                        as="button"
                        class="w-full flex items-center px-2 py-2 rounded-md group transition-all duration-200 relative"
                        :class="[ 
                            isActive(item.route) 
                            ? 'bg-ghost dark:bg-ghost-dark text-primary dark:text-primary-dark font-bold' 
                            : 'text-ink/70 dark:text-ink-dark/70 hover:text-primary dark:hover:text-primary-dark hover:bg-ghost dark:hover:bg-ghost-dark' 
                        ]"
                    >
                        <div class="w-5 h-5 flex items-center justify-center flex-shrink-0">
                            <component :is="item.icon" :stroke-width="isActive(item.route) ? 2.5 : 2" class="w-4 h-4 transition-transform group-hover:scale-110" />
                        </div>
                        
                        <span class="ml-2 text-xs whitespace-nowrap transition-all duration-300 origin-left"
                            :class="[ isHovered ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4 w-0 overflow-hidden' ]">
                            {{ item.name }}
                        </span>

                        <div v-if="!isHovered" class="absolute left-10 bg-gray-900 text-white text-[10px] px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap shadow-lg translate-x-1">
                            {{ item.name }}
                        </div>
                    </Link>

                    <div v-else>
                        <button 
                            @click="toggleSubmenu(item.name)"
                            class="w-full flex items-center px-2 py-1.5 rounded-md group transition-all duration-200 relative justify-between"
                            :class="[ 
                                expandedMenu === item.name || isParentActive(item.active)
                                ? 'bg-ghost dark:bg-ghost-dark text-primary dark:text-primary-dark font-bold' 
                                : 'text-ink/70 dark:text-ink-dark/70 hover:text-primary dark:hover:text-primary-dark hover:bg-ghost dark:hover:bg-ghost-dark'
                            ]"
                        >
                            <div class="flex items-center">
                                <div class="w-5 h-5 flex items-center justify-center flex-shrink-0">
                                    <component :is="item.icon" :stroke-width="2" class="w-4 h-4 transition-transform group-hover:scale-110" />
                                </div>
                                <span class="ml-2 text-xs whitespace-nowrap transition-all duration-300 origin-left"
                                    :class="[ isHovered ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4 w-0 overflow-hidden' ]">
                                    {{ item.name }}
                                </span>
                            </div>
                            <ChevronRight 
                                v-if="isHovered"
                                class="w-3 h-3 transition-transform duration-200"
                                :class="{ 'rotate-90': expandedMenu === item.name }"
                            />
                        </button>

                        <div 
                            v-show="(expandedMenu === item.name || isParentActive(item.active)) && isHovered" 
                            class="ml-2 mt-0.5 space-y-0.5 border-l border-ghost dark:border-gray-700 pl-2 overflow-hidden"
                        >
                            <Link 
                                v-for="(child, cIndex) in item.children" 
                                :key="cIndex"
                                :href="route(child.route)"
                                class="flex items-center px-2 py-1.5 rounded-md text-xs transition-colors duration-200"
                                :class="[
                                    isActive(child.route)
                                    ? 'text-primary dark:text-primary-dark bg-ghost dark:bg-ghost-dark font-bold'
                                    : 'text-ink/60 dark:text-ink-dark/60 hover:text-primary dark:hover:text-primary-dark hover:bg-ghost dark:hover:bg-ghost-dark'
                                ]"
                            >   
                                <component :is="child.icon" class="w-3 h-3 mr-2 opacity-70"/>
                                {{ child.name }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-12 flex items-center justify-center border-t border-ghost dark:border-gray-700 bg-page/50 dark:bg-gray-800/50">
                <div class="flex items-center px-2">
                    <div class="w-6 h-6 rounded-md bg-gradient-to-tr from-primary to-purple-500 flex items-center justify-center text-white font-bold text-[9px] shadow-sm flex-shrink-0">
                        {{ name ? name[0] : 'U' }}
                    </div>
                    <div class="overflow-hidden transition-all duration-300 ml-2" :class="[ isHovered ? 'opacity-100 w-auto' : 'opacity-0 w-0' ]">
                        <p class="text-[11px] font-bold text-ink dark:text-ink-dark whitespace-nowrap truncate w-24">{{ user?.name }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-shrink-0 transition-all duration-300 hidden md:block" 
             :class="[ isHovered ? 'w-60' : 'w-14' ]">
        </div>

        <main class="flex-1 min-w-0 overflow-x-hidden">
            <header class="min-h-[3rem] bg-surface dark:bg-surface-dark border-b border-ghost dark:border-gray-700 flex flex-col sticky top-0 z-0 transition-colors duration-300">
    
                <div class="flex justify-between items-center px-6 py-2">
                    <div class="text-ink dark:text-ink-dark tracking-wide flex-1">
                        <slot name="header-title" /> 
                    </div>

                    <div class="flex items-center gap-4">
                        <ThemeToggle />
                    </div>
                </div>

                <div class="w-full">
                    <slot name="header-nav" />
                </div>
            </header>

            <div class="p-6">
                <slot/>
            </div>
        </main>

    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 2px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4b5563; }
</style>