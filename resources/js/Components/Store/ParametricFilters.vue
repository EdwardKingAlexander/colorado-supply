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
  <div class="bg-white/95 dark:bg-gray-900/90 rounded-2xl shadow-lg ring-1 ring-gray-100 dark:ring-gray-800 p-4">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-semibold tracking-tight text-gray-900 dark:text-white">
        Filter by Specs
      </h2>
      <button
        v-if="hasActiveFilters"
        type="button"
        class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
        @click="clearAllFilters"
      >
        Clear All ({{ activeFilterCount }})
      </button>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 4" :key="i" class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-3">
        <div class="flex items-center justify-between mb-3">
          <div class="h-3 w-24 bg-gray-100 dark:bg-gray-700 rounded animate-pulse"></div>
          <div class="h-3 w-10 bg-gray-100 dark:bg-gray-700 rounded animate-pulse"></div>
        </div>
        <div class="space-y-2">
          <div v-for="j in 3" :key="`skeleton-${i}-${j}`" class="h-4 bg-gray-50 dark:bg-gray-600/70 rounded animate-pulse"></div>
        </div>
      </div>
    </div>

    <div v-else-if="filters.length === 0" class="text-center py-6">
      <p class="text-sm text-gray-500 dark:text-gray-400">
        No filters available for this category yet. Try selecting a different category or broadening your search.
      </p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="filter in orderedFilters"
        :key="filter.name"
        class="pb-4 border-b border-gray-100 dark:border-gray-800 last:border-0"
      >
        <h3 class="text-sm font-semibold tracking-tight text-gray-900 dark:text-white mb-2">
          {{ filter.name }}
        </h3>

        <!-- Product Type multi-select -->
        <div v-if="isProductTypeFilter(filter)" class="space-y-1">
          <label
            v-for="value in filter.values"
            :key="value"
            class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition"
          >
            <input
              type="checkbox"
              :name="`filter-${filter.name}-${value}`"
              :value="value"
              :checked="isValueSelected(filter.name, value)"
              class="w-4 h-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"
              @change="toggleFilter(filter.name, value, true)"
            />
            <span class="ml-2 text-sm font-mono text-gray-700 dark:text-gray-300">{{ value }}</span>
          </label>
        </div>

        <!-- String/Select type filters -->
        <div v-else-if="filter.type === 'string' || filter.type === 'select'" class="space-y-1">
          <label
            v-for="value in filter.values"
            :key="value"
            class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition"
          >
            <input
              type="radio"
              :name="`filter-${filter.name}`"
              :value="value"
              :checked="isValueSelected(filter.name, value)"
              class="w-4 h-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"
              @change="toggleFilter(filter.name, value)"
            />
            <span class="ml-2 text-sm font-mono text-gray-700 dark:text-gray-300">{{ value }}</span>
          </label>
        </div>

        <!-- Boolean type filters -->
        <div v-else-if="filter.type === 'boolean'" class="space-y-1">
          <label
            v-for="value in ['true', 'false']"
            :key="value"
            class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition"
          >
            <input
              type="radio"
              :name="`filter-${filter.name}`"
              :value="value"
              :checked="isValueSelected(filter.name, value)"
              class="w-4 h-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"
              @change="toggleFilter(filter.name, value)"
            />
            <span class="ml-2 text-sm font-mono text-gray-700 dark:text-gray-300">{{ value === 'true' ? 'Yes' : 'No' }}</span>
          </label>
        </div>

        <!-- Numeric type filters (integer/float) -->
        <div v-else-if="filter.type === 'integer' || filter.type === 'float'" class="space-y-1">
          <label
            v-for="value in filter.values"
            :key="value"
            class="flex items-center px-2 py-1.5 rounded hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition"
          >
            <input
              type="radio"
              :name="`filter-${filter.name}`"
              :value="value"
              :checked="isValueSelected(filter.name, value)"
              class="w-4 h-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800"
              @change="toggleFilter(filter.name, value)"
            />
            <span class="ml-2 text-sm font-mono text-gray-700 dark:text-gray-300">{{ value }}</span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>
