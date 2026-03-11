<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <MainLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-ink"
            >
                Profile
            </h2>
        </template>

        <div class="py-4">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-4 lg:px-4">
                <div
                    class="bg-surface p-4 shadow sm:rounded-md sm:p-4"
                >
                    <UpdateProfileInformationForm
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-surface p-4 shadow sm:rounded-md sm:p-4"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </MainLayout>
</template>
