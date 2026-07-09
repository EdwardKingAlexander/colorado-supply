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
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Cart Summary</p>
            <h1 class="text-2xl font-semibold text-gray-900">Your Cart</h1>
          </div>
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-60"
              :disabled="!hasItems"
              @click="handleClear"
            >
              Clear All
            </button>
            <Link
              href="/store/checkout"
              class="px-4 py-2 text-sm font-semibold text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
              :class="{ 'pointer-events-none opacity-60': !hasItems }"
              :tabindex="!hasItems ? -1 : undefined"
              :aria-disabled="!hasItems"
            >
              Proceed to Checkout
            </Link>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
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
              <div class="px-6 py-2 bg-gray-50 text-sm font-semibold text-gray-700 col-span-full">
                {{ locationNameById[locationId] }}
              </div>
              <div
                v-for="item in group"
                :key="item.id"
                class="px-6 py-4 grid grid-cols-1 gap-3 md:grid-cols-12 md:items-center text-sm text-gray-800"
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
                      class="px-2 py-1 border border-gray-300 rounded text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="item.quantity <= 1"
                      @click="cartStore.decrementQuantity(item.id)"
                    >
                      &minus;
                    </button>
                    <span class="w-8 text-center font-semibold text-gray-900">{{ item.quantity }}</span>
                    <button
                      type="button"
                      class="px-2 py-1 border border-gray-300 rounded text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
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
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
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
                    class="text-xs font-semibold text-gray-500 hover:text-gray-900"
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
              class="inline-flex items-center justify-center mt-6 px-5 py-2 text-sm font-semibold text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Return to Store
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
