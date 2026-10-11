<script setup>
import { computed } from 'vue'
import StoreLayout from '@/Layouts/StoreLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { useCartStore } from '@/Stores/useCartStore'

const cartStore = useCartStore()

const props = defineProps({
  locations: {
    type: Array,
    default: () => [],
  },
})

const locations = computed(() => props.locations ?? [])

const hasItems = computed(() => cartStore.items.length > 0)
const itemCount = computed(() => cartStore.itemCount.value)

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
})

const formatCurrency = (amount) => currencyFormatter.format(Number(amount) || 0)

const totalDisplay = computed(() => formatCurrency(cartStore.total.value))

const locationNameById = computed(() =>
  locations.value.reduce(
    (acc, location) => {
      acc[location.id] = location.name
      return acc
    },
    { 0: 'Main Store' },
  ),
)

const groupedItems = computed(() => {
  const groups = {}
  cartStore.items.forEach(item => {
    const locationId = item.location_id || 0 // Use 0 for "Main Store" if location_id is null
    if (!groups[locationId]) {
      groups[locationId] = []
    }
    groups[locationId].push(item)
  })
  return groups
})

// Older cart lines may predate stored URLs; rebuild the canonical one from id + slug.
const productUrl = (item) => item.url
  ?? route('store.product', { product: item.productId ?? item.id, slug: item.slug ?? undefined })

const handleRemove = (id) => cartStore.removeItem(id)
const handleClear = () => cartStore.clearCart()
</script>

<template>
  <Head title="Cart" />

  <StoreLayout>
    <div class="bg-st-bg min-h-screen">
      <div class="mobile-page-gutter mx-auto max-w-5xl space-y-6 py-6 sm:py-8 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="st-display text-2xl font-extrabold text-st-ink sm:text-3xl">Your cart</h1>
          </div>
          <div class="grid grid-cols-1 gap-2 xs:grid-cols-2 sm:flex sm:items-center sm:gap-3">
            <button
              type="button"
              class="inline-flex min-h-12 items-center justify-center rounded-md border border-st-line px-4 py-3 text-base font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action disabled:opacity-60"
              :disabled="!hasItems"
              @click="handleClear"
            >
              Clear All
            </button>
            <Link
              href="/store/checkout"
              class="inline-flex min-h-12 items-center justify-center rounded-md bg-st-fill px-4 py-3 text-base font-semibold text-white transition-colors hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
              :class="{ 'pointer-events-none opacity-60': !hasItems }"
              :tabindex="!hasItems ? -1 : undefined"
              :aria-disabled="!hasItems"
            >
              Proceed to Checkout
            </Link>
          </div>
        </div>

        <div class="bg-st-panel rounded-lg shadow-sm border border-st-line">
          <div class="flex items-center justify-between border-b border-st-line px-4 py-4 sm:px-6">
            <div>
              <p class="text-sm text-st-muted">Items</p>
              <p class="text-lg font-semibold text-st-ink">{{ itemCount }} total</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-st-muted">Cart Total</p>
              <p class="text-2xl font-semibold text-st-ink">{{ totalDisplay }}</p>
            </div>
          </div>

          <div v-if="hasItems" class="divide-y divide-st-line">
            <div class="hidden md:grid md:grid-cols-12 px-6 py-3 text-sm font-medium text-st-muted">
              <div class="col-span-4">Item</div>
              <div class="col-span-2 text-right">Unit Price</div>
              <div class="col-span-2 text-right">Quantity</div>
              <div class="col-span-2 text-right">Subtotal</div>
              <div class="col-span-1 text-right">Location</div>
              <div class="col-span-1 text-right"> </div>
            </div>

            <template v-for="(group, locationId) in groupedItems" :key="locationId">
              <div class="col-span-full bg-st-bg px-4 py-3 text-base font-semibold text-st-ink/85 sm:px-6">
                {{ locationNameById[locationId] }}
              </div>
              <div
                v-for="item in group"
                :key="item.id"
                class="grid grid-cols-1 gap-4 px-4 py-5 text-base text-st-ink sm:px-6 md:grid-cols-12 md:items-center md:text-sm"
              >
                <div class="md:col-span-4">
                  <Link
                    :href="productUrl(item)"
                    class="font-medium text-st-ink hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                  >
                    {{ item.name }}
                  </Link>
                  <p v-if="item.sku" class="text-xs text-st-muted mt-1">
                    SKU: {{ item.sku }}
                  </p>
                  <p v-if="item.minQuantity > 1" class="text-xs text-st-muted mt-1">
                    Minimum order: {{ item.minQuantity }}
                  </p>
                </div>
                <div class="md:col-span-2 text-st-muted md:text-right">
                  {{ formatCurrency(item.price) }}
                </div>
                <div class="md:col-span-2 md:text-right">
                  <div class="flex items-center gap-2 md:justify-end">
                    <button
                      type="button"
                      class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action disabled:cursor-not-allowed disabled:opacity-50"
                      :disabled="item.quantity <= (item.minQuantity ?? 1)"
                      :aria-label="`Decrease quantity of ${item.name}`"
                      @click="cartStore.decrementQuantity(item.id)"
                    >
                      &minus;
                    </button>
                    <label :for="`cart-qty-${item.id}`" class="sr-only">Quantity of {{ item.name }}</label>
                    <input
                      :id="`cart-qty-${item.id}`"
                      :value="item.quantity"
                      type="number"
                      :min="item.minQuantity ?? 1"
                      inputmode="numeric"
                      class="st-num h-12 w-16 rounded-md border border-st-line bg-st-sunk px-1 text-center text-base font-semibold text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"
                      @change="cartStore.setQuantity(item.id, $event.target.value); $event.target.value = item.quantity"
                    />
                    <button
                      type="button"
                      :aria-label="`Increase quantity of ${item.name}`"
                      class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-st-line text-xl font-semibold text-st-ink/85 transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action"
                      @click="cartStore.incrementQuantity(item.id)"
                    >
                      +
                    </button>
                  </div>
                </div>
                <div class="md:col-span-2 font-semibold text-st-ink md:text-right">
                  {{ formatCurrency(item.price * item.quantity) }}
                </div>
                <!-- Location Selector -->
                <div class="md:col-span-1 md:text-right">
                  <select
                    v-model="item.location_id"
                    @change="cartStore.updateItemLocation(item.id, item.location_id)"
                    class="block min-h-12 w-full rounded-md border-st-line text-base shadow-sm focus:border-st-action focus:ring-st-action"
                  >
                    <option :value="null">Main Store</option>
                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                      {{ loc.name }}
                    </option>
                  </select>
                </div>
                <div class="md:col-span-1 md:text-right">
                  <button
                    type="button"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-md px-3 py-2 text-base font-semibold text-red-400 hover:bg-red-500/10 focus:outline-none focus:ring-2 focus:ring-red-600 md:w-auto"
                    @click="handleRemove(item.id)"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </template>
          </div>

          <div v-else class="px-6 py-16 text-center text-sm text-st-muted">
            <p class="font-medium text-st-ink/85">Your cart is empty.</p>
            <p class="mt-2">Browse the catalog and add items to your cart.</p>
            <Link
              :href="route('store.index')"
              class="mt-6 inline-flex min-h-12 items-center justify-center rounded-md border border-st-line px-5 py-3 text-base font-semibold text-st-ink transition-colors hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action"
            >
              Return to Store
            </Link>
          </div>
        </div>
      </div>
    </div>
  </StoreLayout>
</template>
