<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useCartStore } from '@/Stores/useCartStore'
import { currency, normalizeQuantity, unitPriceFor } from '@/Support/pricing'

// Buy box for the product page (ai/modules/product-detail-page, phase 3).
// Quantity has no upper bound: orders above stock are backordered.
const props = defineProps({
  product: { type: Object, required: true },
})

const quantity = defineModel('quantity', { type: Number, default: 1 })

const emit = defineEmits(['request-quote'])

const pricing = computed(() => props.product.pricing ?? { priced: false })
const minQuantity = computed(() => props.product.minimum_order_quantity ?? 1)
const unit = computed(() => props.product.unit_of_measure ?? 'EA')
const onHand = computed(() => props.product.stock?.on_hand)

const unitPrice = computed(() => unitPriceFor(pricing.value, quantity.value))
const lineTotal = computed(() => (unitPrice.value === null ? null : unitPrice.value * quantity.value))
const canBuy = computed(() => unitPrice.value !== null)

// A tier-only product has no price below its first break.
const firstTier = computed(() => pricing.value.tiers?.[0] ?? null)
const belowFirstTier = computed(() => !canBuy.value && firstTier.value && quantity.value < firstTier.value.min_quantity)

const backorderQuantity = computed(() =>
  typeof onHand.value === 'number' && quantity.value > onHand.value ? quantity.value - Math.max(0, onHand.value) : 0,
)

const tierRows = computed(() => {
  const tiers = pricing.value.tiers ?? []
  const rows = []

  if (typeof pricing.value.base_price === 'number' && tiers.length && tiers[0].min_quantity > 1) {
    rows.push({ from: 1, to: tiers[0].min_quantity - 1, price: pricing.value.base_price })
  }

  tiers.forEach((tier, index) => {
    const next = tiers[index + 1]
    rows.push({ from: tier.min_quantity, to: next ? next.min_quantity - 1 : null, price: tier.unit_price })
  })

  return rows.map((row) => ({
    ...row,
    active: quantity.value >= row.from && (row.to === null || quantity.value <= row.to),
  }))
})

const setQuantity = (value) => {
  quantity.value = normalizeQuantity(value, minQuantity.value)
}

// Live update while typing so the price and total change instantly. The
// minimum order is only enforced when the field is left (or on add), so
// typing "30" with a minimum of 5 isn't fought at the "3".
const onQuantityInput = (event) => {
  const typed = Math.floor(Number(event.target.value))

  if (event.target.value !== '' && Number.isFinite(typed) && typed >= 1) {
    quantity.value = typed
  }
}

const belowMinimum = computed(() => quantity.value < minQuantity.value)

const cartStore = useCartStore()
const added = ref(null)
let feedbackTimer

const addToCart = () => {
  // A quantity typed below the minimum is raised to it before adding.
  setQuantity(quantity.value)

  if (!canBuy.value) {
    return
  }

  cartStore.addItem({
    id: props.product.id,
    name: props.product.name,
    sku: props.product.sku,
    slug: props.product.slug,
    url: props.product.url,
    unit_of_measure: unit.value,
    minimum_order_quantity: minQuantity.value,
    pricing: pricing.value,
    price: unitPrice.value,
  }, quantity.value)

  added.value = quantity.value
  clearTimeout(feedbackTimer)
  feedbackTimer = setTimeout(() => { added.value = null }, 6000)
}

onBeforeUnmount(() => clearTimeout(feedbackTimer))
</script>

<template>
  <section aria-labelledby="buy-box-heading" class="rounded-lg border border-st-line bg-st-bg p-4 sm:p-5">
    <h2 id="buy-box-heading" class="sr-only">Order this product</h2>

    <!-- Quantity price breaks -->
    <div v-if="tierRows.length" class="mb-4">
      <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-st-muted">Quantity pricing</h3>
      <table class="w-full text-sm">
        <thead class="sr-only">
          <tr><th scope="col">Quantity</th><th scope="col">Price per {{ unit }}</th></tr>
        </thead>
        <tbody class="divide-y divide-st-line">
          <tr v-for="row in tierRows" :key="row.from" :class="row.active ? 'font-semibold text-st-ink' : 'text-st-muted'">
            <td class="st-num py-1.5">
              {{ row.to === null ? `${row.from.toLocaleString()}+` : `${row.from.toLocaleString()}–${row.to.toLocaleString()}` }}
              <span v-if="row.active" class="sr-only">(your quantity)</span>
            </td>
            <td class="st-num py-1.5 text-right">{{ currency.format(row.price) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Quantity -->
    <label for="buy-quantity" class="block text-sm font-semibold text-st-ink">Quantity ({{ unit }})</label>
    <div class="mt-2 flex items-center gap-2">
      <button
        type="button"
        class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="quantity <= minQuantity"
        aria-label="Decrease quantity"
        @click="setQuantity(quantity - 1)"
      >
        &minus;
      </button>
      <input
        id="buy-quantity"
        :value="quantity"
        type="number"
        :min="minQuantity"
        inputmode="numeric"
        aria-describedby="buy-quantity-help"
        class="st-num h-12 w-28 rounded-md border border-st-line bg-st-panel px-2 text-center text-lg font-semibold text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"
        @input="onQuantityInput"
        @change="setQuantity($event.target.value); $event.target.value = quantity"
      />
      <button
        type="button"
        class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action"
        aria-label="Increase quantity"
        @click="setQuantity(quantity + 1)"
      >
        +
      </button>
    </div>
    <div id="buy-quantity-help" class="mt-2 space-y-1 text-sm text-st-muted">
      <p v-if="minQuantity > 1" :class="{ 'font-semibold text-st-warn': belowMinimum }">Minimum order: {{ minQuantity.toLocaleString() }} {{ unit }}</p>
      <p v-if="backorderQuantity > 0">
        {{ backorderQuantity.toLocaleString() }} will be backordered<span v-if="product.stock?.lead_time_days"> (ships in about {{ product.stock.lead_time_days }} {{ product.stock.lead_time_days === 1 ? 'day' : 'days' }})</span>.
      </p>
      <p v-if="belowFirstTier">Priced from {{ firstTier.min_quantity.toLocaleString() }} {{ unit }}. Increase the quantity or request a quote.</p>
    </div>

    <!-- Line total -->
    <div v-if="lineTotal !== null" class="mt-4 flex items-baseline justify-between border-t border-st-line pt-4">
      <span class="text-sm text-st-muted">
        {{ quantity.toLocaleString() }} × {{ currency.format(unitPrice) }}
      </span>
      <span class="st-num text-xl font-bold text-st-ink">{{ currency.format(lineTotal) }}</span>
    </div>

    <!-- Actions -->
    <div class="mt-4 space-y-3">
      <button
        v-if="canBuy"
        type="button"
        class="min-h-12 w-full rounded-md bg-st-fill px-6 py-3 text-base font-semibold text-white transition-colors hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2"
        @click="addToCart"
      >
        Add to Cart
      </button>
      <button
        type="button"
        class="min-h-12 w-full rounded-md px-6 py-3 text-base font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2"
        :class="canBuy ? 'border border-st-line text-st-ink hover:bg-st-sunk' : 'bg-st-fill text-white hover:bg-st-fill/85'"
        @click="emit('request-quote')"
      >
        Request a Quote
      </button>
    </div>

    <p aria-live="polite" class="mt-3 min-h-5 text-center text-sm">
      <template v-if="added">
        <span class="font-semibold text-st-ok">Added {{ added.toLocaleString() }} to cart</span>
        ·
        <Link :href="route('store.cart')" class="font-semibold text-st-action hover:underline">View cart</Link>
      </template>
    </p>
  </section>
</template>
