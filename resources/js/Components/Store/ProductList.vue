<script setup>
import ProductRow from '@/Components/Store/ProductRow.vue';

const products = [
    {
        id: 1,
        name: 'Hex Head Cap Screw',
        partNumber: 'HCS-1234',
        description: 'Grade 8 Steel, 1/2"-13 Thread, 2" Length',
        price: 1.25,
    },
    {
        id: 2,
        name: 'Ball Valve - Brass',
        partNumber: 'BV-5678',
        description: '1/2" NPT, Full Port, Lever Handle',
        price: 24.50,
    },
    {
        id: 3,
        name: 'Wire Connector',
        partNumber: 'WC-9012',
        description: '22-14 AWG, Spring Insert, Pack of 100',
        price: 8.75,
    },
    {
        id: 4,
        name: 'Safety Glasses',
        partNumber: 'SG-3456',
        description: 'Clear Polycarbonate Lens, Anti-Fog Coating',
        price: 12.00,
    },
];
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h1 class="text-xl font-semibold text-gray-900">Products</h1>
            <p class="text-sm text-gray-500 mt-1">{{ products.length }} items</p>
        </div>

        <!-- Table Header -->
        <div class="hidden lg:grid lg:grid-cols-12 gap-4 px-4 py-3 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-700 uppercase tracking-wider">
            <div class="col-span-2">Part Number</div>
            <div class="col-span-3">Name</div>
            <div class="col-span-4">Description</div>
            <div class="col-span-2">Price</div>
            <div class="col-span-1"></div>
        </div>

        <!-- Product Rows -->
        <div class="divide-y divide-gray-200">
            <ProductRow
                v-for="product in products"
                :key="product.id"
                :product="product"
            />
        </div>
    </div>
</template>
