<script setup>
const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  selected: {
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['select'])

// emits selected category id so the store page can re-fetch products
const handleSelect = (categoryId) => {
  emit('select', categoryId)
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm h-full">
    <div class="p-4 border-b border-gray-200">
      <h2 class="text-lg font-semibold text-gray-900">Categories</h2>
      <p class="text-xs text-gray-500 mt-1">Browse the catalog tree.</p>
    </div>
    <div class="p-3">
      <div
        v-if="loading"
        class="space-y-2"
        aria-live="polite"
      >
        <div v-for="placeholder in 6" :key="placeholder" class="h-8 rounded bg-gray-100 animate-pulse" />
        <p class="text-xs text-gray-500">Loading categories...</p>
      </div>
      <nav v-else class="space-y-1">
        <button
          type="button"
          class="w-full text-left px-3 py-2 text-sm font-medium rounded transition-colors"
          :class="selected === null ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-50'"
          @click="handleSelect(null)"
        >
          All Categories
        </button>
        <template v-if="categories.length">
          <button
            v-for="category in categories"
            :key="category.id"
            type="button"
            class="w-full text-left px-3 py-2 text-sm rounded flex items-center justify-between transition-colors"
            :class="
              selected === category.id
                ? 'bg-gray-900 text-white'
                : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
            "
            @click="handleSelect(category.id)"
          >
            <span>{{ category.name }}</span>
            <span class="text-xs text-gray-400" v-if="category.parent_id">Sub</span>
          </button>
        </template>
        <p v-else class="text-sm text-gray-500 px-3 py-2">No categories available.</p>
      </nav>
    </div>
  </div>
</template>
