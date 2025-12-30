<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useQuoteStore } from '@/Stores/useQuoteStore'

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
})

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const partNumber = computed(() => props.product.sku ?? props.product.slug ?? `#${props.product.id}`)

const priceDisplay = computed(() => {
  const price = Number(props.product.price)

  if (Number.isFinite(price)) {
    return currencyFormatter.format(price)
  }

  return 'Call for pricing'
})

const unitLabel = computed(() => props.product.unit ?? 'EA')

const specPreview = computed(() => {
  if (!Array.isArray(props.product.specifications) || props.product.specifications.length === 0) {
    return null
  }

  return props.product.specifications
    .slice(0, 2)
    .map((spec) => `${spec.name}: ${spec.value}`)
    .join(' | ')
})

const productSlug = computed(() => props.product.slug ?? props.product.id)

const buildPlaceholderImage = (seed, width = 400, height = 400) => {
  const safeSeed = encodeURIComponent(seed ?? 'product')
  return `https://picsum.photos/seed/${safeSeed}/${width}/${height}`
}

const productImage = computed(() => {
  const image = props.product.image
  if (typeof image === 'string' && image.length > 0) {
    if (image.startsWith('http://') || image.startsWith('https://')) {
      return image
    }

    return `/storage/${image.replace(/^\/+/, '')}`
  }

  const seed = props.product.slug ?? props.product.id ?? 'catalog-item'
  return buildPlaceholderImage(seed, 400, 400)
})

const stockStatus = computed(() => {
  if (!props.product.in_stock) {
    return {
      label: props.product.lead_time_days ? `Ships in ${props.product.lead_time_days} days` : 'Out of Stock',
      class: 'bg-yellow-100 text-yellow-800',
    }
  }
  return {
    label: 'In Stock',
    class: 'bg-green-100 text-green-800',
  }
})

const quoteStore = useQuoteStore()
const justAdded = ref(false)
let feedbackTimer
const quantity = ref(1)

const incrementLocalQuantity = () => {
  quantity.value += 1
}

const decrementLocalQuantity = () => {
  if (quantity.value <= 1) {
    return
  }

  quantity.value -= 1
}

const handleAddToQuote = () => {
  quoteStore.addItem(props.product, quantity.value)
  justAdded.value = true

  if (feedbackTimer) {
    clearTimeout(feedbackTimer)
  }

  feedbackTimer = setTimeout(() => {
    justAdded.value = false
    feedbackTimer = null
  }, 1500)
}

onBeforeUnmount(() => {
  if (feedbackTimer) {
    clearTimeout(feedbackTimer)
  }
})
</script>

<template>
  <div class="px-4 py-4 hover:bg-gray-50/70 dark:hover:bg-gray-800 transition-all">
    <div class="hidden lg:grid lg:grid-cols-12 gap-4 items-center">
      <div class="col-span-1">
        <Link :href="route('store.show', productSlug)">
          <img
            :src="productImage"
            :alt="product.name"
            class="w-16 h-16 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
          />
        </Link>
      </div>
      <div class="col-span-2">
        <p class="text-sm font-mono text-gray-900 dark:text-white tracking-tight">{{ partNumber }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 uppercase">{{ unitLabel }}</p>
        <span
          :class="[stockStatus.class, 'inline-flex items-center gap-1 mt-1 px-2 py-0.5 text-[11px] font-semibold rounded-full']"
        >
          {{ stockStatus.label }}
        </span>
      </div>
      <div class="col-span-3">
        <Link :href="route('store.show', productSlug)" class="text-sm font-medium text-gray-900 dark:text-white hover:text-gray-700 dark:hover:text-gray-300 hover:underline">
          {{ product.name }}
        </Link>
        <p v-if="specPreview" class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1">
          {{ specPreview }}
        </p>
      </div>
      <div class="col-span-2">
        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
          {{ product.description ?? 'No description available.' }}
        </p>
      </div>
      <div class="col-span-2">
        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ priceDisplay }}</p>
        <p v-if="product.vendor" class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">
          {{ product.vendor.name }}
        </p>
      </div>
      <div class="col-span-2">
        <div class="flex items-center justify-between gap-2 mb-2">
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="quantity <= 1"
            @click="decrementLocalQuantity"
          >
            &minus;
          </button>
          <span class="w-10 text-center text-sm font-semibold text-gray-900">{{ quantity }}</span>
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
            @click="incrementLocalQuantity"
          >
            +
          </button>
        </div>
        <button
          type="button"
          class="w-full px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
          @click="handleAddToQuote"
        >
          <span v-if="justAdded" class="inline-flex items-center gap-1">
            Added <span aria-hidden="true">&check;</span>
          </span>
          <span v-else>Add to Quote</span>
        </button>
      </div>
    </div>

    <div class="lg:hidden space-y-3">
      <div class="flex gap-3">
        <Link :href="route('store.show', productSlug)" class="flex-shrink-0">
          <img
            :src="productImage"
            :alt="product.name"
            class="w-20 h-20 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
          />
        </Link>
        <div class="flex-1 min-w-0">
          <Link :href="route('store.show', productSlug)" class="text-sm font-medium text-gray-900 dark:text-white hover:text-gray-700 dark:hover:text-gray-300 hover:underline">
            {{ product.name }}
          </Link>
          <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1">{{ partNumber }}</p>
          <div class="flex items-center gap-2 mt-1">
            <span
              :class="[stockStatus.class, 'inline-block px-2 py-0.5 text-xs font-medium rounded']"
            >
              {{ stockStatus.label }}
            </span>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ unitLabel }}</p>
          </div>
          <p v-if="specPreview" class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1">
            {{ specPreview }}
          </p>
        </div>
        <div class="text-right flex-shrink-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ priceDisplay }}</p>
          <p v-if="product.vendor" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            {{ product.vendor.name }}
          </p>
        </div>
      </div>
      <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
        {{ product.description ?? 'No description available.' }}
      </p>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="px-3 py-1 border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="quantity <= 1"
          @click="decrementLocalQuantity"
        >
          &minus;
        </button>
        <span class="w-10 text-center text-sm font-semibold text-gray-900">{{ quantity }}</span>
        <button
          type="button"
          class="px-3 py-1 border border-gray-300 rounded text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
          @click="incrementLocalQuantity"
        >
          +
        </button>
        <button
          type="button"
          class="flex-1 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 rounded-lg transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
          @click="handleAddToQuote"
        >
          <span v-if="justAdded" class="inline-flex items-center gap-1">
            Added <span aria-hidden="true">&check;</span>
          </span>
          <span v-else>Add to Quote</span>
        </button>
      </div>
    </div>
  </div>
</template>
