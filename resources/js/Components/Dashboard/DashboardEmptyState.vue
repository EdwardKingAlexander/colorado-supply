<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  account: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <section class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center">
    <h2 class="text-lg font-semibold text-gray-900">No purchasing activity yet</h2>
    <p class="mx-auto mt-2 max-w-2xl text-sm text-gray-600">
      Start an order from the store to populate spending trends, location rollups, and purchasing reports.
      <span v-if="!account.company">Location reporting will appear once this account is assigned to a company.</span>
    </p>
    <Link :href="route('store.index')" class="mt-5 inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
      Browse store
    </Link>
  </section>
</template>
