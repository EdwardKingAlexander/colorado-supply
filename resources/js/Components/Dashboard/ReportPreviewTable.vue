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

const label = (column) => column.replaceAll('_', ' ')
const format = (value, column) => {
  if (column === 'spend') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value) || 0)
  }

  return value ?? ''
}
</script>

<template>
  <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
      <h2 class="text-base font-semibold text-gray-900">Report preview</h2>
      <p class="text-sm text-gray-500">{{ rowCount }} rows</p>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
          <tr>
            <th v-for="column in columns" :key="column" class="px-5 py-3">{{ label(column) }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="(row, index) in rows" :key="index">
            <td v-for="column in columns" :key="column" class="whitespace-nowrap px-5 py-3 text-gray-700">
              {{ format(row[column], column) }}
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="columns.length || 1" class="px-5 py-8 text-center text-gray-500">No matching report data.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
