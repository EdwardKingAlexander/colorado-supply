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
  <section aria-label="Report options" class="dashboard-filter st-panel p-4 sm:p-5">
    <div class="grid gap-4 md:grid-cols-6">
      <label class="block">
        <span class="text-sm font-medium text-st-muted">Range</span>
        <select v-model="form.range" class="st-field">
          <option value="this_month">This month</option>
          <option value="last_30_days">Last 30 days</option>
          <option value="quarter_to_date">Quarter to date</option>
          <option value="year_to_date">Year to date</option>
          <option value="last_12_months">Last 12 months</option>
          <option value="custom">Custom</option>
        </select>
      </label>
      <label class="block">
        <span class="text-sm font-medium text-st-muted">Start</span>
        <input v-model="form.start_date" type="date" :disabled="form.range !== 'custom'" class="st-field" />
      </label>
      <label class="block">
        <span class="text-sm font-medium text-st-muted">End</span>
        <input v-model="form.end_date" type="date" :disabled="form.range !== 'custom'" class="st-field" />
      </label>
      <label class="block">
        <span class="text-sm font-medium text-st-muted">Group by</span>
        <select v-model="form.group_by" class="st-field">
          <option v-for="option in filters.options.group_by" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
      </label>
      <label class="block">
        <span class="text-sm font-medium text-st-muted">Location</span>
        <select v-model="form.location_id" class="st-field">
          <option value="">All</option>
          <option v-for="location in filters.options.locations" :key="location.id" :value="location.id">{{ location.name }}</option>
        </select>
      </label>
      <label class="block">
        <span class="text-sm font-medium text-st-muted">Sublocation</span>
        <select v-model="form.sublocation_id" class="st-field">
          <option value="">All</option>
          <template v-for="location in filters.options.locations" :key="location.id">
            <option v-for="child in location.children" :key="child.id" :value="child.id">{{ location.name }} / {{ child.name }}</option>
          </template>
        </select>
      </label>
    </div>

    <div class="mt-5 grid gap-2 border-t border-st-line pt-4 xs:grid-cols-2 sm:flex">
      <button type="button" class="st-btn-primary" @click="apply">
        Preview report
      </button>
      <a :href="exportUrl()" class="st-btn-secondary">
        Export CSV
      </a>
    </div>
  </section>
</template>
