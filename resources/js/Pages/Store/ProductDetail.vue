<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useQuoteStore } from '@/Stores/useQuoteStore';

const props = defineProps({
    slug: {
        type: String,
        required: true,
    },
});

const product = ref(null);
const loading = ref(true);
const error = ref(null);

const currencyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

const priceDisplay = computed(() => {
    if (!product.value?.price) {
        return 'Call for pricing';
    }

    const price = Number(product.value.price);

    if (Number.isFinite(price)) {
        return currencyFormatter.format(price);
    }

    return 'Call for pricing';
});

const partNumber = computed(() => {
    if (!product.value) {
        return '';
    }

    return product.value.sku ?? product.value.slug ?? `#${product.value.id}`;
});

const unitLabel = computed(() => product.value?.unit ?? 'EA');

const specifications = computed(() => {
    if (!product.value?.specifications || !Array.isArray(product.value.specifications)) {
        return [];
    }

    return product.value.specifications;
});

const buildPlaceholderImage = (seed, width = 800, height = 800) => {
    const safeSeed = encodeURIComponent(seed ?? 'product-detail');
    return `https://picsum.photos/seed/${safeSeed}/${width}/${height}`;
};

const resolveImagePath = (image) => {
    if (typeof image !== 'string' || image.length === 0) {
        return null;
    }

    if (image.startsWith('http://') || image.startsWith('https://')) {
        return image;
    }

    return `/storage/${image.replace(/^\/+/, '')}`;
};

const productImage = computed(() => {
    const resolved = resolveImagePath(product.value?.image ?? '');
    if (resolved) {
        return resolved;
    }

    const seed = product.value?.slug ?? product.value?.id ?? props.slug;
    return buildPlaceholderImage(seed, 800, 800);
});

const stockStatus = computed(() => {
    if (!product.value) {
        return null;
    }

    if (!product.value.in_stock) {
        return {
            label: product.value.lead_time_days ? `Ships in ${product.value.lead_time_days} days` : 'Out of Stock',
            class: 'bg-yellow-100 text-yellow-800',
            icon: '⏱',
        };
    }
    return {
        label: 'In Stock',
        class: 'bg-green-100 text-green-800',
        icon: '✓',
    };
});

const hasDimensions = computed(() => {
    if (!product.value?.dimensions) {
        return false;
    }
    const dims = product.value.dimensions;
    return dims.length_mm || dims.width_mm || dims.height_mm || dims.weight_g;
});

const quoteStore = useQuoteStore();
const justAdded = ref(false);
let feedbackTimer;

const fetchProduct = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/api/v1/store/products/${props.slug}`);

        if (!response.ok) {
            throw new Error('Product not found');
        }

        const data = await response.json();
        product.value = data.data;
    } catch (err) {
        error.value = err.message;
        console.error('Failed to fetch product:', err);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchProduct();
});

const handleAddToQuote = () => {
    if (!product.value) {
        return;
    }

    quoteStore.addItem(product.value);
    justAdded.value = true;

    if (feedbackTimer) {
        clearTimeout(feedbackTimer);
    }

    feedbackTimer = setTimeout(() => {
        justAdded.value = false;
        feedbackTimer = null;
    }, 1500);
};

onBeforeUnmount(() => {
    if (feedbackTimer) {
        clearTimeout(feedbackTimer);
    }
});
</script>

<template>
    <Head :title="product?.name ?? 'Product Details'" />

    <AuthenticatedLayout>
        <div class="bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Back to Catalog -->
                <div class="mb-6">
                    <Link
                        :href="route('store.index')"
                        class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <svg
                            class="w-4 h-4 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                        Back to Catalog
                    </Link>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="bg-white rounded-lg shadow-sm p-8">
                    <div class="animate-pulse space-y-6">
                        <div class="h-8 bg-gray-200 rounded w-1/3"></div>
                        <div class="grid grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <div class="h-64 bg-gray-200 rounded"></div>
                            </div>
                            <div class="space-y-4">
                                <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                <div class="h-32 bg-gray-200 rounded"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-white rounded-lg shadow-sm p-8">
                    <div class="text-center">
                        <p class="text-red-600 font-medium">{{ error }}</p>
                        <Link
                            :href="route('store.index')"
                            class="mt-4 inline-block text-sm text-gray-600 hover:text-gray-900"
                        >
                            Return to catalog
                        </Link>
                    </div>
                </div>

                <!-- Product Details -->
                <div v-else-if="product" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700 px-8 py-6">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex-1">
                                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ product.name }}
                                </h1>
                                <div class="flex flex-wrap items-center gap-3 mt-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                        Part #: {{ partNumber }}
                                    </p>
                                    <span v-if="stockStatus" :class="[stockStatus.class, 'inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full']">
                                        <span>{{ stockStatus.icon }}</span>
                                        {{ stockStatus.label }}
                                    </span>
                                    <p v-if="product.vendor" class="text-sm text-gray-500 dark:text-gray-400">
                                        Vendor: {{ product.vendor.name }}
                                    </p>
                                </div>
                                <p v-if="product.category" class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    Category: {{ product.category.name }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ priceDisplay }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    per {{ unitLabel }}
                                </p>
                                <p v-if="product.list_price && product.list_price > product.price" class="text-sm text-gray-500 dark:text-gray-400 line-through mt-1">
                                    List: {{ currencyFormatter.format(product.list_price) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Two-column layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">
                        <!-- Left: Image/Preview -->
                        <div class="space-y-4">
                            <div
                                class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden"
                                style="aspect-ratio: 1 / 1"
                            >
                                <img
                                    :src="productImage"
                                    :alt="product.name"
                                    class="w-full h-full object-contain"
                                />
                            </div>

                            <!-- Additional Product Info -->
                            <div v-if="product.mpn || product.gtin || product.country_of_origin" class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4">
                                <h3 class="text-xs font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-2">
                                    Product Information
                                </h3>
                                <dl class="space-y-1 text-sm">
                                    <div v-if="product.mpn" class="flex justify-between">
                                        <dt class="text-gray-600 dark:text-gray-400">MPN:</dt>
                                        <dd class="font-mono text-gray-900 dark:text-white">{{ product.mpn }}</dd>
                                    </div>
                                    <div v-if="product.gtin" class="flex justify-between">
                                        <dt class="text-gray-600 dark:text-gray-400">GTIN:</dt>
                                        <dd class="font-mono text-gray-900 dark:text-white">{{ product.gtin }}</dd>
                                    </div>
                                    <div v-if="product.country_of_origin" class="flex justify-between">
                                        <dt class="text-gray-600 dark:text-gray-400">Country of Origin:</dt>
                                        <dd class="text-gray-900 dark:text-white">{{ product.country_of_origin }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Right: Specs & Actions -->
                        <div class="space-y-6">
                            <!-- Description -->
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-2">
                                    Description
                                </h3>
                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                    {{ product.description ?? 'No description available.' }}
                                </p>
                            </div>

                            <!-- Dimensions -->
                            <div v-if="hasDimensions">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-3">
                                    Physical Dimensions
                                </h3>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600">
                                    <div v-if="product.dimensions.length_mm" class="px-4 py-2.5 grid grid-cols-2 gap-4">
                                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Length</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ product.dimensions.length_mm }} mm</dd>
                                    </div>
                                    <div v-if="product.dimensions.width_mm" class="px-4 py-2.5 grid grid-cols-2 gap-4">
                                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Width</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ product.dimensions.width_mm }} mm</dd>
                                    </div>
                                    <div v-if="product.dimensions.height_mm" class="px-4 py-2.5 grid grid-cols-2 gap-4">
                                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Height</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ product.dimensions.height_mm }} mm</dd>
                                    </div>
                                    <div v-if="product.dimensions.weight_g" class="px-4 py-2.5 grid grid-cols-2 gap-4">
                                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Weight</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ product.dimensions.weight_g }} g</dd>
                                    </div>
                                </div>
                            </div>

                            <!-- Specifications -->
                            <div v-if="specifications.length > 0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wide mb-3">
                                    Technical Specifications
                                </h3>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-200 dark:divide-gray-600">
                                    <div
                                        v-for="spec in specifications"
                                        :key="spec.name"
                                        class="px-4 py-2.5 grid grid-cols-2 gap-4"
                                    >
                                        <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ spec.name }}
                                        </dt>
                                        <dd class="text-sm text-gray-900 dark:text-white font-mono">
                                            {{ spec.value ?? '—' }}
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-6 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    No technical specifications available for this product.
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="pt-4">
                                <button
                                    type="button"
                                    class="w-full px-6 py-3 text-base font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                                    @click="handleAddToQuote"
                                >
                                    <span v-if="justAdded" class="inline-flex items-center gap-1">
                                        Added <span aria-hidden="true">&check;</span>
                                    </span>
                                    <span v-else>Add to Quote</span>
                                </button>
                                <p class="text-xs text-gray-500 text-center mt-3">
                                    Questions? Contact our sales team for assistance.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
