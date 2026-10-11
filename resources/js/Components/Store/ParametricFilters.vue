<script setup>
import { computed } from 'vue'

const props = defineProps({
  filters: {
    type: Array,
    default: () => [],
  },
  activeFilters: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:activeFilters', 'clear'])

const PRODUCT_TYPE_FILTER = 'Product Type'

const orderedFilters = computed(() => {
  if (!Array.isArray(props.filters)) {
    return []
  }

  const productTypeFilter = props.filters.find(filter => filter.name === PRODUCT_TYPE_FILTER)
  const otherFilters = props.filters.filter(filter => filter.name !== PRODUCT_TYPE_FILTER)

  return productTypeFilter ? [productTypeFilter, ...otherFilters] : otherFilters
})

const toggleFilter = (attributeName, value, isMulti = false) => {
  const newFilters = { ...props.activeFilters }

  if (isMulti) {
    const currentValues = Array.isArray(newFilters[attributeName])
      ? [...newFilters[attributeName]]
      : newFilters[attributeName]
        ? [newFilters[attributeName]]
        : []

    const existingIndex = currentValues.indexOf(value)

    if (existingIndex >= 0) {
      currentValues.splice(existingIndex, 1)
    } else {
      currentValues.push(value)
    }

    if (currentValues.length > 0) {
      newFilters[attributeName] = currentValues
    } else {
      delete newFilters[attributeName]
    }
  } else {
    if (newFilters[attributeName] === value) {
      delete newFilters[attributeName]
    } else {
      newFilters[attributeName] = value
    }
  }

  emit('update:activeFilters', newFilters)
}

const isValueSelected = (attributeName, value) => {
  const filterValue = props.activeFilters[attributeName]

  if (Array.isArray(filterValue)) {
    return filterValue.includes(value)
  }

  return filterValue === value
}

const isProductTypeFilter = (filter) => filter.name === PRODUCT_TYPE_FILTER

const optionsFor = (filter) => {
  if (filter.type === 'boolean') {
    return [{ value: 'true', label: 'Yes' }, { value: 'false', label: 'No' }]
  }

  return (filter.values ?? []).map(value => ({ value, label: value }))
}

const clearAllFilters = () => {
  emit('clear')
}

const hasActiveFilters = computed(() => {
  return Object.values(props.activeFilters).some(value => {
    if (Array.isArray(value)) {
      return value.length > 0
    }

    return value !== undefined && value !== null
  })
})

const activeFilterCount = computed(() => {
  return Object.values(props.activeFilters).reduce((count, value) => {
    if (Array.isArray(value)) {
      return count + value.length
    }

    return count + 1
  }, 0)
})
</script>

<template>
  <div>
    <div class="mb-1 flex min-h-11 items-center justify-between">
      <h2 class="text-base font-bold text-st-ink">Filter by spec</h2>
      <button
        v-if="hasActiveFilters"
        type="button"
        class="inline-flex min-h-11 items-center rounded-md px-2 text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
        @click="clearAllFilters"
      >
        Clear all ({{ activeFilterCount }})
      </button>
    </div>

    <div v-if="loading" class="space-y-5 py-2" aria-busy="true">
      <div v-for="i in 3" :key="i" class="space-y-2">
        <div class="h-3.5 w-24 animate-pulse rounded bg-st-sunk" />
        <div v-for="j in 3" :key="`skeleton-${i}-${j}`" class="h-3 w-full animate-pulse rounded bg-st-sunk" />
      </div>
    </div>

    <p v-else-if="filters.length === 0" class="py-2 text-sm text-st-muted">
      This category has no spec filters yet.
    </p>

    <div v-else class="divide-y divide-st-line">
      <div
        v-for="(filter, index) in orderedFilters"
        :key="filter.name"
        role="group"
        :aria-labelledby="`spec-filter-${index}`"
        class="py-3"
      >
        <p :id="`spec-filter-${index}`" class="mb-1 text-sm font-semibold text-st-ink">{{ filter.name }}</p>
        <label
          v-for="option in optionsFor(filter)"
          :key="option.value"
          class="flex min-h-11 cursor-pointer items-center gap-3 rounded px-2 text-[0.9375rem] text-st-ink/85 hover:bg-st-sunk lg:min-h-9"
        >
          <input
            :type="isProductTypeFilter(filter) ? 'checkbox' : 'radio'"
            :name="isProductTypeFilter(filter) ? `filter-${filter.name}-${option.value}` : `filter-${filter.name}`"
            :value="option.value"
            :checked="isValueSelected(filter.name, option.value)"
            class="h-4 w-4 border-st-muted/70 bg-st-sunk text-st-fill focus:ring-st-action focus:ring-offset-st-panel"
            :class="isProductTypeFilter(filter) ? 'rounded' : ''"
            @click="!isProductTypeFilter(filter) && isValueSelected(filter.name, option.value) && toggleFilter(filter.name, option.value)"
            @change="toggleFilter(filter.name, option.value, isProductTypeFilter(filter))"
          />
          <span>{{ option.label }}</span>
        </label>
      </div>
    </div>
  </div>
</template>
