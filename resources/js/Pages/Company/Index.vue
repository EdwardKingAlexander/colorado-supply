<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    company: Object,
    locations: Array,
});
</script>

<template>
    <Head title="Company Home" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Company: {{ company.name }}</h2>
        </template>

        <div class="py-8 sm:py-12">
            <div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-4 text-gray-900 sm:p-6">
                        <h3 class="text-xl font-semibold">Your Locations</h3>
                        <ul class="mt-4 divide-y divide-gray-200">
                            <li v-for="location in locations" :key="location.id">
                                <Link :href="route('store.location.index', { location: location.slug })" class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-medium text-indigo-700 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                                    {{ location.name }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
