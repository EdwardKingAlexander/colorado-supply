<script setup>
defineProps({
    product: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="px-4 py-4 hover:bg-gray-50 transition-colors">
        <!-- Desktop Layout -->
        <div class="hidden lg:grid lg:grid-cols-12 gap-4 items-center">
            <div class="col-span-2">
                <p class="text-sm font-mono text-gray-900">{{ product.partNumber }}</p>
            </div>
            <div class="col-span-3">
                <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
            </div>
            <div class="col-span-4">
                <p class="text-sm text-gray-600">{{ product.description }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-sm font-semibold text-gray-900">${{ product.price.toFixed(2) }}</p>
            </div>
            <div class="col-span-1">
                <button
                    type="button"
                    class="w-full px-3 py-1.5 text-xs font-medium text-white bg-gray-900 hover:bg-gray-800 rounded transition-colors"
                >
                    Add to Quote
                </button>
            </div>
        </div>

        <!-- Mobile Layout -->
        <div class="lg:hidden space-y-2">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
                    <p class="text-xs font-mono text-gray-500 mt-1">{{ product.partNumber }}</p>
                </div>
                <p class="text-sm font-semibold text-gray-900">${{ product.price.toFixed(2) }}</p>
            </div>
            <p class="text-sm text-gray-600">{{ product.description }}</p>
            <button
                type="button"
                class="w-full px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors"
            >
                Add to Quote
            </button>
        </div>
    </div>
</template>
