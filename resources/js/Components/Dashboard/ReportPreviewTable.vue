<script setup>
defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  rows: {
    type: Array,
    default: () => [],
  },
  rowCount: {
    type: Number,
    default: 0,
  },
})

const label = (column) => {
  const text = column.replaceAll('_', ' ')
  return text.charAt(0).toUpperCase() + text.slice(1)
}
const isMoney = (column) => column === 'spend'
const format = (value, column) => {
  if (isMoney(column)) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value) || 0)
  }

  return value ?? ''
}
</script>

<template>
  <section class="st-panel">
    <div class="flex items-center justify-between border-b border-st-line px-5 py-4">
      <h2 class="text-base font-semibold text-st-ink">Report preview</h2>
      <p class="st-num text-sm text-st-muted">{{ rowCount }} {{ rowCount === 1 ? 'row' : 'rows' }}</p>
    </div>
    <table class="responsive-data-table st-table min-w-full divide-y divide-st-line text-sm">
      <thead>
        <tr>
          <th v-for="column in columns" :key="column" scope="col" :class="{ '!text-right': isMoney(column) }">{{ label(column) }}</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-st-line">
        <tr v-for="(row, index) in rows" :key="index">
          <td
            v-for="column in columns"
            :key="column"
            :data-label="label(column)"
            class="whitespace-nowrap text-st-ink"
            :class="{ 'st-num text-right font-semibold': isMoney(column) }"
          >
            {{ format(row[column], column) }}
          </td>
        </tr>
        <tr v-if="rows.length === 0">
          <td :colspan="columns.length || 1" class="!py-8 text-center text-st-muted">No orders match these options. Try a wider date range or all locations.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
