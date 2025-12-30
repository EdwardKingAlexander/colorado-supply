<script setup>
import { onMounted, ref, computed } from 'vue'
import axios from 'axios'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SearchBar from '@/Components/Store/SearchBar.vue'
import ParametricFilters from '@/Components/Store/ParametricFilters.vue'
import ProductList from '@/Components/Store/ProductList.vue'
import { Head, Link } from '@inertiajs/vue3'
import { useQuoteStore } from '@/Stores/useQuoteStore'

// Navigation state
const allCategories = ref([])
const categoriesLoading = ref(true)
const selectedParentCategory = ref(null)
const selectedSubcategory = ref(null)
const navigationLevel = ref('categories') // 'categories', 'subcategories', 'products'

// Product state
const products = ref([])
const productsLoading = ref(true)
const pagination = ref(null)
const searchTerm = ref('')
const currentPage = ref(1)
const totalPages = ref(1)
const availableFilters = ref([])
const activeFilters = ref({})
const filtersLoading = ref(false)

const isSearching = computed(() => searchTerm.value.trim().length > 0)
const showFilters = computed(() => navigationLevel.value === 'products' && Boolean(selectedSubcategory.value))
const showingProductView = computed(() => navigationLevel.value === 'products' && (selectedSubcategory.value || isSearching.value))
const isGlobalSearch = computed(() => navigationLevel.value === 'products' && !selectedSubcategory.value && isSearching.value)
const quoteStore = useQuoteStore()
const quoteItemCount = computed(() => quoteStore.itemCount.value)

// Computed: Get parent categories (top-level categories with no parent)
const parentCategories = computed(() => {
  return allCategories.value.filter(cat => !cat.parent_id)
})

// Computed: Get subcategories for selected parent
const subcategories = computed(() => {
  if (!selectedParentCategory.value) return []
  return allCategories.value.filter(cat => cat.parent_id === selectedParentCategory.value.id)
})

// Computed: Breadcrumb trail
const activeFilterPills = computed(() => {
  const pills = []
  const trimmedSearch = searchTerm.value.trim()

  if (trimmedSearch.length > 0) {
    pills.push({
      id: `search-${trimmedSearch}`,
      type: 'search',
      label: `Search: ${trimmedSearch}`,
    })
  }

  Object.entries(activeFilters.value).forEach(([attribute, value]) => {
    if (Array.isArray(value)) {
      value.forEach((option, index) => {
        pills.push({
          id: `${attribute}-${option}-${index}`,
          type: 'filter',
          attribute,
          value: option,
          label: `${attribute}: ${option}`,
        })
      })
    } else if (value !== undefined && value !== null && value !== '') {
      pills.push({
        id: `${attribute}-${value}`,
        type: 'filter',
        attribute,
        value,
        label: `${attribute}: ${value}`,
      })
    }
  })

  return pills
})

const hasRemovableFilters = computed(() => activeFilterPills.value.some(pill => pill.type === 'filter'))

const breadcrumbTrail = computed(() => {
  const crumbs = [{
    key: 'catalog',
    label: 'Browse Catalog',
    target: 'catalog',
    clickable:
      navigationLevel.value !== 'categories' ||
      Boolean(selectedParentCategory.value) ||
      Boolean(selectedSubcategory.value) ||
      isSearching.value,
  }]

  if (selectedParentCategory.value) {
    crumbs.push({
      key: `parent-${selectedParentCategory.value.id}`,
      label: selectedParentCategory.value.name,
      target: 'parent',
      clickable: navigationLevel.value === 'products' && Boolean(selectedSubcategory.value),
    })
  }

  if (selectedSubcategory.value) {
    crumbs.push({
      key: `sub-${selectedSubcategory.value.id}`,
      label: selectedSubcategory.value.name,
      target: null,
      clickable: false,
    })
  } else if (isGlobalSearch.value) {
    crumbs.push({
      key: 'search',
      label: `Search: "${searchTerm.value.trim()}"`,
      target: null,
      clickable: false,
    })
  }

  return crumbs
})

const fetchCategories = async () => {
  categoriesLoading.value = true
  try {
    const { data } = await axios.get('/api/v1/store/categories')
    allCategories.value = data.data ?? []
  } catch (error) {
    console.error('Failed to load categories', error)
    allCategories.value = []
  } finally {
    categoriesLoading.value = false
  }
}

// Navigation: Reset to main categories view
const resetToCategories = () => {
  selectedParentCategory.value = null
  selectedSubcategory.value = null
  navigationLevel.value = 'categories'
  searchTerm.value = ''
  products.value = []
  availableFilters.value = []
  activeFilters.value = {}
  pagination.value = null
  currentPage.value = 1
  totalPages.value = 1
  productsLoading.value = false
}

const showParentCategoryList = () => {
  if (!selectedParentCategory.value) {
    return
  }

  selectedSubcategory.value = null
  navigationLevel.value = 'subcategories'
  products.value = []
  availableFilters.value = []
  activeFilters.value = {}
  pagination.value = null
  currentPage.value = 1
  totalPages.value = 1
  productsLoading.value = false
}

// Navigation: Select a parent category → show subcategories
const selectParentCategory = (category) => {
  selectedParentCategory.value = category
  selectedSubcategory.value = null
  navigationLevel.value = 'subcategories'
  products.value = []
  availableFilters.value = []
  activeFilters.value = {}
}

// Navigation: Select a subcategory → show filters + products
const selectSubcategory = async (subcategory) => {
  selectedSubcategory.value = subcategory
  navigationLevel.value = 'products'
  activeFilters.value = {}
  currentPage.value = 1

  // Load filters and products for this subcategory
  await Promise.all([
    fetchFilters(),
    fetchProducts()
  ])
}

const fetchFilters = async () => {
  if (!selectedSubcategory.value) return

  filtersLoading.value = true
  const params = {
    category_id: selectedSubcategory.value.id,
    ...(Object.keys(activeFilters.value).length > 0 ? { filters: activeFilters.value } : {}),
  }

  try {
    const response = await axios.get('/api/v1/store/filters', { params })
    availableFilters.value = response.data.data ?? []
  } catch (error) {
    console.error('Failed to load filters', error)
    availableFilters.value = []
  } finally {
    filtersLoading.value = false
  }
}

const fetchProducts = async () => {
  const trimmedQuery = searchTerm.value.trim()

  if (!selectedSubcategory.value && trimmedQuery === '') {
    return
  }

  productsLoading.value = true

  const endpoint = trimmedQuery.length ? '/api/v1/store/search' : '/api/v1/store/products'
  const params = {
    page: currentPage.value,
  }

  if (selectedSubcategory.value) {
    params.category_id = selectedSubcategory.value.id
  }

  if (trimmedQuery.length) {
    params.query = trimmedQuery
  }

  if (Object.keys(activeFilters.value).length > 0) {
    params.filters = activeFilters.value
  }

  try {
    const response = await axios.get(endpoint, { params })
    products.value = response.data.data ?? []
    pagination.value = response.data.meta ?? null
    currentPage.value = response.data.meta?.current_page ?? 1
    totalPages.value = response.data.meta?.last_page ?? 1
  } catch (error) {
    console.error('Failed to load products', error)
    products.value = []
    pagination.value = null
    currentPage.value = 1
    totalPages.value = 1
  } finally {
    productsLoading.value = false
  }
}

// listens to debounced search events from the search bar
const handleSearch = async (term) => {
  searchTerm.value = term
  currentPage.value = 1

  const trimmedQuery = term.trim()

  if (trimmedQuery === '') {
    if (!selectedSubcategory.value) {
      navigationLevel.value = 'categories'
      products.value = []
      pagination.value = null
      totalPages.value = 1
      productsLoading.value = false
      return
    }

    await fetchProducts()
    return
  }

  if (navigationLevel.value !== 'products') {
    navigationLevel.value = 'products'
  }

  await fetchProducts()
}

// Handle parametric filter changes
const handleFiltersUpdate = async (newFilters) => {
  activeFilters.value = newFilters
  currentPage.value = 1
  // Refetch filters to show only attributes available on filtered products
  await Promise.all([
    fetchFilters(),
    fetchProducts()
  ])
}

const removeFilterPill = async (pill) => {
  if (pill.type === 'search') {
    await handleSearch('')

    return
  }

  const updatedFilters = { ...activeFilters.value }
  const currentValue = updatedFilters[pill.attribute]

  if (Array.isArray(currentValue)) {
    const nextValues = currentValue.filter((value) => value !== pill.value)

    if (nextValues.length > 0) {
      updatedFilters[pill.attribute] = nextValues
    } else {
      delete updatedFilters[pill.attribute]
    }
  } else {
    delete updatedFilters[pill.attribute]
  }

  activeFilters.value = updatedFilters
  currentPage.value = 1

  await Promise.all([
    fetchFilters(),
    fetchProducts()
  ])
}

const handleClearFilters = async () => {
  activeFilters.value = {}
  currentPage.value = 1
  // Refetch filters to show all available attributes
  await Promise.all([
    fetchFilters(),
    fetchProducts()
  ])
}

const goToPreviousPage = () => {
  if (currentPage.value <= 1) return
  currentPage.value -= 1
  fetchProducts()
}

const goToNextPage = () => {
  if (currentPage.value >= totalPages.value) return
  currentPage.value += 1
  fetchProducts()
}

const handleBreadcrumbClick = (crumb) => {
  if (!crumb.clickable || !crumb.target) {
    return
  }

  if (crumb.target === 'catalog') {
    resetToCategories()
    return
  }

  if (crumb.target === 'parent') {
    showParentCategoryList()
  }
}

onMounted(() => {
  fetchCategories()
})
</script>

<template>
  <Head title="Store" />

  <AuthenticatedLayout>
    <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header with Quote CTA -->
        <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div class="space-y-2">
            <p class="text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 uppercase">Industrial Supply Catalog</p>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Browse products & build your quote</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Use the search bar and filters below to zero in on the exact specs you need.
            </p>
          </div>
          <Link
            :href="route('store.quote')"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75h-9A2.25 2.25 0 005.25 6v12a2.25 2.25 0 002.25 2.25h9A2.25 2.25 0 0018.75 18V6a2.25 2.25 0 00-2.25-2.25z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5h7.5M8.25 12h7.5M8.25 16.5h4.5" />
            </svg>
            <span>View Cart</span>
            <span
              v-if="quoteItemCount"
              class="ml-1 inline-flex items-center justify-center rounded-full bg-gray-900 dark:bg-blue-600 px-2 py-0.5 text-xs font-bold text-white"
            >
              {{ quoteItemCount }}
            </span>
          </Link>
        </div>

        <!-- Sticky Search + Context Bar -->
        <div class="sticky top-24 z-0 -mx-4 sm:-mx-6 lg:-mx-8 mb-8">
          <div class="bg-gray-50/95 dark:bg-gray-900/95 border border-gray-200 dark:border-gray-700 shadow-sm backdrop-blur rounded-b-2xl px-4 sm:px-6 lg:px-8 py-3 space-y-3">
            <SearchBar
              v-model="searchTerm"
              variant="inline"
              :loading="navigationLevel === 'products' ? productsLoading : false"
              placeholder="Search by product, SKU, or spec"
              @search="handleSearch"
            />

            <div
              v-if="breadcrumbTrail.length"
              class="flex flex-wrap items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-300"
            >
              <template v-for="(crumb, index) in breadcrumbTrail" :key="crumb.key">
                <button
                  v-if="crumb.clickable"
                  type="button"
                  class="text-blue-600 dark:text-blue-400 hover:underline"
                  @click="handleBreadcrumbClick(crumb)"
                >
                  {{ crumb.label }}
                </button>
                <span
                  v-else
                  :class="index === breadcrumbTrail.length - 1 ? 'text-gray-900 dark:text-gray-100 font-semibold' : ''"
                >
                  {{ crumb.label }}
                </span>
                <span
                  v-if="index < breadcrumbTrail.length - 1"
                  class="text-gray-400 dark:text-gray-500 mx-1"
                >
                  /
                </span>
              </template>
            </div>

            <div v-if="activeFilterPills.length" class="flex flex-wrap items-center gap-2">
              <button
                v-for="pill in activeFilterPills"
                :key="pill.id"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-xs font-medium text-gray-700 dark:text-gray-100 shadow-sm hover:border-blue-500 hover:text-blue-600 dark:hover:border-blue-400 dark:hover:text-blue-300 transition"
                @click="removeFilterPill(pill)"
              >
                <span>{{ pill.label }}</span>
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>

              <button
                v-if="hasRemovableFilters"
                type="button"
                class="ml-auto text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline"
                @click="handleClearFilters"
              >
                Clear filters
              </button>
            </div>
          </div>
        </div>

        <!-- Breadcrumb Navigation (McMaster-Carr style) -->
        <!-- VIEW 1: Main Categories (like McMaster homepage) -->
        <div v-if="navigationLevel === 'categories'" class="max-w-5xl">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Browse Catalog</h1>

          <div v-if="categoriesLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="animate-pulse">
              <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
            </div>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <button
              v-for="category in parentCategories"
              :key="category.id"
              @click="selectParentCategory(category)"
              class="text-left p-6 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all"
            >
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {{ category.name }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ category.description }}
              </p>
            </button>
          </div>
        </div>

        <!-- VIEW 2: Subcategories (after selecting main category) -->
        <div v-if="navigationLevel === 'subcategories'" class="max-w-5xl">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            {{ selectedParentCategory?.name }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mb-8">
            {{ selectedParentCategory?.description }}
          </p>

          <div class="space-y-3">
            <button
              v-for="subcategory in subcategories"
              :key="subcategory.id"
              @click="selectSubcategory(subcategory)"
              class="w-full text-left px-6 py-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all group"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 mb-1">
                    {{ subcategory.name }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ subcategory.description }}
                  </p>
                </div>
                <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </button>
          </div>
        </div>

        <!-- VIEW 3: Products with Filters (McMaster-Carr layout) -->
        <div v-if="showingProductView" class="grid grid-cols-12 gap-6">
          <!-- Left Sidebar: Parametric Filters (McMaster-Carr style) -->
          <div v-if="showFilters" class="col-span-12 lg:col-span-3">
            <ParametricFilters
              :filters="availableFilters"
              :active-filters="activeFilters"
              :loading="filtersLoading"
              @update:active-filters="handleFiltersUpdate"
              @clear="handleClearFilters"
            />
          </div>

          <!-- Right Side: Products -->
          <div :class="['col-span-12', showFilters ? 'lg:col-span-9' : 'lg:col-span-12']">
            <div v-if="isGlobalSearch" class="mb-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
              Showing results across all categories for "<span class="font-semibold text-gray-900 dark:text-white">{{ searchTerm }}</span>".
            </div>
            <ProductList
              :products="products"
              :loading="productsLoading"
              :meta="pagination"
              :is-search="isSearching"
              :current-page="currentPage"
              :total-pages="totalPages"
              @previous="goToPreviousPage"
              @next="goToNextPage"
              @clear-filters="handleClearFilters"
              @reset-navigation="resetToCategories"
            />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
