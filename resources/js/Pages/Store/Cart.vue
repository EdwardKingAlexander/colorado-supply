<script setup>
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
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

const handleRemove = (id) => cartStore.removeItem(id)
const handleClear = () => cartStore.clearCart()
</script>

<template>
  <Head title="Cart" />

  <AuthenticatedLayout>
    <div class="bg-gray-50 min-h-screen">
      <div class="mobile-page-gutter mx-auto max-w-5xl space-y-6 py-6 sm:py-8 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Cart Summary</p>
            <h1 class="text-2xl font-semibold text-gray-900">Your Cart</h1>
          </div>
          <div class="grid grid-cols-1 gap-2 xs:grid-cols-2 sm:flex sm:items-center sm:gap-3">
            <button
              type="button"
              class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-4 py-3 text-base font-semibold text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-60"
              :disabled="!hasItems"
              @click="handleClear"
            >
              Clear All
            </button>
            <Link
              href="/store/checkout"
              class="inline-flex min-h-12 items-center justify-center rounded-md bg-gray-900 px-4 py-3 text-base font-semibold text-white transition-colors hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
              :class="{ 'pointer-events-none opacity-60': !hasItems }"
              :tabindex="!hasItems ? -1 : undefined"
              :aria-disabled="!hasItems"
            >
              Proceed to Checkout
            </Link>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4 sm:px-6">
            <div>
              <p class="text-sm text-gray-500">Items</p>
              <p class="text-lg font-semibold text-gray-900">{{ itemCount }} total</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-500">Cart Total</p>
              <p class="text-2xl font-semibold text-gray-900">{{ totalDisplay }}</p>
            </div>
          </div>

          <div v-if="hasItems" class="divide-y divide-gray-100">
            <div class="hidden md:grid md:grid-cols-12 px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
              <div class="col-span-4">Item</div>
              <div class="col-span-2 text-right">Unit Price</div>
              <div class="col-span-2 text-right">Quantity</div>
              <div class="col-span-2 text-right">Subtotal</div>
              <div class="col-span-1 text-right">Location</div>
              <div class="col-span-1 text-right"> </div>
            </div>

            <template v-for="(group, locationId) in groupedItems" :key="locationId">
              <div class="col-span-full bg-gray-50 px-4 py-3 text-base font-semibold text-gray-700 sm:px-6">
                {{ locationNameById[locationId] }}
              </div>
              <div
                v-for="item in group"
                :key="item.id"
                class="grid grid-cols-1 gap-4 px-4 py-5 text-base text-gray-800 sm:px-6 md:grid-cols-12 md:items-center md:text-sm"
              >
                <div class="md:col-span-4">
                  <p class="font-medium text-gray-900">{{ item.name }}</p>
                  <p v-if="item.slug" class="text-xs text-gray-500 mt-1">
                    SKU: {{ item.slug }}
                  </p>
                </div>
                <div class="md:col-span-2 text-gray-600 md:text-right">
                  {{ formatCurrency(item.price) }}
                </div>
                <div class="md:col-span-2 md:text-right">
                  <div class="flex items-center gap-2 md:justify-end">
                    <button
                      type="button"
                      class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-gray-300 text-xl font-semibold text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                      :disabled="item.quantity <= 1"
                      @click="cartStore.decrementQuantity(item.id)"
                    >
                      &minus;
                    </button>
                    <span class="w-10 text-center text-base font-semibold text-gray-900">{{ item.quantity }}</span>
                    <button
                      type="button"
                      class="inline-flex h-12 w-12 items-center justify-center rounded-md border border-gray-300 text-xl font-semibold text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      @click="cartStore.incrementQuantity(item.id)"
                    >
                      +
                    </button>
                  </div>
                </div>
                <div class="md:col-span-2 font-semibold text-gray-900 md:text-right">
                  {{ formatCurrency(item.price * item.quantity) }}
                </div>
                <!-- Location Selector -->
                <div class="md:col-span-1 md:text-right">
                  <select
                    v-model="item.location_id"
                    @change="cartStore.updateItemLocation(item.id, item.location_id)"
                    class="block min-h-12 w-full rounded-md border-gray-300 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-md px-3 py-2 text-base font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-600 md:w-auto"
                    @click="handleRemove(item.id)"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </template>
          </div>

          <div v-else class="px-6 py-16 text-center text-sm text-gray-500">
            <p class="font-medium text-gray-700">Your cart is empty.</p>
            <p class="mt-2">Browse the catalog and add items to your cart.</p>
            <Link
              :href="route('store.index')"
              class="mt-6 inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 px-5 py-3 text-base font-semibold text-gray-900 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              Return to Store
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
