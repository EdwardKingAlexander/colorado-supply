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

const headerSubtitle = computed(() => {
  if (props.loading) {
    return 'Loading catalog data...'
  }

  if (!props.products.length) {
    return props.isSearch ? 'No products found. Adjust your filters or search term.' : 'Products will appear here once available.'
  }

  const from = props.meta?.from ?? 1
  const to = props.meta?.to ?? props.products.length
  const total = props.meta?.total ?? props.products.length

  return `Showing ${from}-${to} of ${total} ${props.isSearch ? 'results' : 'items'}`
})

const canGoPrevious = computed(() => props.currentPage > 1)
const canGoNext = computed(() => props.currentPage < props.totalPages)
const showPagination = computed(() => props.totalPages > 1)
</script>

<template>
  <div class="bg-white/95 dark:bg-gray-900/90 rounded-2xl shadow-xl ring-1 ring-gray-100 dark:ring-gray-800 overflow-hidden">
    <div class="p-5 border-b border-gray-100 dark:border-gray-800">
      <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white">Products</h1>
      <p class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-1">
        {{ headerSubtitle }}
      </p>
    </div>

    <div
      class="hidden lg:grid lg:grid-cols-12 gap-4 px-5 py-3 bg-gray-50/90 dark:bg-gray-800/90 border-b border-gray-100 dark:border-gray-800 text-[11px] font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-[0.2em]"
    >
      <div class="col-span-1">Image</div>
      <div class="col-span-2">Part Number</div>
      <div class="col-span-3">Name</div>
      <div class="col-span-2">Description</div>
      <div class="col-span-2">Price</div>
      <div class="col-span-2 text-right">Quote</div>
    </div>

    <div v-if="loading" class="grid grid-cols-1 gap-4 px-4 py-6 sm:grid-cols-2">
      <div
        v-for="row in 4"
        :key="`skeleton-${row}`"
        class="border border-dashed border-gray-200 dark:border-gray-700 rounded-xl p-4 space-y-4 animate-pulse bg-gray-50/60 dark:bg-gray-700/40"
      >
        <div class="flex items-center gap-3">
          <div class="w-16 h-16 rounded bg-gray-200 dark:bg-gray-600"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-3/4"></div>
            <div class="h-3 bg-gray-100 dark:bg-gray-500 rounded w-1/2"></div>
          </div>
        </div>
        <div class="h-3 bg-gray-100 dark:bg-gray-500 rounded w-full"></div>
        <div class="flex items-center justify-between">
          <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-20"></div>
          <div class="h-8 bg-gray-200 dark:bg-gray-600 rounded w-24"></div>
        </div>
      </div>
    </div>
    <template v-else>
      <div v-if="products.length" class="divide-y divide-gray-100 dark:divide-gray-800">
        <ProductRow v-for="product in products" :key="product.id" :product="product" />
      </div>
      <div v-else class="p-10 text-center text-sm text-gray-500">
        <div class="inline-flex flex-col items-center gap-2 rounded-2xl border border-dashed border-gray-300 dark:border-gray-600 px-8 py-6 shadow-sm">
          <p class="font-semibold text-lg text-gray-800 dark:text-gray-100">No products match your filters</p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Try removing a filter, widening the search term, or browsing categories.
          </p>
          <div class="mt-4 flex flex-wrap justify-center gap-2">
            <button
              type="button"
              class="px-3 py-1.5 text-xs font-semibold rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
              @click="$emit('clear-filters')"
            >
              Clear filters
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-xs font-semibold rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
              @click="$emit('reset-navigation')"
            >
              Browse categories
            </button>
          </div>
        </div>
      </div>
    </template>

    <div
      v-if="showPagination"
      class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 text-sm bg-gray-50/60 dark:bg-gray-900/60"
    >
      <button
        type="button"
        class="px-4 py-2 border rounded-lg font-medium transition"
        :class="canGoPrevious ? 'border-gray-300 text-gray-700 hover:bg-gray-50' : 'border-gray-200 text-gray-400 cursor-not-allowed'"
        :disabled="!canGoPrevious"
        @click="emit('previous')"
      >
        Previous
      </button>
      <p class="text-xs text-gray-500">
        Page {{ currentPage }} of {{ totalPages }}
      </p>
      <button
        type="button"
        class="px-4 py-2 border rounded-lg font-medium transition"
        :class="canGoNext ? 'border-gray-300 text-gray-700 hover:bg-gray-50' : 'border-gray-200 text-gray-400 cursor-not-allowed'"
        :disabled="!canGoNext"
        @click="emit('next')"
      >
        Next
      </button>
    </div>
  </div>
</template>
