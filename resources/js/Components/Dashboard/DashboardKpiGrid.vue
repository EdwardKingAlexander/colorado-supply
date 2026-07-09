<script setup>
const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
})

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })
const money = (value) => currency.format(Number(value) || 0)

const cards = [
  { label: 'Total spend', value: () => money(props.summary.total_spend) },
  { label: 'Orders', value: () => props.summary.orders_count },
  { label: 'Average order', value: () => money(props.summary.average_order_value) },
  { label: 'Open / unpaid', value: () => `${props.summary.open_orders_count} / ${props.summary.unpaid_orders_count}` },
  { label: 'Top location', value: () => props.summary.top_location?.label ?? 'None' },
]
</script>

<template>
  <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
    <div v-for="card in cards" :key="card.label" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
      <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ card.label }}</p>
      <p class="mt-2 truncate text-2xl font-semibold text-gray-900">{{ card.value() }}</p>
    </div>
  </section>
</template>
