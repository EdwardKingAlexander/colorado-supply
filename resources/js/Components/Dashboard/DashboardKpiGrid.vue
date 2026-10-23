<script setup>
import { computed } from 'vue'

const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
})

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })
const money = (value) => currency.format(Number(value) || 0)

// One ledger strip: total spend leads, the rest read across beside it.
const figures = computed(() => [
  { label: 'Orders', value: props.summary.orders_count },
  { label: 'Average order', value: money(props.summary.average_order_value) },
  { label: 'Open / unpaid', value: `${props.summary.open_orders_count} / ${props.summary.unpaid_orders_count}` },
  { label: 'Top location', value: props.summary.top_location?.label ?? 'None', text: true },
])
</script>

<template>
  <section aria-label="Purchasing summary" class="overflow-hidden rounded-lg border border-st-line bg-st-line">
    <dl class="grid gap-px sm:grid-cols-2 xl:grid-cols-[minmax(0,1.6fr)_repeat(4,minmax(0,1fr))]">
      <div class="bg-st-panel p-5 sm:col-span-2 xl:col-span-1">
        <dt class="text-sm font-medium text-st-muted">Total spend</dt>
        <dd class="st-display st-num mt-1 break-words text-4xl font-extrabold leading-none text-st-ink sm:text-[2.75rem]">
          {{ money(summary.total_spend) }}
        </dd>
      </div>
      <div v-for="figure in figures" :key="figure.label" class="flex flex-col justify-between bg-st-panel p-5">
        <dt class="text-sm font-medium text-st-muted">{{ figure.label }}</dt>
        <dd
          class="mt-2 break-words font-bold text-st-ink"
          :class="figure.text ? 'text-lg leading-6' : 'st-num text-2xl leading-8'"
        >
          {{ figure.value }}
        </dd>
      </div>
    </dl>
  </section>
</template>
