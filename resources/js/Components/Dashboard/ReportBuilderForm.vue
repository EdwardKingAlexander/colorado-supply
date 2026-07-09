<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
})

const form = reactive({
  range: props.filters.key ?? props.filters.range ?? 'last_30_days',
  start_date: props.filters.start_date,
  end_date: props.filters.end_date,
  location_id: props.filters.location_id ?? '',
  sublocation_id: props.filters.sublocation_id ?? '',
  group_by: props.filters.group_by ?? 'month',
})

const apply = () => {
  router.get(route('dashboard.reports'), {
    ...form,
    location_id: form.location_id || undefined,
    sublocation_id: form.sublocation_id || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const exportUrl = () => route('dashboard.reports.export', {
  ...form,
  location_id: form.location_id || undefined,
  sublocation_id: form.sublocation_id || undefined,
})
</script>

<template>
  <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
    <div class="grid gap-4 md:grid-cols-6">
      <label class="text-sm">
        <span class="font-medium text-gray-700">Range</span>
        <select v-model="form.range" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
          <option value="this_month">This month</option>
          <option value="last_30_days">Last 30 days</option>
          <option value="quarter_to_date">Quarter to date</option>
          <option value="year_to_date">Year to date</option>
          <option value="last_12_months">Last 12 months</option>
          <option value="custom">Custom</option>
        </select>
      </label>
      <label class="text-sm">
        <span class="font-medium text-gray-700">Start</span>
        <input v-model="form.start_date" type="date" :disabled="form.range !== 'custom'" class="mt-1 block w-full rounded-md border-gray-300 text-sm disabled:bg-gray-100" />
      </label>
      <label class="text-sm">
        <span class="font-medium text-gray-700">End</span>
        <input v-model="form.end_date" type="date" :disabled="form.range !== 'custom'" class="mt-1 block w-full rounded-md border-gray-300 text-sm disabled:bg-gray-100" />
      </label>
      <label class="text-sm">
        <span class="font-medium text-gray-700">Group by</span>
        <select v-model="form.group_by" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
          <option v-for="option in filters.options.group_by" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
      </label>
      <label class="text-sm">
        <span class="font-medium text-gray-700">Location</span>
        <select v-model="form.location_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
          <option value="">All</option>
          <option v-for="location in filters.options.locations" :key="location.id" :value="location.id">{{ location.name }}</option>
        </select>
      </label>
      <label class="text-sm">
        <span class="font-medium text-gray-700">Sublocation</span>
        <select v-model="form.sublocation_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
          <option value="">All</option>
          <template v-for="location in filters.options.locations" :key="location.id">
            <option v-for="child in location.children" :key="child.id" :value="child.id">{{ location.name }} / {{ child.name }}</option>
          </template>
        </select>
      </label>
    </div>

    <div class="mt-5 flex flex-wrap gap-2">
      <button type="button" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800" @click="apply">
        Preview report
      </button>
      <a :href="exportUrl()" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
        Export CSV
      </a>
    </div>
  </section>
</template>
