<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  order: {
    type: Object,
    required: true,
  },
})

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })

// Ordered milestones for the visual timeline. A step is "done" when the
// order has progressed past it, "current" when it is the latest reached.
const steps = computed(() => {
  const paid = props.order.payment_status.value === 'paid'
  const shipped = ['partially_fulfilled', 'fulfilled'].includes(props.order.fulfillment_status.value)
    || props.order.shipments.length > 0
  const fulfilled = props.order.fulfillment_status.value === 'fulfilled'

  return [
    { label: 'Order placed', detail: props.order.placed_at, done: true },
    { label: 'Payment received', detail: props.order.payment_status.label, done: paid },
    { label: 'Shipped', detail: props.order.shipments[0]?.carrier ?? null, done: shipped },
    { label: 'Order complete', detail: null, done: fulfilled },
  ]
})

const isCancelled = computed(() => props.order.status.value === 'cancelled')
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <Head>
      <title>Track Order {{ order.order_number }} | Colorado Supply & Procurement</title>
    </Head>

    <header class="bg-gray-900 py-5">
      <div class="mx-auto flex max-w-3xl items-center justify-between px-4 sm:px-6">
        <span class="text-base font-bold tracking-wide text-gray-100">
          COLORADO <span class="text-amber-400">SUPPLY &amp; PROCUREMENT</span>
        </span>
        <a href="/" class="text-sm font-semibold text-gray-200 hover:text-amber-300">Home</a>
      </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
      <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              Order {{ order.order_number }}
            </h1>
            <p class="mt-1 text-base text-gray-600 dark:text-gray-300">
              Placed {{ order.placed_at }}
            </p>
          </div>
          <span
            class="rounded-full px-3 py-1 text-sm font-semibold"
            :class="isCancelled
              ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
              : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'"
          >
            {{ order.status.label }}
          </span>
        </div>

        <!-- Status timeline -->
        <ol v-if="!isCancelled" class="mt-8 space-y-4" aria-label="Order progress">
          <li
            v-for="(step, index) in steps"
            :key="step.label"
            class="flex items-start gap-3"
          >
            <span
              class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
              :class="step.done
                ? 'bg-green-600 text-white'
                : 'border-2 border-gray-300 bg-white text-gray-400 dark:border-gray-600 dark:bg-gray-800'"
            >
              <template v-if="step.done">✓</template>
              <template v-else>{{ index + 1 }}</template>
            </span>
            <div>
              <p
                class="text-base font-semibold"
                :class="step.done ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
              >
                {{ step.label }}
              </p>
              <p v-if="step.detail && step.done" class="text-sm text-gray-500 dark:text-gray-400">
                {{ step.detail }}
              </p>
            </div>
          </li>
        </ol>
        <p v-else class="mt-6 text-base text-gray-600 dark:text-gray-300">
          This order has been cancelled. If you have questions, reply to your
          order email and our team will assist.
        </p>

        <!-- Shipments -->
        <div v-if="order.shipments.length" class="mt-8">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Shipments</h2>
          <ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700">
            <li
              v-for="(shipment, index) in order.shipments"
              :key="index"
              class="flex flex-wrap items-center justify-between gap-2 py-3 text-base"
            >
              <span class="text-gray-700 dark:text-gray-200">
                {{ shipment.carrier || 'Carrier pending' }}
                <span v-if="shipment.shipped_at" class="text-sm text-gray-500 dark:text-gray-400">
                  — {{ shipment.shipped_at }}
                </span>
              </span>
              <span v-if="shipment.tracking_number" class="font-mono text-sm text-gray-900 dark:text-gray-100">
                {{ shipment.tracking_number }}
              </span>
            </li>
          </ul>
        </div>

        <!-- Items -->
        <div class="mt-8">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Items</h2>
          <ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700">
            <li
              v-for="(item, index) in order.items"
              :key="index"
              class="flex items-center justify-between gap-4 py-3 text-base"
            >
              <span class="text-gray-700 dark:text-gray-200">
                {{ item.name }}
                <span class="text-sm text-gray-500 dark:text-gray-400">× {{ item.quantity }}</span>
              </span>
              <span class="shrink-0 text-gray-900 dark:text-gray-100">
                {{ currency.format(item.line_total) }}
              </span>
            </li>
          </ul>

          <dl class="mt-4 space-y-1 border-t border-gray-200 pt-4 text-base dark:border-gray-700">
            <div class="flex justify-between text-gray-600 dark:text-gray-300">
              <dt>Subtotal</dt>
              <dd>{{ currency.format(order.subtotal) }}</dd>
            </div>
            <div v-if="order.shipping_total" class="flex justify-between text-gray-600 dark:text-gray-300">
              <dt>Shipping</dt>
              <dd>{{ currency.format(order.shipping_total) }}</dd>
            </div>
            <div v-if="order.tax_total" class="flex justify-between text-gray-600 dark:text-gray-300">
              <dt>Tax</dt>
              <dd>{{ currency.format(order.tax_total) }}</dd>
            </div>
            <div class="flex justify-between font-bold text-gray-900 dark:text-white">
              <dt>Total</dt>
              <dd>{{ currency.format(order.grand_total) }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        Questions about this order? Reply to your order confirmation email and
        our team will assist.
      </p>
    </main>
  </div>
</template>
