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
    Menu,        // Icon Hamburger untuk mobile
    X,           // Icon Close untuk mobile
    CalendarClock,
    ClipboardCheck, 
    ListCheck,
    UserCheck,
    FileBarChart,
    Users,
    ClipboardList,
    FileStack,
    FileText
} from 'lucide-vue-next';

import ThemeToggle from '@/Components/ThemeToggle.vue'; 

const page = usePage();
const user = computed(() => page.props.auth.user);
const name = computed(() => user.value?.name?.split(' ')[0]);

const can = (permission) => {
    return page.props.auth.permissions?.includes(permission) ?? false;
};

const canAny = (...permissions) => {
    return permissions.some(p => can(p));
};

const allMenuItems = [
    { 
        name: 'Dashboard', 
        icon: LayoutDashboard, 
        route: 'dashboard',
        active: 'dashboard',
        permission: null,
    },
    { 
        name: 'Master Data', 
        icon: Database, 
        route: null,
        active: 'master.*', 
        permission: 'view-master-data|manage-buildings|manage-floors|manage-rooms|manage-assets|manage-checklist-parameters|manage-report-forms',
        children: [
            { name: 'Data Gedung',        route: 'buildings.index', icon: Building2 }, 
            { name: 'Data Lantai',        route: 'floors.index',    icon: Layers }, 
            { name: 'Data Ruangan/Area',  route: 'rooms.index',     icon: DoorOpen },
            { name: 'Data Asset',         route: 'apars.index',     icon: Box },
            { name: 'Checklist Parameter', route: 'checklist-parameters.index', icon: ListCheck },
            { name: 'Format Laporan',     route: 'report-forms.index', icon: FileText },
        ]
    },
    {
        name: 'Manajemen Jadwal',
        icon: CalendarClock,
        route: 'schedules.index',
        permission: 'view-schedules',
    },
    {
        name: 'Monitoring Tugas',
        icon: ClipboardCheck,
        route: 'inspections.index',
        permission: 'manage-inspections',
    },
    {
        name: 'Tugas K3',
        icon: UserCheck,
        route: 'inspections.open',
        permission: 'execute-inspections',
        show: () => user.value?.department?.name === 'K3' || user.value?.roles?.some(r => r.name === 'executor_k3'),
    },
    {
        name: 'Tugas PIC',
        icon: UserCheck,
        route: 'inspections.my-tasks',
        permission: 'execute-inspections',
        show: () => user.value?.department?.name !== 'K3' && !user.value?.roles?.some(r => r.name === 'executor_k3'),
    },

    {
        name: 'Riwayat Laporan',
        icon: FileStack,
        route: 'reports.index',
        permission: 'view-reports',
    },
    {
        name: 'Status Laporan',
        icon: FileBarChart,
        route: 'reports.pic',
        permission: 'view-pic-reports',
    },
    {
        name: 'Kelola Pengguna',
        icon: Users,
        route: 'users.index',
        permission: 'view-users',
    },
    {
        name: 'Audit Trail',
        icon: ClipboardList,
        route: 'audit-trail.index',
        permission: 'manage-roles',
    },
    { 
        name: 'Logout', 
        icon: LogOut, 
        route: 'logout',
        method: 'post',
        permission: null, // selalu tampil
    },
];

const menuItems = computed(() => {
    return allMenuItems.filter(item => {
        // First check custom visibility logic if defined
        if (item.show !== undefined && !item.show()) return false;

        // Then check permissions
        if (!item.permission) return true; // null = selalu tampil

        const perms = item.permission.split('|');
        return perms.some(p => can(p));
    });
});

const isHovered = ref(false);
const expandedMenu = ref(null);
const isMobileMenuOpen = ref(false); // State untuk mengatur buka/tutup menu di Mobile

const isActive = (routeName) => route().current(routeName);
const isParentActive = (pattern) => route().current(pattern);

const toggleSubmenu = (menuName) => {
    if (!isHovered.value && !isMobileMenuOpen.value) {
        isHovered.value = true;
    }
    expandedMenu.value = expandedMenu.value === menuName ? null : menuName;
};
</script>

<template>
    <div class="min-h-screen flex bg-page dark:bg-page-dark transition-colors duration-300 font-sans relative">
        
        <div 
            v-if="isMobileMenuOpen" 
            @click="isMobileMenuOpen = false"
            class="fixed inset-0 bg-ink/50 dark:bg-black/50 backdrop-blur-sm z-40 md:hidden transition-opacity"
        ></div>

        <aside 
            @mouseenter="isHovered=true"
            @mouseleave="isHovered=false; expandedMenu=null"
            class="fixed top-0 left-0 h-screen bg-surface dark:bg-surface-dark border-r border-ghost dark:border-ghost-dark z-50 transition-all duration-300 ease-in-out flex flex-col overflow-hidden shadow-sm"
            :class="[ 
                isHovered ? 'md:w-60 md:shadow-2xl' : 'md:w-14',
                'w-[260px]',
                isMobileMenuOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full md:translate-x-0'
            ]" 
        >
            
            <div class="h-14 flex items-center flex-shrink-0 border-b border-ghost dark:border-ghost-dark bg-surface dark:bg-surface-dark z-10 px-4 md:px-0"
                 :class="[ isHovered || isMobileMenuOpen ? 'justify-between md:justify-start' : 'justify-center' ]">
                
                <div class="flex items-center" :class="[ isHovered || isMobileMenuOpen ? 'md:ml-4' : '' ]">
                    <div class="flex items-center justify-center w-8 h-8 flex-shrink-0">
                        <span class="font-black text-xl text-primary dark:text-primary-dark tracking-tighter">
                            K3
                        </span>
                    </div>
                    
                    <div class="overflow-hidden transition-all duration-300 origin-left flex items-center" 
                         :class="[ isHovered || isMobileMenuOpen ? 'w-auto opacity-100 ml-2' : 'w-0 opacity-0 md:hidden' ]">
                        <span class="font-bold text-xs text-ink dark:text-ink-dark tracking-tight uppercase">Maintenance</span>
                    </div>
                </div>

                <button 
                    @click="isMobileMenuOpen = false"
                    class="md:hidden p-1.5 text-ink/60 hover:text-ink rounded-md hover:bg-ghost transition-colors flex-shrink-0"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="flex-1 py-2 overflow-y-auto custom-scrollbar px-1.5 space-y-0.5">
                <div v-for="(item, index) in menuItems" :key="index">
                    
                    <Link 
                        v-if="!item.children"
                        :href="route(item.route)"
                        :method="item.method || 'get'" 
                        as="button"
                        @click="isMobileMenuOpen = false"
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
                            :class="[ isHovered || isMobileMenuOpen ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4 w-0 md:overflow-hidden' ]">
                            {{ item.name }}
                        </span>

                        <div v-if="!isHovered && !isMobileMenuOpen" class="hidden md:block absolute left-10 bg-gray-900 text-white text-[10px] px-2 py-1 rounded-md opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap shadow-lg translate-x-1">
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
                                    :class="[ isHovered || isMobileMenuOpen ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4 w-0 md:overflow-hidden' ]">
                                    {{ item.name }}
                                </span>
                            </div>
                            <ChevronRight 
                                v-if="isHovered || isMobileMenuOpen"
                                class="w-3 h-3 transition-transform duration-200"
                                :class="{ 'rotate-90': expandedMenu === item.name }"
                            />
                        </button>

                        <div 
                            v-show="(expandedMenu === item.name || isParentActive(item.active)) && (isHovered || isMobileMenuOpen)" 
                            class="ml-2 mt-0.5 space-y-0.5 border-l border-ghost dark:border-ghost-dark pl-2 overflow-hidden"
                        >
                            <Link 
                                v-for="(child, cIndex) in item.children" 
                                :key="cIndex"
                                :href="route(child.route)"
                                @click="isMobileMenuOpen = false"
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

            <Link 
                :href="route('profile.edit')" 
                class="h-14 flex items-center border-t border-ghost dark:border-ghost-dark bg-page/50 dark:bg-surface-dark/50 hover:bg-ghost dark:hover:bg-ghost-dark transition-colors duration-200 cursor-pointer"
                :class="[ isHovered || isMobileMenuOpen ? 'justify-start px-4' : 'justify-center' ]"
            >
                <div class="flex items-center">
                    <div class="w-6 h-6 rounded-md bg-gradient-to-tr from-primary to-purple-500 flex items-center justify-center text-white font-bold text-[9px] shadow-sm flex-shrink-0">
                        {{ user?.name ? user.name[0] : 'U' }}
                    </div>
                    
                    <div class="overflow-hidden transition-all duration-300 ml-2 flex items-center" 
                         :class="[ isHovered || isMobileMenuOpen ? 'opacity-100 w-auto' : 'opacity-0 w-0 md:hidden' ]">
                        <p class="text-[11px] font-bold text-ink dark:text-ink-dark whitespace-nowrap truncate w-24">
                            {{ user?.name }}
                        </p>
                    </div>
                </div>
            </Link>
        </aside>

        <div class="flex-shrink-0 hidden md:block w-14 z-0"></div>

        <main class="flex-1 min-w-0 overflow-x-hidden flex flex-col min-h-screen">
            <header class="min-h-[3.5rem] bg-surface dark:bg-surface-dark border-b border-ghost dark:border-ghost-dark flex flex-col sticky top-0 z-30 transition-colors duration-300">
                
                <div class="flex items-center px-4 py-2 h-14 gap-3">
                    <button 
                        @click="isMobileMenuOpen = true"
                        class="md:hidden p-2 -ml-2 rounded-md text-ink/80 hover:bg-ghost dark:hover:bg-ghost-dark transition-colors flex-shrink-0"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <div class="text-ink dark:text-ink-dark tracking-wide font-semibold text-sm sm:text-base truncate">
                        <slot name="header-title" /> 
                    </div>
                </div>

                <div class="w-full">
                    <slot name="header-nav" />
                </div>
            </header>

            <div class="p-4 flex-1">
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