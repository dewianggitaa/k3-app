import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user || null);

    const isAuthenticated = computed(() => !!user.value);

    const hasPin = computed(() => !!user.value?.signature_pin);

    return { 
        user, 
        isAuthenticated,
        hasPin 
    };
}