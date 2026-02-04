import { ref, onMounted } from 'vue';

const isDark = ref(false);

export function useTheme() {
    
    onMounted(() => {
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            isDark.value = true;
            document.documentElement.classList.add('dark');
        } else {
            isDark.value = false;
            document.documentElement.classList.remove('dark');
        }
    });

    const toggleTheme = () => {
        if (isDark.value) {
            isDark.value = false;
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
        } else {
            isDark.value = true;
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
        }
    };

    return { isDark, toggleTheme };
}