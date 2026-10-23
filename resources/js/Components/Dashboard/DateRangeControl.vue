<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
  locations: {
    type: Array,
    default: () => [],
  },
})

const form = reactive({
  range: props.filters.range ?? 'last_30_days',
  start_date: props.filters.start_date,
  end_date: props.filters.end_date,
  location_id: props.filters.location_id ?? '',
  sublocation_id: props.filters.sublocation_id ?? '',
})

const apply = () => {
  router.get(route('dashboard'), {
    ...form,
    location_id: form.location_id || undefined,
    sublocation_id: form.sublocation_id || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

watch(() => form.location_id, () => {
  form.sublocation_id = ''
})
</script>

<template>
  <section aria-label="Dashboard filters" class="dashboard-filter st-panel p-4">
    <div class="grid gap-3 md:grid-cols-6">
      <label class="block md:col-span-1">
        <span class="text-sm font-medium text-st-muted">Range</span>
        <select v-model="form.range" class="st-field">
          <option v-for="option in filters.options.ranges" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
      </label>
      <label class="block md:col-span-1">
        <span class="text-sm font-medium text-st-muted">Start</span>
        <input v-model="form.start_date" type="date" :disabled="form.range !== 'custom'" class="st-field" />
      </label>
      <label class="block md:col-span-1">
        <span class="text-sm font-medium text-st-muted">End</span>
        <input v-model="form.end_date" type="date" :disabled="form.range !== 'custom'" class="st-field" />
      </label>
      <label class="block md:col-span-1">
        <span class="text-sm font-medium text-st-muted">Location</span>
        <select v-model="form.location_id" class="st-field">
          <option value="">All</option>
          <option v-for="location in locations" :key="location.id" :value="location.id">{{ location.name }}</option>
        </select>
      </label>
      <label class="block md:col-span-1">
        <span class="text-sm font-medium text-st-muted">Sublocation</span>
        <select v-model="form.sublocation_id" class="st-field">
          <option value="">All</option>
          <template v-for="location in locations" :key="location.id">
            <option v-for="child in location.children" :key="child.id" :value="child.id">{{ location.name }} / {{ child.name }}</option>
          </template>
        </select>
      </label>
      <div class="flex items-end md:col-span-1">
        <button type="button" class="st-btn-primary w-full" @click="apply">
          Apply
        </button>
      </div>
    </div>
  </section>
</template>
