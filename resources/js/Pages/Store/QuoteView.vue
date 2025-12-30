<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { useQuoteStore } from '@/Stores/useQuoteStore'

const quoteStore = useQuoteStore()
const page = usePage()

const props = defineProps({
  locations: {
    type: Array,
    default: () => [],
  },
})

const locations = computed(() => props.locations ?? [])

const hasItems = computed(() => quoteStore.items.length > 0)
const itemCount = computed(() => quoteStore.itemCount.value)

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
})

const formatCurrency = (amount) => currencyFormatter.format(Number(amount) || 0)

const totalDisplay = computed(() => formatCurrency(quoteStore.total.value))

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
  quoteStore.items.forEach(item => {
    const locationId = item.location_id || 0 // Use 0 for "Main Store" if location_id is null
    if (!groups[locationId]) {
      groups[locationId] = []
    }
    groups[locationId].push(item)
  })
  return groups
})

const handleRemove = (id) => quoteStore.removeItem(id)
const handleClear = () => quoteStore.clearQuote()

const generating = ref(false)
const downloadMessage = ref(null)
const downloadError = ref(null)
const lastQuoteNumber = ref(null)
const lastQuoteId = ref(null)
const showDeliveryModal = ref(false)
const emailInput = ref(page.props?.auth?.user?.email ?? '')
const emailError = ref(null)

const emailIsValid = computed(() => {
  if (!emailInput.value) {
    return false
  }

  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)
})

watch(emailInput, () => {
  emailError.value = null
})

const openDeliveryModal = () => {
  if (!hasItems.value || generating.value) {
    return
  }

  emailError.value = null
  showDeliveryModal.value = true
}

const closeDeliveryModal = () => {
  showDeliveryModal.value = false
}

const buildPayload = (delivery) => {
  const user = page.props?.auth?.user
  const baseCustomer = user
    ? {
        name: user.name,
        email: user.email,
      }
    : {}

  const customer = {
    ...baseCustomer,
    email: delivery === 'email' ? emailInput.value : baseCustomer.email,
  }

  return {
    delivery,
    customer,
    items: quoteStore.items.map((item) => ({
      id: item.id,
      product_id: item.productId ?? item.id ?? null,
      name: item.name,
      quantity: item.quantity,
      price: item.price,
      slug: item.slug,
      location_id: item.location_id ?? null,
    })),
    total: quoteStore.total.value,
    tax: 0,
  }
}

const downloadPdf = (data, filename) => {
  const blob = new Blob([data], { type: 'application/pdf' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

const generateQuote = async (delivery) => {
  if (generating.value) {
    return
  }

  generating.value = true
  downloadError.value = null
  downloadMessage.value = null

  try {
    const config = delivery === 'download' ? { responseType: 'blob' } : undefined
    const response = await axios.post('/api/v1/store/quote', buildPayload(delivery), config)

    if (delivery === 'download') {
      const disposition = response.headers['content-disposition'] || ''
      const matches = disposition.match(/filename="(.+)"/)
      const filename = matches?.[1] ?? `Quote-${Date.now()}.pdf`
      const quoteNumber = response.headers['x-quote-number'] ?? null
      const quoteId = response.headers['x-quote-id'] ?? null

      downloadPdf(response.data, filename)
      syncQuoteMeta({ quote_id: quoteId, quote_number: quoteNumber })
      downloadMessage.value = quoteNumber ? `Quote ${quoteNumber} downloaded ✓` : 'Quote downloaded ✓'
    } else {
      syncQuoteMeta(response.data)
      downloadMessage.value =
        response.data?.quote_number !== undefined
          ? `Quote ${response.data.quote_number} emailed ✓`
          : response.data?.message ?? 'Quote emailed ✓'
    }
  } catch (error) {
    console.error('Failed to process quote', error)
    downloadError.value = 'Unable to generate quote. Please try again.'
  } finally {
    generating.value = false
  }
}

const syncQuoteMeta = (payload) => {
  if (!payload) {
    return
  }

  lastQuoteId.value = payload.quote_id ?? payload.id ?? null
  lastQuoteNumber.value = payload.quote_number ?? payload.number ?? null
  quoteStore.clearQuote()
}

const handleDeliverySelection = async (mode) => {
  if (mode === 'email' && !emailIsValid.value) {
    emailError.value = 'Enter a valid email to send the quote.'
    return
  }

  closeDeliveryModal()
  await generateQuote(mode)
}
</script>

<template>
  <Head title="Quote" />

  <AuthenticatedLayout>
    <div class="bg-gray-50 min-h-screen">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Quote Summary</p>
            <h1 class="text-2xl font-semibold text-gray-900">Current Quote</h1>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex flex-col items-end gap-1">
              <button
                type="button"
                class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                :disabled="!hasItems || generating"
                @click="openDeliveryModal"
              >
                <span v-if="generating">Generating…</span>
                <span v-else>Generate Quote</span>
              </button>
              <p v-if="downloadMessage" class="text-xs text-green-600">{{ downloadMessage }}</p>
              <p v-else-if="downloadError" class="text-xs text-red-600">{{ downloadError }}</p>
            </div>
            <button
              type="button"
              class="px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-60"
              :disabled="!hasItems"
              @click="handleClear"
            >
              Clear All
            </button>
          </div>
        </div>

        <div v-if="lastQuoteNumber" class="rounded-lg border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-800">
          ✅ Quote {{ lastQuoteNumber }} has been created.
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Items</p>
              <p class="text-lg font-semibold text-gray-900">{{ itemCount }} total</p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-500">Quote Total</p>
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
                      @click="quoteStore.decrementQuantity(item.id)"
                    >
                      &minus;
                    </button>
                    <span class="w-8 text-center font-semibold text-gray-900">{{ item.quantity }}</span>
                    <button
                      type="button"
                      class="px-2 py-1 border border-gray-300 rounded text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                      @click="quoteStore.incrementQuantity(item.id)"
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
                    @change="quoteStore.updateItemLocation(item.id, item.location_id)"
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
            <p class="font-medium text-gray-700">Your quote is empty.</p>
            <p class="mt-2">Browse the catalog and add items to build a request.</p>
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
    <div
      v-if="showDeliveryModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Deliver Quote</p>
            <h2 class="text-lg font-semibold text-gray-900">Choose how you want to receive the PDF</h2>
          </div>
          <button
            type="button"
            class="text-sm text-gray-500 hover:text-gray-900"
            @click="closeDeliveryModal"
            aria-label="Close"
          >
            ✕
          </button>
        </div>

        <div class="mt-4 space-y-4">
          <div>
            <label for="quote-email" class="text-sm font-semibold text-gray-700">Email address</label>
            <input
              id="quote-email"
              v-model="emailInput"
              type="email"
              placeholder="you@example.com"
              class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900"
              autocomplete="email"
            />
            <p v-if="emailError" class="pt-1 text-xs text-red-600">
              {{ emailError }}
            </p>
            <button
              type="button"
              class="mt-3 w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="generating"
              @click="handleDeliverySelection('email')"
            >
              Email Quote
            </button>
          </div>

          <div class="border-t border-dashed border-gray-200 pt-4">
            <button
              type="button"
              class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="generating"
              @click="handleDeliverySelection('download')"
            >
              Download PDF
            </button>
          </div>

          <button
            type="button"
            class="w-full rounded-md px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-900"
            @click="closeDeliveryModal"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
