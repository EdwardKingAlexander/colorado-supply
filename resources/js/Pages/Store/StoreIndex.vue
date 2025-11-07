<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SearchBar from '@/Components/Store/SearchBar.vue';
import CategorySidebar from '@/Components/Store/CategorySidebar.vue';
import ProductList from '@/Components/Store/ProductList.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Store" />

    <AuthenticatedLayout>
        <div class="bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Search Bar -->
                <div class="mb-8">
                    <SearchBar />
                </div>

                <!-- Two-column layout -->
                <div class="grid grid-cols-12 gap-6">
                    <!-- Left Sidebar -->
                    <div class="col-span-12 lg:col-span-3">
                        <CategorySidebar />
                    </div>

                    <!-- Right Product List -->
                    <div class="col-span-12 lg:col-span-9">
                        <ProductList />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
