<script setup>
defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })
</script>

<template>
  <section class="st-panel">
    <div class="border-b border-st-line px-5 py-4">
      <h2 class="text-base font-semibold text-st-ink">Top purchased items</h2>
    </div>
    <ul class="divide-y divide-st-line">
      <li v-for="item in items" :key="`${item.name}-${item.sku}`" class="flex items-center justify-between gap-4 px-5 py-3">
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-st-ink">{{ item.name }}</p>
          <p class="st-num text-sm text-st-muted">{{ item.sku ?? 'No SKU' }}, qty {{ item.quantity }}</p>
        </div>
        <p class="st-num whitespace-nowrap text-sm font-semibold text-st-ink">{{ currency.format(item.total) }}</p>
      </li>
    </ul>
    <p v-if="items.length === 0" class="px-5 py-8 text-center text-sm text-st-muted">Items you buy most often will show here.</p>
  </section>
</template>
