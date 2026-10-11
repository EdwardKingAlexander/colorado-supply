<script setup>
import { computed } from 'vue'
import ProductRow from '@/Components/Store/ProductRow.vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  meta: {
    type: Object,
    default: null,
  },
  isSearch: {
    type: Boolean,
    default: false,
  },
  hasFilters: {
    type: Boolean,
    default: false,
  },
  currentPage: {
    type: Number,
    default: 1,
  },
  totalPages: {
    type: Number,
    default: 1,
  },
})

const emit = defineEmits(['previous', 'next', 'clear-filters', 'reset-navigation'])

const rangeLabel = computed(() => {
  if (props.loading) {
    return 'Loading products'
  }

  const total = props.meta?.total ?? props.products.length
  const noun = total === 1 ? 'product' : 'products'

  if (props.totalPages <= 1) {
    return `${total} ${noun}`
  }

  return `${props.meta?.from ?? 1}–${props.meta?.to ?? props.products.length} of ${total} ${noun}`
})

const canGoPrevious = computed(() => props.currentPage > 1)
const canGoNext = computed(() => props.currentPage < props.totalPages)
const showPagination = computed(() => props.totalPages > 1)
</script>

<template>
  <section class="overflow-hidden rounded-lg border border-st-line bg-st-panel" aria-label="Products">
    <div class="flex items-center justify-between gap-4 border-b border-st-line px-4 py-3 sm:px-5">
      <p class="st-num text-sm text-st-muted" aria-live="polite">{{ rangeLabel }}</p>
      <p v-if="showPagination" class="st-num text-sm text-st-muted">Page {{ currentPage }} of {{ totalPages }}</p>
    </div>

    <!-- Column headings (desktop table) -->
    <div
      class="hidden border-b border-st-line bg-st-sunk/70 px-5 py-2 text-sm font-medium text-st-muted lg:grid lg:grid-cols-[3.5rem_9rem_minmax(0,1fr)_8.5rem_7.5rem_12rem] lg:gap-x-4"
      aria-hidden="true"
    >
      <span />
      <span>Part number</span>
      <span>Product</span>
      <span>Availability</span>
      <span class="text-right">Price</span>
      <span class="text-right">Quantity</span>
    </div>

    <div v-if="loading" class="divide-y divide-st-line" aria-busy="true">
      <div v-for="row in 6" :key="`skeleton-${row}`" class="flex items-center gap-4 px-5 py-4">
        <div class="h-14 w-14 shrink-0 animate-pulse rounded-md bg-st-sunk" />
        <div class="flex-1 space-y-2">
          <div class="h-4 w-2/5 animate-pulse rounded bg-st-sunk" />
          <div class="h-3 w-3/5 animate-pulse rounded bg-st-sunk" />
        </div>
        <div class="hidden h-10 w-44 animate-pulse rounded bg-st-sunk sm:block" />
      </div>
    </div>

    <template v-else>
      <div v-if="products.length" class="divide-y divide-st-line">
        <ProductRow v-for="product in products" :key="product.id" :product="product" />
      </div>

      <div v-else class="px-6 py-14 text-center">
        <p class="st-display text-lg font-bold text-st-ink">
          {{ isSearch ? 'No parts match that search' : 'No parts match these specs' }}
        </p>
        <p class="mx-auto mt-2 max-w-md text-base text-st-muted">
          {{ isSearch
            ? 'Check the part number, try a shorter term, or browse the catalog by department.'
            : 'Remove a filter to widen the results, or go back to the catalog.' }}
        </p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
          <button
            v-if="hasFilters"
            type="button"
            class="inline-flex min-h-11 items-center rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
            @click="emit('clear-filters')"
          >
            Clear filters
          </button>
          <button
            type="button"
            class="inline-flex min-h-11 items-center rounded-md bg-st-fill px-4 text-sm font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
            @click="emit('reset-navigation')"
          >
            Browse the catalog
          </button>
        </div>
      </div>
    </template>

    <nav
      v-if="showPagination"
      class="flex items-center justify-between gap-2 border-t border-st-line px-4 py-3 sm:px-5"
      aria-label="Pagination"
    >
      <button
        type="button"
        class="inline-flex min-h-11 items-center rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-st-line"
        :disabled="!canGoPrevious"
        @click="emit('previous')"
      >
        Previous page
      </button>
      <button
        type="button"
        class="inline-flex min-h-11 items-center rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-st-line"
        :disabled="!canGoNext"
        @click="emit('next')"
      >
        Next page
      </button>
    </nav>
  </section>
</template>
