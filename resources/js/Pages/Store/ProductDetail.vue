<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import ProductBuyBox from '@/Components/Store/ProductBuyBox.vue';
import RequestQuoteModal from '@/Components/Store/RequestQuoteModal.vue';
import { unitPriceFor } from '@/Support/pricing';

// Server-built payload (App\Services\Store\ProductPageData). Every key is
// optional: anything the product lacks is absent, so sections check presence.
const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const product = computed(() => props.product);
const pricing = computed(() => props.product.pricing ?? { priced: false });

const currencyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

// Shared with the buy box so the header price follows the chosen quantity.
const quantity = ref(props.product.minimum_order_quantity ?? 1);

const unitPrice = computed(() => unitPriceFor(pricing.value, quantity.value));

const priceDisplay = computed(() => {
    if (unitPrice.value !== null) {
        return currencyFormatter.format(unitPrice.value);
    }

    // Tier-only pricing: no price below the first break.
    const firstTier = pricing.value.tiers?.[0];

    return firstTier ? `From ${currencyFormatter.format(firstTier.unit_price)}` : 'Call for pricing';
});

const partNumber = computed(() => product.value.sku);

const unitLabel = computed(() => product.value.unit_of_measure ?? 'EA');

const category = computed(() => product.value.breadcrumbs?.at(-1) ?? null);

const specifications = computed(() => product.value.specs?.attributes ?? []);

const dimensions = computed(() => product.value.specs?.dimensions ?? null);

// No stock-photo fallback: a missing image shows a neutral part glyph instead.
const productImage = computed(() => product.value.images?.[0] ?? null);

const productInfo = computed(() => ({
    mpn: product.value.identifiers?.mpn,
    gtin: product.value.identifiers?.gtin,
    country: product.value.specs?.general?.country_of_origin,
}));

const hasProductInfo = computed(() => Object.values(productInfo.value).some(Boolean));

const stockStatus = computed(() => {
    const stock = product.value.stock;

    if (!stock || typeof stock.in_stock === 'undefined') {
        return stock?.lead_time_days
            ? { label: `Ships in ${stock.lead_time_days} ${stock.lead_time_days === 1 ? 'day' : 'days'}`, class: 'bg-st-warn/15 text-st-warn', icon: '⏱' }
            : null;
    }

    if (!stock.in_stock) {
        return {
            label: stock.lead_time_days ? `Ships in ${stock.lead_time_days} ${stock.lead_time_days === 1 ? 'day' : 'days'}` : 'Out of Stock',
            class: 'bg-st-warn/15 text-st-warn',
            icon: '⏱',
        };
    }

    return {
        label: 'In Stock',
        class: 'bg-st-ok/15 text-st-ok',
        icon: '✓',
    };
});

const quoteOpen = ref(false);

// Mobile sticky bar: shown below lg once the buy box scrolls out of view.
const buyBox = ref(null);
const buyBoxVisible = ref(true);
let buyBoxObserver;

onMounted(() => {
    if (!buyBox.value || typeof IntersectionObserver === 'undefined') {
        return;
    }

    buyBoxObserver = new IntersectionObserver(([entry]) => {
        buyBoxVisible.value = entry.isIntersecting;
    });
    buyBoxObserver.observe(buyBox.value);
});

onBeforeUnmount(() => buyBoxObserver?.disconnect());

const jumpToBuyBox = () => {
    buyBox.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    buyBox.value?.querySelector('#buy-quantity')?.focus({ preventScroll: true });
};
</script>

<template>
    <Head :title="product.name" />

    <StoreLayout>
        <div class="bg-st-bg min-h-screen">
            <div class="mobile-page-gutter mx-auto max-w-7xl py-6 sm:py-8 lg:px-8">
                <!-- Back to Catalog -->
                <div class="mb-6">
                    <Link
                        :href="route('store.index')"
                        class="inline-flex min-h-12 items-center rounded-md px-2 text-base text-st-muted transition-colors hover:bg-st-sunk hover:text-st-ink focus:outline-none focus:ring-2 focus:ring-st-action"
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

                <!-- Product Details -->
                <div class="bg-st-panel rounded-lg shadow-sm">
                    <!-- Header -->
                    <div class="border-b border-st-line px-4 py-5 sm:px-8 sm:py-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                <h1 class="st-display text-2xl font-extrabold text-st-ink sm:text-3xl">
                                    {{ product.name }}
                                </h1>
                                <div class="flex flex-wrap items-center gap-3 mt-3">
                                    <p class="text-sm text-st-muted font-mono">
                                        Part #: {{ partNumber }}
                                    </p>
                                    <span v-if="stockStatus" :class="[stockStatus.class, 'inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full']">
                                        <span>{{ stockStatus.icon }}</span>
                                        {{ stockStatus.label }}
                                    </span>
                                    <p v-if="product.vendor" class="text-sm text-st-muted">
                                        Vendor: {{ product.vendor.name }}
                                    </p>
                                </div>
                                <p v-if="category" class="text-sm text-st-muted mt-2">
                                    Category: {{ category.name }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-left sm:text-right">
                                <p class="text-3xl font-bold text-st-ink">
                                    {{ priceDisplay }}
                                </p>
                                <p class="text-sm text-st-muted mt-1">
                                    per {{ unitLabel }}
                                </p>
                                <p v-if="pricing.list_price && unitPrice !== null && pricing.list_price > unitPrice" class="text-sm text-st-muted line-through mt-1">
                                    List: {{ currencyFormatter.format(pricing.list_price) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Two-column layout -->
                    <div class="grid grid-cols-1 gap-8 p-4 sm:p-8 lg:grid-cols-2">
                        <!-- Left: Image/Preview -->
                        <div class="space-y-4">
                            <div
                                class="bg-st-bg rounded-lg border border-st-line overflow-hidden"
                                style="aspect-ratio: 1 / 1"
                            >
                                <img
                                    v-if="productImage"
                                    :src="productImage.url"
                                    :alt="productImage.alt"
                                    class="w-full h-full object-contain"
                                />
                                <svg v-else class="h-full w-full p-[22%] text-st-muted/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                    <path stroke-linejoin="round" d="M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z" />
                                    <path stroke-linejoin="round" d="m4 7.25 8 4.5 8-4.5M12 11.75v9.5" />
                                </svg>
                            </div>

                            <!-- Additional Product Info -->
                            <div v-if="hasProductInfo" class="bg-st-bg rounded-lg border border-st-line p-4">
                                <h3 class="text-xs font-semibold text-st-ink mb-2">
                                    Product Information
                                </h3>
                                <dl class="space-y-1 text-sm">
                                    <div v-if="productInfo.mpn" class="flex justify-between">
                                        <dt class="text-st-muted">MPN:</dt>
                                        <dd class="font-mono text-st-ink">{{ productInfo.mpn }}</dd>
                                    </div>
                                    <div v-if="productInfo.gtin" class="flex justify-between">
                                        <dt class="text-st-muted">GTIN:</dt>
                                        <dd class="font-mono text-st-ink">{{ productInfo.gtin }}</dd>
                                    </div>
                                    <div v-if="productInfo.country" class="flex justify-between">
                                        <dt class="text-st-muted">Country of Origin:</dt>
                                        <dd class="text-st-ink">{{ productInfo.country }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Right: Buy box & specs -->
                        <div class="space-y-6">
                            <div ref="buyBox">
                                <ProductBuyBox v-model:quantity="quantity" :product="product" @request-quote="quoteOpen = true" />
                            </div>

                            <!-- Description -->
                            <div v-if="product.description">
                                <h3 class="text-sm font-semibold text-st-ink mb-2">
                                    Description
                                </h3>
                                <p class="text-sm text-st-ink/85 leading-relaxed whitespace-pre-line">
                                    {{ product.description }}
                                </p>
                            </div>

                            <!-- Dimensions -->
                            <div v-if="dimensions">
                                <h3 class="text-sm font-semibold text-st-ink mb-3">
                                    Physical Dimensions
                                </h3>
                                <div class="bg-st-bg rounded-lg border border-st-line divide-y divide-st-line">
                                    <div v-if="dimensions.length_mm" class="px-4 py-2.5 grid grid-cols-2 gap-4">
                                        <dt class="text-sm font-medium text-st-ink/85">Length</dt>
                                        <dd class="text-sm text-st-ink font-mono">{{ dimensions.length_mm }} mm</dd>
                                    </div>
                                    <div v-if="dimensions.width_mm" class="px-4 py-2.5 grid grid-cols-2 gap-4">
                                        <dt class="text-sm font-medium text-st-ink/85">Width</dt>
                                        <dd class="text-sm text-st-ink font-mono">{{ dimensions.width_mm }} mm</dd>
                                    </div>
                                    <div v-if="dimensions.height_mm" class="px-4 py-2.5 grid grid-cols-2 gap-4">
                                        <dt class="text-sm font-medium text-st-ink/85">Height</dt>
                                        <dd class="text-sm text-st-ink font-mono">{{ dimensions.height_mm }} mm</dd>
                                    </div>
                                    <div v-if="dimensions.weight_g" class="px-4 py-2.5 grid grid-cols-2 gap-4">
                                        <dt class="text-sm font-medium text-st-ink/85">Weight</dt>
                                        <dd class="text-sm text-st-ink font-mono">{{ dimensions.weight_g }} g</dd>
                                    </div>
                                </div>
                            </div>

                            <!-- Specifications -->
                            <div v-if="specifications.length > 0">
                                <h3 class="text-sm font-semibold text-st-ink mb-3">
                                    Technical Specifications
                                </h3>
                                <div class="bg-st-bg rounded-lg border border-st-line divide-y divide-st-line">
                                    <div
                                        v-for="spec in specifications"
                                        :key="spec.name"
                                        class="px-4 py-2.5 grid grid-cols-2 gap-4"
                                    >
                                        <dt class="text-sm font-medium text-st-ink/85">
                                            {{ spec.name }}
                                        </dt>
                                        <dd class="text-sm text-st-ink font-mono">
                                            {{ spec.value ?? '—' }}
                                        </dd>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <RequestQuoteModal v-model:open="quoteOpen" :product="product" :quantity="quantity" />

        <!-- Mobile sticky action bar -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-full"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="!buyBoxVisible"
                class="fixed inset-x-0 bottom-0 z-40 border-t border-st-line bg-st-panel/95 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur lg:hidden"
            >
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm text-st-muted">{{ product.name }}</p>
                        <p class="st-num text-lg font-bold text-st-ink">
                            {{ priceDisplay }}<span v-if="unitPrice !== null" class="text-sm font-normal text-st-muted"> / {{ unitLabel }}</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="min-h-12 shrink-0 rounded-md bg-st-fill px-5 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2"
                        @click="unitPrice !== null ? jumpToBuyBox() : (quoteOpen = true)"
                    >
                        {{ unitPrice !== null ? 'Buy' : 'Request a Quote' }}
                    </button>
                </div>
            </div>
        </Transition>
    </StoreLayout>
</template>
