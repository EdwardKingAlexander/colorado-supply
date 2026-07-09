<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
})

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })
const maxTotal = (rows) => Math.max(...rows.map((row) => Number(row.total) || 0), 1)
</script>

<template>
  <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
    <h2 class="text-base font-semibold text-gray-900">{{ title }}</h2>
    <div class="mt-4 space-y-3">
      <div v-for="row in rows" :key="`${row.label}-${row.location_id ?? ''}`" class="space-y-1">
        <div class="flex items-center justify-between gap-3 text-sm">
          <span class="truncate font-medium text-gray-700">{{ row.label }}</span>
          <span class="whitespace-nowrap text-gray-600">{{ currency.format(row.total) }}</span>
        </div>
        <div class="h-2 rounded-full bg-gray-100">
          <div class="h-2 rounded-full bg-blue-600" :style="{ width: `${Math.max(4, (Number(row.total) / maxTotal(rows)) * 100)}%` }"></div>
        </div>
      </div>
      <p v-if="rows.length === 0" class="py-8 text-center text-sm text-gray-500">No spending data for this period.</p>
    </div>
  </section>
</template>
