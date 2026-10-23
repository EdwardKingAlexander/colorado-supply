<script setup>
import StoreLayout from '@/Layouts/StoreLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
  order: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <StoreLayout>
    <Head>
      <title>Checkout Cancelled | Colorado Supply & Procurement</title>
    </Head>

    <div class="mobile-page-gutter mx-auto max-w-xl py-10 sm:py-16">
      <div class="st-panel p-6 sm:p-8">
        <h1 class="st-display text-2xl font-extrabold text-st-ink sm:text-3xl">
          Checkout cancelled
        </h1>
        <p class="mt-2 text-base leading-6 text-st-muted">
          Order <span class="st-num font-semibold text-st-ink">{{ order.order_number }}</span> has not been charged.
          You can pay for it whenever you're ready.
        </p>

        <div class="mt-6 grid gap-2 sm:grid-cols-2">
          <Link :href="route('store.checkout.pay', { order: order.id })" class="st-btn-primary">Try payment again</Link>
          <Link :href="route('store.index')" class="st-btn-secondary">Keep shopping</Link>
        </div>
      </div>
    </div>
  </StoreLayout>
</template>
