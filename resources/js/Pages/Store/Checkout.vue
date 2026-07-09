<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
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
})

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

onMounted(() => {
  if (!hasItems.value) {
    router.visit(route('store.cart'))
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
    billing_address: form.billing_address,
    shipping_same_as_billing: form.shipping_same_as_billing,
    shipping_address: form.shipping_same_as_billing ? null : form.shipping_address,
  }

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

  <AuthenticatedLayout>
    <div class="bg-gray-50 min-h-screen">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
          <p class="text-sm text-gray-500 uppercase tracking-wide">Checkout</p>
          <h1 class="text-2xl font-semibold text-gray-900">Shipping & Billing Details</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <form class="lg:col-span-2 space-y-6" @submit.prevent="submit">
            <!-- Contact Info -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
              <h2 class="text-lg font-semibold text-gray-900">Contact Information</h2>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <InputLabel for="contact_name" value="Full Name" />
                  <TextInput id="contact_name" v-model="form.contact_name" class="mt-1 block w-full" required />
                  <InputError class="mt-1" :message="fieldError('contact_name')" />
                </div>
                <div>
                  <InputLabel for="contact_email" value="Email" />
                  <TextInput id="contact_email" type="email" v-model="form.contact_email" class="mt-1 block w-full" required />
                  <InputError class="mt-1" :message="fieldError('contact_email')" />
                </div>
                <div>
                  <InputLabel for="contact_phone" value="Phone (optional)" />
                  <TextInput id="contact_phone" v-model="form.contact_phone" class="mt-1 block w-full" />
                  <InputError class="mt-1" :message="fieldError('contact_phone')" />
                </div>
                <div>
                  <InputLabel for="company_name" value="Company (optional)" />
                  <TextInput id="company_name" v-model="form.company_name" class="mt-1 block w-full" />
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
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                ></textarea>
                <InputError class="mt-1" :message="fieldError('notes')" />
              </div>
            </div>

            <!-- Billing Address -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
              <h2 class="text-lg font-semibold text-gray-900">Billing Address</h2>

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
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Shipping Address</h2>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" v-model="form.shipping_same_as_billing" class="rounded border-gray-300" />
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

            <div class="flex items-center justify-between">
              <Link :href="route('store.cart')" class="text-sm text-gray-600 hover:text-gray-900">
                &larr; Back to Cart
              </Link>
              <PrimaryButton :disabled="submitting || !hasItems">
                <span v-if="submitting">Placing Order…</span>
                <span v-else>Place Order</span>
              </PrimaryButton>
            </div>
          </form>

          <!-- Order Summary -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4 sticky top-24">
              <h2 class="text-lg font-semibold text-gray-900">Order Summary</h2>

              <div class="space-y-4 divide-y divide-gray-100">
                <template v-for="(group, locationId) in groupedItems" :key="locationId">
                  <div class="pt-2 first:pt-0">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                      {{ locationNameById[locationId] }}
                    </p>
                    <div v-for="item in group" :key="item.id" class="flex items-center justify-between text-sm py-1">
                      <div>
                        <p class="font-medium text-gray-900">{{ item.name }}</p>
                        <p class="text-xs text-gray-500">Qty {{ item.quantity }} &times; {{ formatCurrency(item.price) }}</p>
                      </div>
                      <p class="font-semibold text-gray-900">{{ formatCurrency(item.price * item.quantity) }}</p>
                    </div>
                  </div>
                </template>
              </div>

              <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700">Total</p>
                <p class="text-xl font-bold text-gray-900">{{ totalDisplay }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
