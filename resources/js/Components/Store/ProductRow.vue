<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useCartStore } from '@/Stores/useCartStore'

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

  return Number.isFinite(price) && price > 0 ? currencyFormatter.format(price) : null
})

const unitLabel = computed(() => props.product.unit ?? 'each')

const specPreview = computed(() => {
  if (!Array.isArray(props.product.specifications) || props.product.specifications.length === 0) {
    return []
  }

  return props.product.specifications.slice(0, 3)
})

// Canonical id-based URL; slugs are only unique per vendor.
const productUrl = computed(() => route('store.product', { product: props.product.id, slug: props.product.slug ?? undefined }))

// Real product photo when one exists; otherwise the row shows a neutral part glyph.
const productImage = computed(() => {
  const image = props.product.image

  if (typeof image !== 'string' || image.length === 0) {
    return null
  }

  if (image.startsWith('http://') || image.startsWith('https://')) {
    return image
  }

  return `/storage/${image.replace(/^\/+/, '')}`
})

const imageFailed = ref(false)

const availability = computed(() => {
  if (props.product.in_stock) {
    return { label: 'In stock', tone: 'ok' }
  }

  if (props.product.lead_time_days) {
    return { label: `Ships in ${props.product.lead_time_days} days`, tone: 'warn' }
  }

  return { label: 'Backordered', tone: 'warn' }
})

const cartStore = useCartStore()
const justAdded = ref(false)
const quantity = ref(1)
let feedbackTimer

const setQuantity = (value) => {
  const parsed = Math.floor(Number(value))
  quantity.value = Number.isFinite(parsed) && parsed > 0 ? Math.min(parsed, 9999) : 1
}

const handleAddToCart = () => {
  cartStore.addItem(props.product, quantity.value)
  justAdded.value = true

  clearTimeout(feedbackTimer)
  feedbackTimer = setTimeout(() => {
    justAdded.value = false
  }, 1800)
}

onBeforeUnmount(() => clearTimeout(feedbackTimer))
</script>

<template>
  <div class="grid grid-cols-[4rem_minmax(0,1fr)] gap-x-4 gap-y-3 px-4 py-4 hover:bg-st-sunk/60 sm:px-5 lg:grid-cols-[3.5rem_9rem_minmax(0,1fr)_8.5rem_7.5rem_12rem] lg:items-center lg:gap-y-0 lg:py-3">
    <!-- Thumbnail -->
    <Link
      :href="productUrl"
      class="row-span-2 block h-16 w-16 overflow-hidden rounded-md border border-st-line bg-st-sunk focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:row-span-1 lg:h-14 lg:w-14"
      tabindex="-1"
      aria-hidden="true"
    >
      <img
        v-if="productImage && !imageFailed"
        :src="productImage"
        alt=""
        loading="lazy"
        class="h-full w-full object-cover"
        @error="imageFailed = true"
      />
      <svg v-else class="h-full w-full p-3.5 text-st-muted/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
        <path stroke-linejoin="round" d="M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z" />
        <path stroke-linejoin="round" d="m4 7.25 8 4.5 8-4.5M12 11.75v9.5" />
      </svg>
    </Link>

    <!-- Part number (desktop column) -->
    <div class="hidden lg:block">
      <p class="st-num break-all text-sm font-semibold text-st-ink">{{ partNumber }}</p>
    </div>

    <!-- Product -->
    <div class="min-w-0">
      <Link
        :href="productUrl"
        class="text-base font-semibold leading-snug text-st-ink hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:text-[0.9375rem]"
      >
        {{ product.name }}
      </Link>
      <p class="st-num mt-0.5 text-sm text-st-muted lg:hidden">{{ partNumber }}</p>
      <dl v-if="specPreview.length" class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-sm text-st-muted">
        <div v-for="spec in specPreview" :key="spec.name" class="flex gap-1">
          <dt>{{ spec.name }}:</dt>
          <dd class="text-st-ink/85">{{ spec.value }}</dd>
        </div>
      </dl>
      <p v-else-if="product.description" class="mt-1 line-clamp-1 text-sm text-st-muted">
        {{ product.description }}
      </p>
    </div>

    <!-- Availability + price on mobile share a line -->
    <div class="col-start-2 flex items-baseline justify-between gap-3 lg:col-start-auto lg:block">
      <p
        class="inline-flex items-center gap-1.5 text-sm font-medium"
        :class="availability.tone === 'ok' ? 'text-st-ok' : 'text-st-warn'"
      >
        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
        {{ availability.label }}
      </p>
      <p class="st-num text-right text-base font-semibold text-st-ink lg:hidden">
        {{ priceDisplay ?? 'Request price' }}
        <span v-if="priceDisplay" class="text-sm font-normal text-st-muted">/ {{ unitLabel }}</span>
      </p>
    </div>

    <div class="hidden text-right lg:block">
      <p class="st-num text-[0.9375rem] font-semibold text-st-ink">{{ priceDisplay ?? 'Request price' }}</p>
      <p v-if="priceDisplay" class="text-sm text-st-muted">per {{ unitLabel }}</p>
    </div>

    <!-- Quantity + add -->
    <div class="col-span-2 flex items-center gap-2 lg:col-span-1 lg:justify-end">
      <label :for="`qty-${product.id}`" class="sr-only">Quantity for {{ product.name }}</label>
      <input
        :id="`qty-${product.id}`"
        :value="quantity"
        type="number"
        min="1"
        max="9999"
        inputmode="numeric"
        class="st-num h-11 w-20 rounded-md border border-st-line bg-st-sunk px-2 text-center text-base text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40 lg:h-10 lg:w-16"
        @change="setQuantity($event.target.value)"
      />
      <button
        type="button"
        class="h-11 flex-1 rounded-md px-4 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action focus-visible:ring-offset-2 focus-visible:ring-offset-st-panel lg:h-10 lg:flex-none"
        :class="justAdded ? 'bg-st-ok/15 text-st-ok' : 'bg-st-fill text-white hover:bg-st-fill/85'"
        @click="handleAddToCart"
      >
        <span aria-live="polite">{{ justAdded ? 'Added to cart' : 'Add to cart' }}</span>
      </button>
    </div>
  </div>
</template>
