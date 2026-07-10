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
  <section class="dashboard-filter rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
    <div class="grid gap-3 md:grid-cols-6">
      <label class="text-base md:col-span-1">
        <span class="font-medium text-gray-700">Range</span>
        <select v-model="form.range" class="mt-2 block w-full rounded-md border-gray-300 text-base">
          <option v-for="option in filters.options.ranges" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
      </label>
      <label class="text-base md:col-span-1">
        <span class="font-medium text-gray-700">Start</span>
        <input v-model="form.start_date" type="date" :disabled="form.range !== 'custom'" class="mt-2 block w-full rounded-md border-gray-300 text-base disabled:bg-gray-100" />
      </label>
      <label class="text-base md:col-span-1">
        <span class="font-medium text-gray-700">End</span>
        <input v-model="form.end_date" type="date" :disabled="form.range !== 'custom'" class="mt-2 block w-full rounded-md border-gray-300 text-base disabled:bg-gray-100" />
      </label>
      <label class="text-base md:col-span-1">
        <span class="font-medium text-gray-700">Location</span>
        <select v-model="form.location_id" class="mt-2 block w-full rounded-md border-gray-300 text-base">
          <option value="">All</option>
          <option v-for="location in locations" :key="location.id" :value="location.id">{{ location.name }}</option>
        </select>
      </label>
      <label class="text-base md:col-span-1">
        <span class="font-medium text-gray-700">Sublocation</span>
        <select v-model="form.sublocation_id" class="mt-2 block w-full rounded-md border-gray-300 text-base">
          <option value="">All</option>
          <template v-for="location in locations" :key="location.id">
            <option v-for="child in location.children" :key="child.id" :value="child.id">{{ location.name }} / {{ child.name }}</option>
          </template>
        </select>
      </label>
      <div class="flex items-end md:col-span-1">
        <button type="button" class="min-h-12 w-full rounded-md bg-gray-900 px-4 py-3 text-base font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @click="apply">
          Apply
        </button>
      </div>
    </div>
  </section>
</template>
