<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

</script>

<template>
    <Head/>
    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-ink dark:text-ink-dark leading-tight">
                    Setting Checklist Parameter
                </h2>
            </div>
        </template>

        <div>
            Disini ada checklist parameter
        </div>
    </MainLayout>
</template>