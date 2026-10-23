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
  <!-- Single-series bar list: one brand hue (validated against both panel surfaces), values as text. -->
  <section class="st-panel p-5">
    <h2 class="text-base font-semibold text-st-ink">{{ title }}</h2>
    <ul class="mt-4 space-y-3.5">
      <li v-for="row in rows" :key="`${row.label}-${row.location_id ?? ''}`" class="space-y-1.5">
        <div class="flex items-baseline justify-between gap-3 text-sm">
          <span class="truncate text-st-ink">{{ row.label }}</span>
          <span class="st-num whitespace-nowrap font-semibold text-st-ink">{{ currency.format(row.total) }}</span>
        </div>
        <div class="h-2 rounded bg-st-sunk" aria-hidden="true">
          <div class="h-2 rounded bg-primary-700 dark:bg-primary-500" :style="{ width: `${Math.max(2, (Number(row.total) / maxTotal(rows)) * 100)}%` }"></div>
        </div>
      </li>
    </ul>
    <p v-if="rows.length === 0" class="py-8 text-center text-sm text-st-muted">No spending in this period.</p>
  </section>
</template>
