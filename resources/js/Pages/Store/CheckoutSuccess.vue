<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
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
  <AppLayout :appName="$page.props.appName">
    <Head>
      <title>Payment | Colorado Supply & Procurement</title>
    </Head>

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 pb-12 pt-28 dark:bg-gray-800 sm:px-6">
      <div class="w-full max-w-md rounded-lg bg-white p-6 text-center shadow-md dark:bg-gray-700 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Thank you for your order
        </h1>
        <p class="mb-6 text-base leading-6 text-gray-600 dark:text-gray-300">
          Order <span class="font-semibold">{{ order.order_number }}</span> — {{ totalDisplay }}
        </p>

        <p v-if="isPaid" class="text-green-600 dark:text-green-400 font-medium mb-6">
          Payment confirmed.
        </p>
        <p v-else class="text-amber-600 dark:text-amber-400 font-medium mb-6">
          We're confirming your payment now. You'll receive an email once it's complete.
        </p>

        <Link href="/store" class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-blue-700 px-6 py-3 text-base font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
          Continue Shopping
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
