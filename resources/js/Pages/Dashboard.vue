<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <MainLayout>
        <template #header>
            <h2
            >
                Dashboard
            </h2>
        </template>

        <div class="py-4">
            <div class="mx-auto max-w-7xl sm:px-4 lg:px-4">
                <div
                    class="overflow-hidden bg-surface shadow-sm sm:rounded-md"
                >
                    <div class="p-4 text-ink">
                        You're logged in!
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
