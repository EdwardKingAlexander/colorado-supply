<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import StoreLayout from '@/Layouts/StoreLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useCartStore } from '@/Stores/useCartStore'

const cartStore = useCartStore()

const props = defineProps({
  locations: {
    type: Array,
    default: () => [],
  },
  contact: {
    type: Object,
    default: () => ({}),
  },
  pickupLocations: {
    type: Array,
    default: () => [],
  },
})

const pickupLocations = computed(() => props.pickupLocations ?? [])
const canWillCall = computed(() => pickupLocations.value.length > 0)

// Remember the buyer's last fulfillment choice on this device only.
const FULFILLMENT_KEY = 'colorado-supply.checkout.fulfillment'
const readSavedFulfillment = () => {
  try {
    return JSON.parse(localStorage.getItem(FULFILLMENT_KEY) ?? 'null')
  } catch {
    return null
  }
}
const saveFulfillment = () => {
  try {
    localStorage.setItem(FULFILLMENT_KEY, JSON.stringify({
      method: form.fulfillment_method,
      pickup_location_id: form.pickup_location_id,
    }))
  } catch {
    // Storage blocked (private mode): the choice just isn't remembered.
  }
}

const locations = computed(() => props.locations ?? [])

const hasItems = computed(() => cartStore.items.length > 0)

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
    const locationId = item.location_id || 0
    if (!groups[locationId]) {
      groups[locationId] = []
    }
    groups[locationId].push(item)
  })
  return groups
})

const form = reactive({
  contact_name: props.contact?.name ?? '',
  contact_email: props.contact?.email ?? '',
  contact_phone: '',
  company_name: '',
  po_number: '',
  job_number: '',
  notes: '',
  fulfillment_method: 'ship',
  pickup_location_id: null,
  billing_address: {
    line1: '',
    line2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'US',
  },
  shipping_same_as_billing: true,
  shipping_address: {
    line1: '',
    line2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'US',
  },
})

const errors = ref({})
const submitting = ref(false)

const fieldError = (path) => errors.value[path]?.[0]

const isWillCall = computed(() => form.fulfillment_method === 'will_call')

const chooseWillCall = () => {
  form.fulfillment_method = 'will_call'
  if (!form.pickup_location_id && pickupLocations.value.length === 1) {
    form.pickup_location_id = pickupLocations.value[0].id
  }
}

onMounted(() => {
  if (!hasItems.value) {
    router.visit(route('store.cart'))
    return
  }

  const saved = readSavedFulfillment()
  if (saved?.method === 'will_call' && canWillCall.value) {
    chooseWillCall()
    if (pickupLocations.value.some((location) => location.id === saved.pickup_location_id)) {
      form.pickup_location_id = saved.pickup_location_id
    }
  }
})

const submit = async () => {
  if (submitting.value || !hasItems.value) {
    return
  }

  submitting.value = true
  errors.value = {}

  const payload = {
    items: cartStore.items.map((item) => ({
      id: item.id,
      product_id: item.productId ?? item.id ?? null,
      name: item.name,
      quantity: item.quantity,
      price: item.price,
      slug: item.slug,
      location_id: item.location_id ?? null,
    })),
    contact_name: form.contact_name,
    contact_email: form.contact_email,
    contact_phone: form.contact_phone || null,
    company_name: form.company_name || null,
    po_number: form.po_number || null,
    job_number: form.job_number || null,
    notes: form.notes || null,
    fulfillment_method: form.fulfillment_method,
    pickup_location_id: isWillCall.value ? form.pickup_location_id : null,
    billing_address: form.billing_address,
    shipping_same_as_billing: isWillCall.value ? true : form.shipping_same_as_billing,
    shipping_address: isWillCall.value || form.shipping_same_as_billing ? null : form.shipping_address,
  }

  saveFulfillment()

  try {
    const response = await axios.post('/api/v1/store/checkout', payload)

    cartStore.clearCart()
    router.visit(route('store.checkout.pay', { order: response.data.order.id }))
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors ?? {}
    } else {
      console.error('Failed to create order', error)
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Head title="Checkout" />

  <StoreLayout>
    <div class="bg-st-bg min-h-screen">
      <div class="mobile-page-gutter mx-auto max-w-5xl space-y-6 py-6 sm:py-8 lg:px-8">
        <div>
          <h1 class="st-display text-2xl font-extrabold text-st-ink sm:text-3xl">Checkout</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <form class="checkout-form space-y-6 lg:col-span-2" @submit.prevent="submit">
            <!-- Contact Info -->
            <div class="space-y-4 rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6">
              <h2 class="text-lg font-semibold text-st-ink">Contact Information</h2>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <InputLabel for="contact_name" value="Full Name" />
                  <TextInput id="contact_name" v-model="form.contact_name" class="mt-2 block w-full" required autocomplete="name" />
                  <InputError class="mt-1" :message="fieldError('contact_name')" />
                </div>
                <div>
                  <InputLabel for="contact_email" value="Email" />
                  <TextInput id="contact_email" type="email" v-model="form.contact_email" class="mt-2 block w-full" required autocomplete="email" />
                  <InputError class="mt-1" :message="fieldError('contact_email')" />
                </div>
                <div>
                  <InputLabel for="contact_phone" value="Phone (optional)" />
                  <TextInput id="contact_phone" type="tel" inputmode="tel" autocomplete="tel" v-model="form.contact_phone" class="mt-2 block w-full" />
                  <InputError class="mt-1" :message="fieldError('contact_phone')" />
                </div>
                <div>
                  <InputLabel for="company_name" value="Company (optional)" />
                  <TextInput id="company_name" v-model="form.company_name" class="mt-2 block w-full" autocomplete="organization" />
                  <InputError class="mt-1" :message="fieldError('company_name')" />
                </div>
                <div>
                  <InputLabel for="po_number" value="PO Number (optional)" />
                  <TextInput id="po_number" v-model="form.po_number" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('po_number')" />
                </div>
                <div>
                  <InputLabel for="job_number" value="Job Number (optional)" />
                  <TextInput id="job_number" v-model="form.job_number" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('job_number')" />
                </div>
              </div>

              <div>
                <InputLabel for="notes" value="Order Notes (optional)" />
                <textarea
                  id="notes"
                  v-model="form.notes"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-st-line shadow-sm focus:border-st-action focus:ring-st-action"
                ></textarea>
                <InputError class="mt-1" :message="fieldError('notes')" />
              </div>
            </div>

            <!-- Delivery method -->
            <fieldset v-if="canWillCall" class="space-y-4 rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6">
              <legend class="sr-only">How do you want to get your order?</legend>
              <h2 class="text-lg font-semibold text-st-ink" aria-hidden="true">How do you want to get your order?</h2>

              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label
                  class="flex min-h-14 cursor-pointer items-start gap-3 rounded-lg border-2 p-4 focus-within:ring-2 focus-within:ring-st-action"
                  :class="!isWillCall ? 'border-st-action bg-st-action/5' : 'border-st-line hover:border-st-muted'"
                >
                  <input v-model="form.fulfillment_method" type="radio" name="fulfillment_method" value="ship" class="mt-1 h-5 w-5 border-st-line text-st-fill" />
                  <span>
                    <span class="block text-base font-semibold text-st-ink">Ship it</span>
                    <span class="block text-sm text-st-muted">Delivered to your address.</span>
                  </span>
                </label>
                <label
                  class="flex min-h-14 cursor-pointer items-start gap-3 rounded-lg border-2 p-4 focus-within:ring-2 focus-within:ring-st-action"
                  :class="isWillCall ? 'border-st-action bg-st-action/5' : 'border-st-line hover:border-st-muted'"
                >
                  <input
                    :checked="isWillCall"
                    type="radio"
                    name="fulfillment_method"
                    value="will_call"
                    class="mt-1 h-5 w-5 border-st-line text-st-fill"
                    @change="chooseWillCall"
                  />
                  <span>
                    <span class="block text-base font-semibold text-st-ink">Will Call</span>
                    <span class="block text-sm text-st-muted">Pick it up at one of our counters. No shipping.</span>
                  </span>
                </label>
              </div>
              <InputError class="mt-1" :message="fieldError('fulfillment_method')" />

              <div v-if="isWillCall" role="radiogroup" aria-labelledby="pickup-location-label" class="space-y-3">
                <p id="pickup-location-label" class="text-base font-semibold text-st-ink">Pickup location</p>
                <label
                  v-for="location in pickupLocations"
                  :key="location.id"
                  class="flex cursor-pointer items-start gap-3 rounded-lg border-2 p-4 focus-within:ring-2 focus-within:ring-st-action"
                  :class="form.pickup_location_id === location.id ? 'border-st-action bg-st-action/5' : 'border-st-line hover:border-st-muted'"
                >
                  <input
                    v-model="form.pickup_location_id"
                    type="radio"
                    name="pickup_location_id"
                    :value="location.id"
                    class="mt-1 h-5 w-5 border-st-line text-st-fill"
                  />
                  <span class="min-w-0 space-y-1">
                    <span class="block text-base font-semibold text-st-ink">{{ location.name }}</span>
                    <span v-if="location.address" class="block text-sm text-st-ink/85">{{ location.address }}</span>
                    <span v-if="location.hours" class="block whitespace-pre-line text-sm text-st-muted">{{ location.hours }}</span>
                    <span v-if="location.phone" class="block text-sm text-st-muted">{{ location.phone }}</span>
                  </span>
                </label>
                <InputError class="mt-1" :message="fieldError('pickup_location_id')" />
                <p class="text-sm text-st-muted">We'll email you when your order is ready. Please wait for that message before coming in.</p>
              </div>
            </fieldset>

            <!-- Billing Address -->
            <div class="space-y-4 rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6">
              <h2 class="text-lg font-semibold text-st-ink">Billing Address</h2>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                  <InputLabel for="billing_line1" value="Address Line 1" />
                  <TextInput id="billing_line1" v-model="form.billing_address.line1" class="mt-1 block w-full" required />
                  <InputError class="mt-1" :message="fieldError('billing_address.line1')" />
                </div>
                <div class="sm:col-span-2">
                  <InputLabel for="billing_line2" value="Address Line 2 (optional)" />
                  <TextInput id="billing_line2" v-model="form.billing_address.line2" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('billing_address.line2')" />
                </div>
                <div>
                  <InputLabel for="billing_city" value="City" />
                  <TextInput id="billing_city" v-model="form.billing_address.city" class="mt-1 block w-full" required />
                  <InputError class="mt-1" :message="fieldError('billing_address.city')" />
                </div>
                <div>
                  <InputLabel for="billing_state" value="State" />
                  <TextInput id="billing_state" v-model="form.billing_address.state" class="mt-1 block w-full" required />
                  <InputError class="mt-1" :message="fieldError('billing_address.state')" />
                </div>
                <div>
                  <InputLabel for="billing_postal_code" value="Postal Code" />
                  <TextInput id="billing_postal_code" v-model="form.billing_address.postal_code" class="mt-1 block w-full" required />
                  <InputError class="mt-1" :message="fieldError('billing_address.postal_code')" />
                </div>
                <div>
                  <InputLabel for="billing_country" value="Country (2-letter code)" />
                  <TextInput id="billing_country" v-model="form.billing_address.country" maxlength="2" class="mt-1 block w-full uppercase" required />
                  <InputError class="mt-1" :message="fieldError('billing_address.country')" />
                </div>
              </div>
            </div>

            <!-- Shipping Address -->
            <div v-if="!isWillCall" class="space-y-4 rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-st-ink">Shipping Address</h2>
                <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-md px-2 text-base text-st-ink/85 hover:bg-st-sunk focus-within:ring-2 focus-within:ring-st-action">
                  <input type="checkbox" v-model="form.shipping_same_as_billing" class="h-5 w-5 rounded border-st-line" />
                  Same as billing address
                </label>
              </div>

              <div v-if="!form.shipping_same_as_billing" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                  <InputLabel for="shipping_line1" value="Address Line 1" />
                  <TextInput id="shipping_line1" v-model="form.shipping_address.line1" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('shipping_address.line1')" />
                </div>
                <div class="sm:col-span-2">
                  <InputLabel for="shipping_line2" value="Address Line 2 (optional)" />
                  <TextInput id="shipping_line2" v-model="form.shipping_address.line2" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('shipping_address.line2')" />
                </div>
                <div>
                  <InputLabel for="shipping_city" value="City" />
                  <TextInput id="shipping_city" v-model="form.shipping_address.city" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('shipping_address.city')" />
                </div>
                <div>
                  <InputLabel for="shipping_state" value="State" />
                  <TextInput id="shipping_state" v-model="form.shipping_address.state" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('shipping_address.state')" />
                </div>
                <div>
                  <InputLabel for="shipping_postal_code" value="Postal Code" />
                  <TextInput id="shipping_postal_code" v-model="form.shipping_address.postal_code" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('shipping_address.postal_code')" />
                </div>
                <div>
                  <InputLabel for="shipping_country" value="Country (2-letter code)" />
                  <TextInput id="shipping_country" v-model="form.shipping_address.country" maxlength="2" class="mt-1 block w-full uppercase" />
                  <InputError class="mt-1" :message="fieldError('shipping_address.country')" />
                </div>
              </div>
            </div>

            <InputError :message="fieldError('items')" />

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
              <Link :href="route('store.cart')" class="inline-flex min-h-12 items-center justify-center rounded-md px-4 py-3 text-base font-semibold text-st-ink/85 hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action">
                &larr; Back to Cart
              </Link>
              <PrimaryButton class="w-full sm:w-auto" :disabled="submitting || !hasItems">
                <span v-if="submitting">Placing Order…</span>
                <span v-else>Place Order</span>
              </PrimaryButton>
            </div>
          </form>

          <!-- Order Summary -->
          <div class="lg:col-span-1">
            <div class="space-y-4 rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6 lg:sticky lg:top-20">
              <h2 class="text-lg font-semibold text-st-ink">Order Summary</h2>

              <div class="space-y-4 divide-y divide-st-line">
                <template v-for="(group, locationId) in groupedItems" :key="locationId">
                  <div class="pt-2 first:pt-0">
                    <p class="mb-2 text-sm font-semibold text-st-muted">
                      {{ locationNameById[locationId] }}
                    </p>
                    <div v-for="item in group" :key="item.id" class="flex items-start justify-between gap-3 py-2 text-base">
                      <div>
                        <p class="font-medium text-st-ink">{{ item.name }}</p>
                        <p class="text-sm text-st-muted">Qty {{ item.quantity }} &times; {{ formatCurrency(item.price) }}</p>
                      </div>
                      <p class="font-semibold text-st-ink">{{ formatCurrency(item.price * item.quantity) }}</p>
                    </div>
                  </div>
                </template>
              </div>

              <div v-if="isWillCall" class="border-t border-st-line pt-4 text-sm">
                <p class="font-semibold text-st-ink/85">Will Call pickup</p>
                <p class="text-st-muted">{{ pickupLocations.find((location) => location.id === form.pickup_location_id)?.name ?? 'Choose a location' }}</p>
              </div>

              <div class="border-t border-st-line pt-4 flex items-center justify-between">
                <p class="text-sm font-semibold text-st-ink/85">Total</p>
                <p class="text-xl font-bold text-st-ink">{{ totalDisplay }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </StoreLayout>
</template>
