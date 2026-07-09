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
  <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-5 py-4">
      <h2 class="text-base font-semibold text-gray-900">Top purchased items</h2>
    </div>
    <div class="divide-y divide-gray-100">
      <div v-for="item in items" :key="`${item.name}-${item.sku}`" class="flex items-center justify-between gap-4 px-5 py-3">
        <div class="min-w-0">
          <p class="truncate text-sm font-medium text-gray-900">{{ item.name }}</p>
          <p class="text-xs text-gray-500">{{ item.sku ?? 'No SKU' }} · Qty {{ item.quantity }}</p>
        </div>
        <p class="whitespace-nowrap text-sm font-semibold text-gray-900">{{ currency.format(item.total) }}</p>
      </div>
      <div v-if="items.length === 0" class="px-5 py-8 text-center text-sm text-gray-500">No item history yet.</div>
    </div>
  </section>
</template>
