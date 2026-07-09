<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  order: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <AppLayout :appName="$page.props.appName">
    <Head>
      <title>Checkout Cancelled | Colorado Supply & Procurement</title>
    </Head>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-800 flex items-center justify-center px-4">
      <div class="max-w-md w-full bg-white dark:bg-gray-700 rounded-lg shadow-md p-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Checkout Cancelled
        </h1>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
          Order <span class="font-semibold">{{ order.order_number }}</span> has not been charged.
          You can try again whenever you're ready.
        </p>

        <Link href="/store" class="inline-block px-6 py-2 bg-blue-700 text-white rounded-md hover:bg-blue-800">
          Return to Store
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
