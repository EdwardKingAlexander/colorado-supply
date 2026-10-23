<script setup>
import StoreLayout from '@/Layouts/StoreLayout.vue'
import PickupDetails from '@/Components/Store/PickupDetails.vue'
import PushOptIn from '@/Components/Notifications/PushOptIn.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

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

const totalDisplay = computed(() => currencyFormatter.format(Number(props.order.grand_total) || 0))

const isPaid = computed(() => props.order.payment_status === 'paid')
</script>

<template>
  <StoreLayout>
    <Head>
      <title>Payment | Colorado Supply & Procurement</title>
    </Head>

    <div class="mobile-page-gutter mx-auto max-w-xl py-10 sm:py-16">
      <div class="st-panel p-6 sm:p-8">
        <p class="inline-flex items-center gap-2 text-sm font-semibold" :class="isPaid ? 'text-st-ok' : 'text-st-warn'">
          <span class="h-2 w-2 rounded-full" :class="isPaid ? 'bg-st-ok' : 'bg-st-warn'" aria-hidden="true" />
          {{ isPaid ? 'Payment confirmed' : 'Confirming payment' }}
        </p>
        <h1 class="st-display mt-2 text-2xl font-extrabold text-st-ink sm:text-3xl">
          Thank you for your order
        </h1>
        <p class="mt-2 text-base leading-6 text-st-muted">
          Order <span class="st-num font-semibold text-st-ink">{{ order.order_number }}</span>, <span class="st-num">{{ totalDisplay }}</span>.
          <template v-if="!isPaid">We'll email you once the payment clears.</template>
        </p>

        <PickupDetails v-if="order.pickup" :pickup="order.pickup" class="mt-6" />

        <div v-if="order.pickup && $page.props.notificationBell?.pushKey" class="mt-6">
          <PushOptIn compact />
        </div>

        <div class="mt-6 grid gap-2 sm:grid-cols-2">
          <Link :href="route('store.index')" class="st-btn-primary">Continue shopping</Link>
          <Link :href="route('dashboard')" class="st-btn-secondary">View account</Link>
        </div>
      </div>
    </div>
  </StoreLayout>
</template>
