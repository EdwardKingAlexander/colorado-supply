<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import ProductBuyBox from '@/Components/Store/ProductBuyBox.vue';
import ProductGallery from '@/Components/Store/ProductGallery.vue';
import ProductKeyFacts from '@/Components/Store/ProductKeyFacts.vue';
import ProductSpecs from '@/Components/Store/ProductSpecs.vue';
import RelatedProducts from '@/Components/Store/RelatedProducts.vue';
import RequestQuoteModal from '@/Components/Store/RequestQuoteModal.vue';
import { currency, unitPriceFor } from '@/Support/pricing';

// Server-built payload (App\Services\Store\ProductPageData). Every key is
// optional: anything the product lacks is absent, and every section below
// renders only when its data exists (ai/modules/product-detail-page).
const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const product = computed(() => props.product);
const pricing = computed(() => props.product.pricing ?? { priced: false });
const unitLabel = computed(() => product.value.unit_of_measure ?? 'EA');

// Shared with the buy box so the price follows the chosen quantity.
const quantity = ref(props.product.minimum_order_quantity ?? 1);
const unitPrice = computed(() => unitPriceFor(pricing.value, quantity.value));

const priceDisplay = computed(() => {
    if (unitPrice.value !== null) {
        return currency.format(unitPrice.value);
    }

    // Tier-only pricing: no price below the first break.
    const firstTier = pricing.value.tiers?.[0];

    return firstTier ? `From ${currency.format(firstTier.unit_price)}` : 'Price on request';
});

const showListPrice = computed(() => pricing.value.list_price && unitPrice.value !== null && pricing.value.list_price > unitPrice.value);
const governmentPrices = computed(() => [
    ['GSA price', pricing.value.gsa_price],
    ['Contract price', pricing.value.contract_price],
].filter(([, value]) => typeof value === 'number'));

// Breadcrumbs (catalog URLs use slugs): the top-level crumb opens its
// department, deeper ones the category's products.
const breadcrumbs = computed(() => (product.value.breadcrumbs ?? []).map((crumb, index, all) => ({
    ...crumb,
    href: crumb.slug
        ? route('store.index', index === 0 && all.length > 1 ? { department: crumb.slug } : { category: crumb.slug })
        : route('store.index'),
})));

const COMPLIANCE = {
    berry_compliant: { label: 'Berry compliant', hint: 'Meets the Berry Amendment (US-made materials).' },
    taa_compliant: { label: 'TAA compliant', hint: 'Made in a Trade Agreements Act designated country.' },
    made_in_usa: { label: 'Made in USA', hint: null },
    gsa_approved: { label: 'GSA approved', hint: 'Available on GSA Schedule.' },
    qpl_listed: { label: 'QPL listed', hint: 'On a Qualified Products List.' },
};
const WARNINGS = {
    is_hazmat: { label: 'Hazmat', hint: 'Hazardous material: special shipping rules and lead times may apply.' },
    export_controlled: { label: 'Export controlled', hint: 'May require an export license (ITAR/EAR) before shipping outside the US.' },
};
const compliance = computed(() => (product.value.compliance ?? []).filter((key) => COMPLIANCE[key]).map((key) => ({ key, ...COMPLIANCE[key] })));
const warnings = computed(() => (product.value.warnings ?? []).filter((key) => WARNINGS[key]).map((key) => ({ key, ...WARNINGS[key] })));
const government = computed(() => product.value.government ?? null);

const plural = (count, word) => `${count.toLocaleString()} ${word}${count === 1 ? '' : 's'}`;

// Availability: never a bare "0" badge.
const availability = computed(() => {
    const stock = product.value.stock;

    if (!stock) {
        return null;
    }

    const leadTime = stock.lead_time_days ? `ships in ${plural(stock.lead_time_days, 'day')}` : null;

    if (stock.in_stock === true) {
        return { tone: 'ok', label: 'In stock', detail: typeof stock.on_hand === 'number' ? `${stock.on_hand.toLocaleString()} ${unitLabel.value} available` : null };
    }

    if (stock.in_stock === false) {
        return { tone: 'warn', label: 'Out of stock', detail: leadTime ? `Backorder ${leadTime}` : 'Contact us for availability' };
    }

    return leadTime ? { tone: 'warn', label: `Ships in ${plural(stock.lead_time_days, 'day')}`, detail: null } : null;
});
const locations = computed(() => product.value.stock?.locations ?? []);

// Long descriptions collapse on small screens.
const descriptionExpanded = ref(false);
const longDescription = computed(() => (product.value.description ?? '').length > 400);

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
            <div class="mobile-page-gutter mx-auto max-w-7xl space-y-6 py-6 pb-28 sm:py-8 lg:px-8 lg:pb-8">
                <!-- Breadcrumbs -->
                <nav aria-label="Breadcrumb">
                    <ol class="flex flex-wrap items-center gap-x-1 gap-y-1 text-sm text-st-muted">
                        <li>
                            <Link :href="route('store.index')" class="rounded px-1 py-1 hover:text-st-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action">Store</Link>
                        </li>
                        <li v-for="crumb in breadcrumbs" :key="crumb.id" class="flex items-center gap-1">
                            <span aria-hidden="true">›</span>
                            <Link :href="crumb.href" class="rounded px-1 py-1 hover:text-st-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action">{{ crumb.name }}</Link>
                        </li>
                        <li class="flex min-w-0 items-center gap-1">
                            <span aria-hidden="true">›</span>
                            <span aria-current="page" class="truncate px-1 text-st-ink/85">{{ product.name }}</span>
                        </li>
                    </ol>
                </nav>

                <p v-if="product.inactive" class="rounded-lg border border-st-warn/40 bg-st-warn/10 px-4 py-3 text-sm text-st-ink" role="note">
                    This product is inactive. Only admins can see this page; buyers get "not found".
                </p>

                <div class="rounded-lg bg-st-panel p-4 shadow-sm sm:p-8">
                    <!-- Title -->
                    <header class="mb-6">
                        <p v-if="product.vendor" class="text-sm font-semibold text-st-muted">{{ product.vendor.name }}</p>
                        <h1 class="st-display mt-1 text-2xl font-extrabold text-st-ink sm:text-3xl">{{ product.name }}</h1>
                    </header>

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                        <!-- Gallery -->
                        <div class="lg:col-span-6">
                            <ProductGallery :images="product.images ?? []" :name="product.name" />
                        </div>

                        <!-- Purchase column -->
                        <div class="space-y-6 lg:col-span-6">
                            <!-- Price -->
                            <div>
                                <p class="flex flex-wrap items-baseline gap-x-2">
                                    <span class="st-num text-3xl font-bold text-st-ink">{{ priceDisplay }}</span>
                                    <span v-if="unitPrice !== null" class="text-sm text-st-muted">per {{ unitLabel }}</span>
                                    <span v-if="showListPrice" class="st-num text-sm text-st-muted line-through">List {{ currency.format(pricing.list_price) }}</span>
                                </p>
                                <p v-if="pricing.source === 'company'" class="mt-1 inline-flex rounded-full bg-st-ok/15 px-2.5 py-0.5 text-xs font-semibold text-st-ok">
                                    Your company price
                                </p>
                                <dl v-if="governmentPrices.length" class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                    <div v-for="[label, value] in governmentPrices" :key="label" class="flex gap-2">
                                        <dt class="text-st-muted">{{ label }}</dt>
                                        <dd class="st-num font-semibold text-st-ink">{{ currency.format(value) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Availability -->
                            <div v-if="availability || locations.length" class="space-y-2">
                                <p v-if="availability" class="flex flex-wrap items-center gap-2 text-sm">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        :class="availability.tone === 'ok' ? 'bg-st-ok/15 text-st-ok' : 'bg-st-warn/15 text-st-warn'"
                                    >
                                        {{ availability.label }}
                                    </span>
                                    <span v-if="availability.detail" class="text-st-ink/85">{{ availability.detail }}</span>
                                </p>
                                <ul v-if="locations.length" class="space-y-1 text-sm" aria-label="Stock by location">
                                    <li v-for="location in locations" :key="location.id" class="flex justify-between gap-4 border-b border-st-line/60 pb-1">
                                        <span class="text-st-ink/85">{{ location.name }}</span>
                                        <span class="st-num" :class="location.on_hand > 0 ? 'text-st-ink' : 'text-st-muted'">
                                            {{ location.on_hand > 0 ? `${location.on_hand.toLocaleString()} ${unitLabel}` : 'Out of stock' }}
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Compliance & warnings -->
                            <div v-if="compliance.length || warnings.length || government" class="space-y-2">
                                <ul v-if="compliance.length || warnings.length" class="flex flex-wrap gap-2" aria-label="Compliance">
                                    <li
                                        v-for="badge in compliance"
                                        :key="badge.key"
                                        class="inline-flex items-center gap-1 rounded-full border border-st-ok/40 bg-st-ok/10 px-3 py-1 text-xs font-semibold text-st-ok"
                                        :title="badge.hint ?? undefined"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m5 12 5 5L20 7" /></svg>
                                        {{ badge.label }}
                                        <span v-if="badge.hint" class="sr-only">: {{ badge.hint }}</span>
                                    </li>
                                    <li
                                        v-for="badge in warnings"
                                        :key="badge.key"
                                        class="inline-flex items-center gap-1 rounded-full border border-st-warn/50 bg-st-warn/10 px-3 py-1 text-xs font-semibold text-st-warn"
                                        :title="badge.hint"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg>
                                        {{ badge.label }}
                                        <span class="sr-only">: {{ badge.hint }}</span>
                                    </li>
                                </ul>
                                <p v-if="government" class="text-sm text-st-ink/85">
                                    <span v-if="government.standard_type" class="font-semibold">{{ government.standard_type }} standard</span>
                                    <span v-if="government.standard_type && government.mil_spec_number"> · </span>
                                    <span v-if="government.mil_spec_number" class="st-num">{{ government.mil_spec_number }}</span>
                                </p>
                            </div>

                            <div ref="buyBox">
                                <ProductBuyBox v-model:quantity="quantity" :product="product" @request-quote="quoteOpen = true" />
                            </div>

                            <ProductKeyFacts :product="product" />
                        </div>
                    </div>

                    <!-- Description -->
                    <section v-if="product.description" aria-labelledby="description-heading" class="mt-10 border-t border-st-line pt-8">
                        <h2 id="description-heading" class="st-display mb-3 text-xl font-extrabold text-st-ink">Description</h2>
                        <p
                            id="product-description"
                            class="max-w-3xl whitespace-pre-line text-sm leading-relaxed text-st-ink/85"
                            :class="{ 'max-lg:line-clamp-6': longDescription && !descriptionExpanded }"
                        >
                            {{ product.description }}
                        </p>
                        <button
                            v-if="longDescription"
                            type="button"
                            class="mt-2 min-h-11 text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:hidden"
                            aria-controls="product-description"
                            :aria-expanded="descriptionExpanded"
                            @click="descriptionExpanded = !descriptionExpanded"
                        >
                            {{ descriptionExpanded ? 'Show less' : 'Show more' }}
                        </button>
                    </section>

                    <ProductSpecs :product="product" class="mt-10 border-t border-st-line pt-8" />

                    <RelatedProducts v-if="product.related" :related="product.related" class="mt-10 border-t border-st-line pt-8" />
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
