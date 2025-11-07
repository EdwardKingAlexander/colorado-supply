<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Product Details" />

    <AuthenticatedLayout>
        <div class="bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white rounded-lg shadow-sm p-8">
                    <div class="flex items-center justify-center h-96">
                        <p class="text-gray-500 text-lg">Product Details Here</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
