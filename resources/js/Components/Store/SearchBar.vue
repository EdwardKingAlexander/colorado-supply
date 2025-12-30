<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: 'Search catalog...',
  },
  debounce: {
    type: Number,
    default: 350,
  },
  variant: {
    type: String,
    default: 'card',
    validator: (value) => ['card', 'inline'].includes(value),
  },
})

const emit = defineEmits(['update:modelValue', 'search'])

const localQuery = ref(props.modelValue ?? '')

const wrapperClasses = computed(() => {
  if (props.variant === 'inline') {
    return 'w-full'
  }

  return 'bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4'
})

const innerClasses = computed(() => {
  if (props.variant === 'inline') {
    return 'w-full flex flex-col gap-2 sm:flex-row sm:items-center'
  }

  return 'max-w-3xl mx-auto flex gap-3'
})

const inputClasses = computed(() => {
  const base =
    'w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 bg-white dark:bg-gray-900'

  if (props.variant === 'inline') {
    return base + ' shadow-sm'
  }

  return base
})

const buttonClasses = computed(() => {
  const base =
    'text-sm font-semibold text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed'

  if (props.variant === 'inline') {
    return base + ' px-4 py-2 bg-gray-900 hover:bg-gray-800 flex-shrink-0'
  }

  return base + ' px-5 py-3 bg-gray-900 hover:bg-gray-800'
})

const watchDebounced = (source, callback, delay = 300) => {
  let timeoutId

  return watch(
    source,
    (value, oldValue, onCleanup) => {
      if (timeoutId) {
        clearTimeout(timeoutId)
      }

      timeoutId = setTimeout(() => callback(value, oldValue), delay)
      onCleanup(() => clearTimeout(timeoutId))
    },
    { flush: 'post' },
  )
}

watch(
  () => props.modelValue,
  (value) => {
    if (value === localQuery.value) {
      return
    }

    localQuery.value = value ?? ''
  },
)

// emits debounced search queries upstream for instant filtering
watchDebounced(
  () => localQuery.value,
  (value) => {
    emit('search', value.trim())
  },
  props.debounce,
)

const handleInput = (event) => {
  localQuery.value = event.target.value
  emit('update:modelValue', localQuery.value)
}

const submitSearch = () => {
  emit('search', localQuery.value.trim())
}
</script>

<template>
  <form :class="wrapperClasses" @submit.prevent="submitSearch">
    <div :class="innerClasses">
      <input
        :value="localQuery"
        :placeholder="placeholder"
        :class="inputClasses"
        type="text"
        name="store-search"
        autocomplete="off"
        @input="handleInput"
      />
      <button
        type="submit"
        :class="buttonClasses"
        :disabled="loading"
      >
        <span v-if="loading" class="animate-pulse">Searching...</span>
        <span v-else>Search</span>
      </button>
    </div>
  </form>
</template>
