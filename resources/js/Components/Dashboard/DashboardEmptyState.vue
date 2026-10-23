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
  <section class="flex flex-col items-center justify-center rounded-lg border border-dashed border-st-line bg-st-panel p-8 text-center">
    <h2 class="st-display text-xl font-bold text-st-ink">No purchases in this period</h2>
    <p class="mx-auto mt-2 max-w-2xl text-base leading-6 text-st-muted">
      Place an order from the store and your spending trends, location totals and reports will fill in here.
      <span v-if="!account.company">Location reporting starts once your account is assigned to a company.</span>
    </p>
    <Link :href="route('store.index')" class="st-btn-primary mt-5">Browse store</Link>
  </section>
</template>
