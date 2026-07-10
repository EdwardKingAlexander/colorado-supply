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

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 pb-12 pt-28 dark:bg-gray-800 sm:px-6">
      <div class="w-full max-w-md rounded-lg bg-white p-6 text-center shadow-md dark:bg-gray-700 sm:p-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Checkout Cancelled
        </h1>
        <p class="mb-6 text-base leading-6 text-gray-600 dark:text-gray-300">
          Order <span class="font-semibold">{{ order.order_number }}</span> has not been charged.
          You can try again whenever you're ready.
        </p>

        <Link href="/store" class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-blue-700 px-6 py-3 text-base font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
          Return to Store
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
