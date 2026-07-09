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

    <div class="min-h-screen bg-gray-50 dark:bg-gray-800 flex items-center justify-center px-4">
      <div class="max-w-md w-full bg-white dark:bg-gray-700 rounded-lg shadow-md p-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Thank you for your order
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
          Order <span class="font-semibold">{{ order.order_number }}</span> — {{ totalDisplay }}
        </p>

        <p v-if="isPaid" class="text-green-600 dark:text-green-400 font-medium mb-6">
          Payment confirmed.
        </p>
        <p v-else class="text-amber-600 dark:text-amber-400 font-medium mb-6">
          We're confirming your payment now. You'll receive an email once it's complete.
        </p>

        <Link href="/store" class="inline-block px-6 py-2 bg-blue-700 text-white rounded-md hover:bg-blue-800">
          Continue Shopping
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
