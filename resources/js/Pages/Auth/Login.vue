<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    username: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <div class="w-full max-w-sm mx-auto px-4 sm:px-0">
            
            <div v-if="status" class="mb-4 p-3 rounded-md bg-green-50 text-[12px] sm:text-[13px] font-medium text-success text-center">
                {{ status }}
            </div>

            <div class="mb-6 sm:mb-8 text-center sm:text-left">
                <p class="text-[12px] sm:text-[13px] font-medium text-ink-light mb-1">
                    Silakan masukkan detail Anda
                </p>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-ink">
                    Selamat datang kembali
                </h1>
            </div>

            <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                
                <div>
                    <TextInput
                        id="username"
                        type="text"
                        class="block w-full py-2 px-3 text-[13px] sm:text-sm rounded-lg transition-all"
                        v-model="form.username"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Username"
                    />
                    <InputError class="mt-1.5 text-[11px] sm:text-[12px]" :message="form.errors.username" />
                </div>

                <div>
                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full py-2 px-3 text-[13px] sm:text-sm rounded-lg transition-all"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                    <InputError class="mt-1.5 text-[11px] sm:text-[12px]" :message="form.errors.password" />
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-[13px] sm:text-sm font-bold tracking-wide text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Memproses...' : 'Masuk' }}
                    </button>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>