<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-ghost relative px-5 sm:px-0"
    >
        <div class="absolute top-6 left-6 sm:left-8">
            <Link href="/" class="flex items-center gap-1.5">
                <span class="text-xl font-black text-primary tracking-tighter">K3</span>
                <span class="text-[13px] font-bold text-ink uppercase tracking-tight">Maintenance</span>
            </Link>
        </div>

        <div
            class="w-full bg-surface px-6 py-8 sm:px-8 sm:py-10 md:px-10 md:py-10 lg:px-10 lg:py-10 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] max-w-[380px] md:max-w-[420px] lg:max-w-[480px] rounded-xl lg:rounded-2xl border border-ghost-hover transition-all duration-300"
        >
            <slot />
        </div>
    </div>
</template>