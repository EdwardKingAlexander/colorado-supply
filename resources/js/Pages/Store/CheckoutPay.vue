<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import InputError from '@/Components/InputError.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
})

const currencyFormatter = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
})

const formatCurrency = (amount) => currencyFormatter.format(Number(amount) || 0)

const isPaid = computed(() => props.order.payment_status === 'paid')

const formatAddress = (address) => {
  if (!address) {
    return ''
  }

  return [address.line1, address.line2, [address.city, address.state, address.postal_code].filter(Boolean).join(', '), address.country]
    .filter(Boolean)
    .join('\n')
}

const redirecting = ref(false)
const cardError = ref(null)

const payWithCard = async () => {
  if (redirecting.value) {
    return
  }

  redirecting.value = true
  cardError.value = null

  try {
    const response = await axios.post(`/api/v1/orders/${props.order.id}/checkout`)

    window.location.href = response.data.checkout_url
  } catch (error) {
    if (error.response?.status === 422) {
      cardError.value = error.response.data.errors?.order?.[0] ?? 'Unable to start checkout. Please try again.'
    } else {
      cardError.value = 'Unable to start checkout. Please try again.'
    }
    redirecting.value = false
  }
}

const redirectingPaypal = ref(false)
const paypalError = ref(null)

const payWithPaypal = async () => {
  if (redirectingPaypal.value) {
    return
  }

  redirectingPaypal.value = true
  paypalError.value = null

  try {
    const response = await axios.post(`/api/v1/orders/${props.order.id}/checkout/paypal`)

    window.location.href = response.data.approve_url
  } catch (error) {
    if (error.response?.status === 422) {
      paypalError.value = error.response.data.errors?.order?.[0] ?? 'Unable to start PayPal checkout. Please try again.'
    } else {
      paypalError.value = 'Unable to start PayPal checkout. Please try again.'
    }
    redirectingPaypal.value = false
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
          <h1 class="text-2xl font-semibold text-gray-900">Order #{{ order.order_number }}</h1>
        </div>

        <div v-if="isPaid" class="bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800">
          This order has already been paid.
          <Link :href="route('store.checkout.success', order.id)" class="font-semibold underline">
            View confirmation
          </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
              <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>

              <div class="divide-y divide-gray-100">
                <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between py-2 text-sm">
                  <div>
                    <p class="font-medium text-gray-900">{{ item.name }}</p>
                    <p class="text-xs text-gray-500">Qty {{ item.quantity }} &times; {{ formatCurrency(item.unit_price) }}</p>
                  </div>
                  <p class="font-semibold text-gray-900">{{ formatCurrency(item.line_total) }}</p>
                </div>
              </div>

              <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-700">Order Total</p>
                <p class="text-xl font-bold text-gray-900">{{ formatCurrency(order.grand_total) }}</p>
              </div>
            </div>

            <!-- Addresses -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Billing Address</h3>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ formatAddress(order.billing_address) }}</p>
              </div>
              <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Shipping Address</h3>
                <p class="text-sm text-gray-600 whitespace-pre-line">{{ formatAddress(order.shipping_address) }}</p>
              </div>
            </div>
          </div>

          <!-- Payment -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4 sticky top-24">
              <h2 class="text-lg font-semibold text-gray-900">Choose a Payment Method</h2>

              <div id="payment-methods" class="space-y-3">
                <PrimaryButton
                  type="button"
                  class="w-full justify-center"
                  :disabled="redirecting || isPaid"
                  @click="payWithCard"
                >
                  <span v-if="redirecting">Redirecting to Stripe…</span>
                  <span v-else>Pay with Card</span>
                </PrimaryButton>

                <InputError :message="cardError" />

                <p class="text-xs text-gray-500">
                  Google Pay and Apple Pay are available automatically on the
                  Stripe payment page when supported by your browser and
                  device.
                </p>

                <PrimaryButton
                  type="button"
                  class="w-full justify-center bg-[#ffc439] hover:bg-[#f0b932] text-gray-900 focus:bg-[#f0b932] focus:ring-[#ffc439] active:bg-[#f0b932]"
                  :disabled="redirectingPaypal || isPaid"
                  @click="payWithPaypal"
                >
                  <span v-if="redirectingPaypal">Redirecting to PayPal…</span>
                  <span v-else>Pay with PayPal</span>
                </PrimaryButton>

                <InputError :message="paypalError" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
