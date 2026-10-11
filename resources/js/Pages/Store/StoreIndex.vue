<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Dialog, DialogPanel } from '@headlessui/vue'
import StoreLayout from '@/Layouts/StoreLayout.vue'
import ParametricFilters from '@/Components/Store/ParametricFilters.vue'
import ProductList from '@/Components/Store/ProductList.vue'

const props = defineProps({
  location: {
    type: Object,
    default: null,
  },
})

const page = usePage()

// Catalog tree
const allCategories = ref([])
const categoriesLoading = ref(true)

// Where the shopper is. Derived from the URL (?department=, ?category=, ?q=)
// so the back button and shared links land on the same view.
const selectedDepartment = ref(null)
const selectedCategory = ref(null)
const searchTerm = ref('')
const view = ref('index') // 'index' | 'department' | 'products'

// Products + parametric filters for the current view
const products = ref([])
const productsLoading = ref(false)
const pagination = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const availableFilters = ref([])
const activeFilters = ref({})
const filtersLoading = ref(false)
const mobileFiltersOpen = ref(false)
let loadedProductsKey = null

const isSearching = computed(() => searchTerm.value.length > 0)
const isGlobalSearch = computed(() => view.value === 'products' && !selectedCategory.value && isSearching.value)
const showFilters = computed(() => view.value === 'products' && Boolean(selectedCategory.value))

const activeFilterCount = computed(() => Object.values(activeFilters.value).reduce(
  (count, value) => count + (Array.isArray(value) ? value.length : 1),
  0,
))

// --- Catalog tree -----------------------------------------------------------

const byName = (a, b) => a.name.localeCompare(b.name)

const childrenOf = (category) => allCategories.value
  .filter(cat => cat.parent_id === category.id)
  .sort(byName)

const departments = computed(() => allCategories.value
  .filter(cat => !cat.parent_id)
  .map((department) => {
    const children = childrenOf(department)
    const total = (department.products_count ?? 0)
      + children.reduce((sum, child) => sum + (child.products_count ?? 0), 0)

    return { ...department, children, total }
  })
  .filter(department => department.total > 0)
  .sort(byName))

const catalogTotal = computed(() => departments.value.reduce((sum, d) => sum + d.total, 0))

const findBySlug = (slug) => (slug ? allCategories.value.find(cat => cat.slug === slug) ?? null : null)
const departmentFor = (category) => {
  if (!category) {
    return null
  }

  const rootId = category.parent_id ?? category.id

  return departments.value.find(d => d.id === rootId) ?? null
}

const currentDepartment = computed(() => departmentFor(selectedCategory.value) ?? selectedDepartment.value)
const siblingCategories = computed(() => currentDepartment.value?.children ?? [])

// --- URL state ----------------------------------------------------------------

const visit = (params, { replace = false } = {}) => {
  const query = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value) {
      query.set(key, value)
    }
  })

  const qs = query.toString()
  const url = window.location.pathname + (qs ? `?${qs}` : '')
  const options = { url, preserveState: true, preserveScroll: replace }

  if (replace) {
    router.replace(options)
  } else {
    router.push(options)
  }
}

const openIndex = () => visit({})
const openDepartment = (department) => {
  // A department without subcategories goes straight to its products.
  if (!department.children?.length) {
    visit({ category: department.slug })

    return
  }

  visit({ department: department.slug })
}
const openCategory = (category) => visit({ category: category.slug })

const applyUrl = async () => {
  if (categoriesLoading.value) {
    return
  }

  const params = new URL(page.url, window.location.origin).searchParams
  const category = findBySlug(params.get('category'))
  const department = category ? departmentFor(category) : departments.value.find(d => d.slug === params.get('department')) ?? null
  const term = (params.get('q') ?? '').trim()

  selectedCategory.value = category
  selectedDepartment.value = department
  searchTerm.value = term

  if (!category && !term) {
    view.value = department ? 'department' : 'index'
    loadedProductsKey = null
    products.value = []
    availableFilters.value = []
    activeFilters.value = {}
    mobileFiltersOpen.value = false

    return
  }

  view.value = 'products'
  const key = `${category?.id ?? ''}|${term}`

  if (key === loadedProductsKey) {
    return
  }

  loadedProductsKey = key
  activeFilters.value = {}
  currentPage.value = 1
  availableFilters.value = []

  await Promise.all([category ? fetchFilters() : Promise.resolve(), fetchProducts()])
}

watch(() => page.url, applyUrl)

// --- Data -----------------------------------------------------------------------

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

const fetchFilters = async () => {
  if (!selectedCategory.value) {
    return
  }

  filtersLoading.value = true

  try {
    const { data } = await axios.get('/api/v1/store/filters', {
      params: {
        category_id: selectedCategory.value.id,
        ...(Object.keys(activeFilters.value).length > 0 ? { filters: activeFilters.value } : {}),
      },
    })
    availableFilters.value = data.data ?? []
  } catch (error) {
    console.error('Failed to load filters', error)
    availableFilters.value = []
  } finally {
    filtersLoading.value = false
  }
}

const fetchProducts = async () => {
  if (!selectedCategory.value && !isSearching.value) {
    return
  }

  productsLoading.value = true
  const params = { page: currentPage.value }

  if (selectedCategory.value) {
    params.category_id = selectedCategory.value.id
  }

  if (isSearching.value) {
    params.query = searchTerm.value
  }

  if (Object.keys(activeFilters.value).length > 0) {
    params.filters = activeFilters.value
  }

  try {
    const endpoint = isSearching.value ? '/api/v1/store/search' : '/api/v1/store/products'
    const { data } = await axios.get(endpoint, { params })
    products.value = data.data ?? []
    pagination.value = data.meta ?? null
    currentPage.value = data.meta?.current_page ?? 1
    totalPages.value = data.meta?.last_page ?? 1
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

// --- Interactions ---------------------------------------------------------------

// Header search: typing refines in place (replace), a new search adds history.
const handleSearch = (term) => {
  const trimmed = term.trim()

  if (trimmed === searchTerm.value) {
    return
  }

  visit(
    { category: selectedCategory.value?.slug, q: trimmed },
    { replace: isSearching.value && trimmed.length > 0 },
  )
}

const refreshFiltersAndProducts = () => {
  currentPage.value = 1

  return Promise.all([fetchFilters(), fetchProducts()])
}

const handleFiltersUpdate = (filters) => {
  activeFilters.value = filters

  return refreshFiltersAndProducts()
}

const handleClearFilters = () => {
  activeFilters.value = {}

  return refreshFiltersAndProducts()
}

const removeFilterValue = (attribute, value) => {
  const next = { ...activeFilters.value }
  const current = next[attribute]

  if (Array.isArray(current) && current.length > 1) {
    next[attribute] = current.filter(v => v !== value)
  } else {
    delete next[attribute]
  }

  return handleFiltersUpdate(next)
}

const filterPills = computed(() => Object.entries(activeFilters.value).flatMap(([attribute, value]) => {
  const values = Array.isArray(value) ? value : [value]

  return values.map(v => ({
    key: `${attribute}:${v}`,
    attribute,
    value: v,
    label: `${attribute}: ${v === 'true' ? 'Yes' : v === 'false' ? 'No' : v}`,
  }))
}))

const scrollToResults = () => {
  document.getElementById('catalog-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const goToPage = async (pageNumber) => {
  currentPage.value = pageNumber
  await fetchProducts()
  scrollToResults()
}

const pageTitle = computed(() => {
  if (view.value === 'products') {
    if (selectedCategory.value) {
      return selectedCategory.value.name
    }

    return `Results for “${searchTerm.value}”`
  }

  if (view.value === 'department') {
    return currentDepartment.value?.name ?? 'Catalog'
  }

  return 'Catalog'
})

onMounted(async () => {
  await fetchCategories()
  await applyUrl()
})
</script>

<template>
  <Head :title="view === 'index' ? 'Store' : `${pageTitle} – Store`" />

  <StoreLayout
    :search="searchTerm"
    live-search
    :searching="productsLoading && isSearching"
    @search="handleSearch"
  >
    <div class="mx-auto max-w-screen-2xl px-4 pb-20 pt-6 sm:px-6 lg:px-8 lg:pt-8">
      <!-- Breadcrumb -->
      <nav v-if="view !== 'index'" aria-label="Breadcrumb" class="mb-3 text-sm">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-st-muted">
          <li>
            <button type="button" class="rounded hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action" @click="openIndex">Catalog</button>
          </li>
          <template v-if="currentDepartment && (view === 'products' || view === 'department')">
            <li aria-hidden="true">/</li>
            <li>
              <button
                v-if="view === 'products' && currentDepartment.children.length"
                type="button"
                class="rounded hover:text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                @click="openDepartment(currentDepartment)"
              >
                {{ currentDepartment.name }}
              </button>
              <span v-else aria-current="page" class="text-st-ink">{{ currentDepartment.name }}</span>
            </li>
          </template>
          <template v-if="selectedCategory && selectedCategory.parent_id">
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="text-st-ink">{{ selectedCategory.name }}</li>
          </template>
        </ol>
      </nav>

      <!-- ============ CATALOG INDEX (home) ============ -->
      <template v-if="view === 'index'">
        <div class="mb-8 flex flex-col gap-3 border-b border-st-line pb-6 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h1 class="st-display text-4xl font-extrabold leading-none text-st-ink sm:text-5xl">Catalog</h1>
            <p class="mt-3 max-w-2xl text-base text-st-muted">
              <template v-if="!categoriesLoading && departments.length">
                <span class="st-num">{{ catalogTotal }}</span> products across
                <span class="st-num">{{ departments.length }}</span> departments.
              </template>
              Pick a category to filter by spec, or search by part number above.
            </p>
          </div>
          <p v-if="location" class="text-sm text-st-muted">
            Stock shown for <span class="font-semibold text-st-ink">{{ location.name }}</span>
          </p>
        </div>

        <div v-if="categoriesLoading" class="columns-1 gap-x-12 sm:columns-2 lg:columns-3 2xl:columns-4" aria-busy="true">
          <div v-for="i in 8" :key="i" class="mb-10 break-inside-avoid border-t-2 border-st-line pt-3">
            <div class="mb-4 h-5 w-2/3 animate-pulse rounded bg-st-panel" />
            <div v-for="j in 4" :key="j" class="mb-3 h-3.5 w-full animate-pulse rounded bg-st-panel" />
          </div>
        </div>

        <div v-else-if="departments.length" class="columns-1 gap-x-12 sm:columns-2 lg:columns-3 2xl:columns-4">
          <section
            v-for="department in departments"
            :key="department.id"
            class="mb-10 break-inside-avoid border-t-2 border-st-ink pt-3"
            :aria-labelledby="`dept-${department.id}`"
          >
            <h2 :id="`dept-${department.id}`" class="mb-2">
              <button
                type="button"
                class="group flex w-full items-baseline justify-between gap-3 rounded text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                @click="openDepartment(department)"
              >
                <span class="st-display text-lg font-extrabold leading-tight text-st-ink group-hover:text-st-action">{{ department.name }}</span>
                <span class="st-num shrink-0 text-sm text-st-muted">{{ department.total }}</span>
              </button>
            </h2>
            <ul>
              <li v-for="category in department.children" :key="category.id">
                <button
                  type="button"
                  class="group flex min-h-11 w-full items-baseline rounded py-1.5 text-left text-[0.9375rem] text-st-ink/85 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action sm:min-h-0"
                  @click="openCategory(category)"
                >
                  <span class="group-hover:text-st-action group-hover:underline">{{ category.name }}</span>
                  <span class="st-leader" aria-hidden="true" />
                  <span class="st-num text-sm text-st-muted">
                    {{ category.products_count ?? '' }}<span class="sr-only"> products</span>
                  </span>
                </button>
              </li>
            </ul>
          </section>
        </div>

        <div v-else class="max-w-xl rounded-lg border border-st-line bg-st-panel p-6">
          <p class="text-lg font-bold">The catalog is empty right now</p>
          <p class="mt-1 text-st-muted">Products will show here as soon as they're added. You can still search by part number above.</p>
        </div>

        <!-- Beyond the catalog -->
        <div class="mt-6 grid gap-8 border-t border-st-line pt-8 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <h2 class="text-base font-bold text-st-ink">Can't find a part?</h2>
            <p class="mt-1 text-[0.9375rem] text-st-muted">Send us the part number, NSN, or spec sheet and we'll source it for you.</p>
            <a href="/#contact" class="mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0">Request a sourcing quote</a>
          </div>
          <div>
            <h2 class="text-base font-bold text-st-ink">Need equipment repaired?</h2>
            <p class="mt-1 text-[0.9375rem] text-st-muted">Tell us what broke and we will follow up with next steps.</p>
            <Link :href="route('repair-services.index')" class="mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0">Start a repair request</Link>
          </div>
          <div v-if="$page.props.auth?.user">
            <h2 class="text-base font-bold text-st-ink">Reordering?</h2>
            <p class="mt-1 text-[0.9375rem] text-st-muted">See what your company has bought before and export it for your records.</p>
            <Link :href="route('dashboard.reports')" class="mt-2 inline-flex min-h-11 items-center font-semibold text-st-action hover:underline sm:min-h-0">View order history</Link>
          </div>
        </div>
      </template>

      <!-- ============ DEPARTMENT ============ -->
      <template v-else-if="view === 'department' && currentDepartment">
        <div class="mb-8 border-b border-st-line pb-6">
          <h1 class="st-display text-3xl font-extrabold leading-tight text-st-ink sm:text-4xl">{{ currentDepartment.name }}</h1>
          <p class="mt-2 text-base text-st-muted">
            <span class="st-num">{{ currentDepartment.total }}</span> products in
            <span class="st-num">{{ currentDepartment.children.length }}</span> categories
          </p>
        </div>

        <ul class="grid max-w-5xl gap-x-12 sm:grid-cols-2">
          <li v-for="category in currentDepartment.children" :key="category.id" class="border-b border-st-line">
            <button
              type="button"
              class="group flex min-h-14 w-full items-baseline py-3 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
              @click="openCategory(category)"
            >
              <span class="text-lg font-semibold text-st-ink group-hover:text-st-action group-hover:underline">{{ category.name }}</span>
              <span class="st-leader" aria-hidden="true" />
              <span class="st-num text-st-muted">{{ category.products_count }}<span class="sr-only"> products</span></span>
            </button>
          </li>
        </ul>
      </template>

      <!-- ============ PRODUCTS ============ -->
      <template v-else-if="view === 'products'">
        <div class="mb-5 flex flex-col gap-1">
          <h1 class="st-display text-2xl font-extrabold leading-tight text-st-ink sm:text-3xl">{{ pageTitle }}</h1>
          <p v-if="isGlobalSearch" class="text-base text-st-muted">Searching every department.</p>
          <p v-else-if="isSearching" class="text-base text-st-muted">
            Matching “{{ searchTerm }}” in {{ selectedCategory?.name }}.
            <button type="button" class="font-semibold text-st-action hover:underline" @click="handleSearch('')">Show all {{ selectedCategory?.name }}</button>
          </p>
        </div>

        <!-- Sibling categories as chips on small screens -->
        <div v-if="showFilters && siblingCategories.length > 1" class="-mx-4 mb-4 overflow-x-auto px-4 lg:hidden">
          <div class="flex gap-2 pb-1">
            <button
              v-for="category in siblingCategories"
              :key="category.id"
              type="button"
              class="inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 text-sm font-medium"
              :class="category.id === selectedCategory?.id ? 'border-st-signal bg-st-signal/10 text-st-ink' : 'border-st-line text-st-muted hover:text-st-ink'"
              :aria-current="category.id === selectedCategory?.id ? 'page' : undefined"
              @click="openCategory(category)"
            >
              {{ category.name }}
            </button>
          </div>
        </div>

        <div class="grid gap-6 xl:gap-8" :class="{ 'lg:grid-cols-[16rem_minmax(0,1fr)]': showFilters }">
          <!-- Left rail -->
          <aside v-if="showFilters" class="hidden lg:block" aria-label="Refine products">
            <div class="sticky top-24 max-h-[calc(100vh-7rem)] space-y-6 overflow-y-auto overflow-x-hidden pb-6 pr-1">
              <nav v-if="siblingCategories.length > 1" :aria-label="`${currentDepartment?.name} categories`">
                <h2 class="mb-1 text-base font-bold text-st-ink">{{ currentDepartment?.name }}</h2>
                <ul>
                  <li v-for="category in siblingCategories" :key="category.id">
                    <button
                      type="button"
                      class="flex min-h-9 w-full items-baseline border-l-2 py-1 pl-3 text-left text-[0.9375rem] focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                      :class="category.id === selectedCategory?.id ? 'border-st-signal font-semibold text-st-ink' : 'border-transparent text-st-muted hover:text-st-ink'"
                      :aria-current="category.id === selectedCategory?.id ? 'page' : undefined"
                      @click="openCategory(category)"
                    >
                      <span>{{ category.name }}</span>
                      <span class="st-num ml-auto pl-2 text-sm text-st-muted">{{ category.products_count }}</span>
                    </button>
                  </li>
                </ul>
              </nav>

              <ParametricFilters
                :filters="availableFilters"
                :active-filters="activeFilters"
                :loading="filtersLoading"
                @update:active-filters="handleFiltersUpdate"
                @clear="handleClearFilters"
              />
            </div>
          </aside>

          <div id="catalog-results" class="min-w-0 scroll-mt-28">
            <div v-if="showFilters || filterPills.length" class="mb-3 flex flex-wrap items-center gap-2">
              <button
                v-if="showFilters"
                type="button"
                class="inline-flex min-h-11 items-center gap-2 rounded-md border border-st-line bg-st-panel px-4 text-sm font-semibold text-st-ink lg:hidden"
                @click="mobileFiltersOpen = true"
              >
                Filter by spec
                <span v-if="activeFilterCount" class="st-num rounded bg-st-fill px-1.5 text-xs text-white">{{ activeFilterCount }}</span>
              </button>
              <button
                v-for="pill in filterPills"
                :key="pill.key"
                type="button"
                class="inline-flex min-h-9 items-center gap-2 rounded-full border border-st-line bg-st-panel px-3 text-sm text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                :aria-label="`Remove filter ${pill.label}`"
                @click="removeFilterValue(pill.attribute, pill.value)"
              >
                {{ pill.label }}
                <svg class="h-3.5 w-3.5 text-st-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
              </button>
            </div>

            <ProductList
              :products="products"
              :loading="productsLoading"
              :meta="pagination"
              :is-search="isSearching"
              :has-filters="activeFilterCount > 0"
              :current-page="currentPage"
              :total-pages="totalPages"
              @previous="goToPage(currentPage - 1)"
              @next="goToPage(currentPage + 1)"
              @clear-filters="handleClearFilters"
              @reset-navigation="openIndex"
            />
          </div>
        </div>
      </template>
    </div>

    <Dialog class="lg:hidden" :open="mobileFiltersOpen" @close="mobileFiltersOpen = false">
      <div class="fixed inset-0 z-40 bg-black/60" aria-hidden="true" />
      <DialogPanel class="store-shell safe-y fixed inset-y-0 right-0 z-50 flex w-full max-w-drawer flex-col bg-st-panel text-st-ink shadow-2xl">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-st-line px-4">
          <h2 class="text-lg font-bold">Filter {{ selectedCategory?.name }}</h2>
          <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink" aria-label="Close filters" @click="mobileFiltersOpen = false">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
          </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-4 py-2">
          <ParametricFilters :filters="availableFilters" :active-filters="activeFilters" :loading="filtersLoading" @update:active-filters="handleFiltersUpdate" @clear="handleClearFilters" />
        </div>
        <div class="safe-bottom shrink-0 border-t border-st-line p-4">
          <button type="button" class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-st-fill px-4 text-base font-semibold text-white hover:bg-st-fill/85" @click="mobileFiltersOpen = false">
            Show {{ pagination?.total ?? products.length }} products
          </button>
        </div>
      </DialogPanel>
    </Dialog>
  </StoreLayout>
</template>
