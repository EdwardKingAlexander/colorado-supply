<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import StoreLayout from '@/Layouts/StoreLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import InputError from '@/Components/InputError.vue'
import PickupDetails from '@/Components/Store/PickupDetails.vue'
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

  <StoreLayout>
    <div class="bg-st-bg min-h-screen">
      <div class="mobile-page-gutter mx-auto max-w-5xl space-y-6 py-6 sm:py-8 lg:px-8">
        <div>
          <h1 class="st-display text-2xl font-extrabold text-st-ink sm:text-3xl">Order #{{ order.order_number }}</h1>
        </div>

        <div v-if="isPaid" class="rounded-lg border border-st-ok/30 bg-st-ok/10 p-4 text-base leading-6 text-st-ok" role="status">
          This order has already been paid.
          <Link :href="route('store.checkout.success', order.id)" class="font-semibold underline">
            View confirmation
          </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 space-y-6">
            <!-- Order Items -->
            <div class="space-y-4 rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6">
              <h2 class="text-lg font-semibold text-st-ink">Order Items</h2>

              <div class="divide-y divide-st-line">
                <div v-for="item in order.items" :key="item.id" class="flex items-start justify-between gap-3 py-3 text-base">
                  <div>
                    <p class="font-medium text-st-ink">{{ item.name }}</p>
                    <p class="text-sm text-st-muted">Qty {{ item.quantity }} &times; {{ formatCurrency(item.unit_price) }}</p>
                  </div>
                  <p class="font-semibold text-st-ink">{{ formatCurrency(item.line_total) }}</p>
                </div>
              </div>

              <div class="border-t border-st-line pt-4 flex items-center justify-between">
                <p class="text-sm font-semibold text-st-ink/85">Order Total</p>
                <p class="text-xl font-bold text-st-ink">{{ formatCurrency(order.grand_total) }}</p>
              </div>
            </div>

            <!-- Addresses -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6">
                <h3 class="mb-2 text-base font-semibold text-st-ink">Billing Address</h3>
                <p class="whitespace-pre-line text-base leading-6 text-st-muted">{{ formatAddress(order.billing_address) }}</p>
              </div>
              <PickupDetails v-if="order.pickup" :pickup="order.pickup" />
              <div v-else class="rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6">
                <h3 class="mb-2 text-base font-semibold text-st-ink">Shipping Address</h3>
                <p class="whitespace-pre-line text-base leading-6 text-st-muted">{{ formatAddress(order.shipping_address) }}</p>
              </div>
            </div>
          </div>

          <!-- Payment -->
          <div class="lg:col-span-1">
            <div class="space-y-4 rounded-lg border border-st-line bg-st-panel p-4 shadow-sm sm:p-6 lg:sticky lg:top-20">
              <h2 class="text-lg font-semibold text-st-ink">Choose a Payment Method</h2>

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

                <p class="text-sm leading-5 text-st-muted">
                  Google Pay and Apple Pay are available automatically on the
                  Stripe payment page when supported by your browser and
                  device.
                </p>

                <PrimaryButton
                  type="button"
                  class="w-full justify-center bg-[#ffc439] hover:bg-[#f0b932] text-st-ink focus:bg-[#f0b932] focus:ring-[#ffc439] active:bg-[#f0b932]"
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
  </StoreLayout>
</template>
