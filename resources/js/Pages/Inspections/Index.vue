<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Swal from 'sweetalert2';

import MainLayout from '@/Layouts/MainLayout.vue';


</script>

<template>
    <Head title="Monitoring Tugas"/>
    <MainLayout>
        <template #header-title>
             <div class="flex items-center gap-4 px-4"> 
                <h2 class="font-bold text-lg text-ink dark:text-ink-dark leading-tight">
                    Monitoring Tugas
                </h2>
            </div>
        </template>
        <div>
            Disini ada tugas tugas
        </div>
    </MainLayout>
</template>