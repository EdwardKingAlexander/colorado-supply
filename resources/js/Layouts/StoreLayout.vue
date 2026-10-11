<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Dialog, DialogPanel, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import CookieConsentBanner from '@/Components/Privacy/CookieConsentBanner.vue'
import { useCartStore } from '@/Stores/useCartStore'
import logo from '@images/logo-cleansed.svg'
import logoLight from '@images/logo-cleansed-light.svg'

const props = defineProps({
  // Current catalog search term (v-model:search).
  search: {
    type: String,
    default: '',
  },
  // When true the page handles searching itself (debounced `search` events).
  // Otherwise submitting the header search opens the catalog with ?q=.
  liveSearch: {
    type: Boolean,
    default: false,
  },
  searching: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:search', 'search'])

const page = usePage()
const cartStore = useCartStore()
const cartCount = computed(() => cartStore.itemCount.value)

const accountName = computed(() => page.props.auth?.user?.name ?? page.props.auth?.admin?.name ?? 'Account')
const accountEmail = computed(() => page.props.auth?.user?.email ?? page.props.auth?.admin?.email ?? null)
const isCustomer = computed(() => Boolean(page.props.auth?.user))
const isAdmin = computed(() => Boolean(page.props.auth?.admin))

const query = ref(props.search ?? '')
const searchInput = ref(null)
const mobileMenuOpen = ref(false)
let debounceTimer = null

watch(() => props.search, (value) => {
  if ((value ?? '') !== query.value) {
    query.value = value ?? ''
  }
})

const onInput = (event) => {
  query.value = event.target.value
  emit('update:search', query.value)

  if (!props.liveSearch) {
    return
  }

  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => emit('search', query.value.trim()), 350)
}

const submit = () => {
  clearTimeout(debounceTimer)
  const term = query.value.trim()

  if (props.liveSearch) {
    emit('search', term)
    return
  }

  router.visit(route('store.index', term ? { q: term } : {}))
}

const clearSearch = () => {
  query.value = ''
  emit('update:search', '')
  if (props.liveSearch) {
    clearTimeout(debounceTimer)
    emit('search', '')
  }
  searchInput.value?.focus()
}

// "/" focuses the catalog search from anywhere in the store.
const onKeydown = (event) => {
  if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
    return
  }

  const target = event.target
  const typing = target instanceof HTMLElement
    && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))

  if (!typing) {
    event.preventDefault()
    searchInput.value?.focus()
    searchInput.value?.select()
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  clearTimeout(debounceTimer)
})
</script>

<template>
  <div class="store-shell min-h-screen bg-st-bg text-st-ink">
    <header class="safe-top sticky top-0 z-40 border-b border-st-line bg-st-panel/95 backdrop-blur">
      <div class="mx-auto flex max-w-screen-2xl flex-wrap items-center gap-x-4 gap-y-3 px-4 py-3 sm:px-6 lg:flex-nowrap lg:gap-x-8 lg:px-8">
        <Link
          :href="route('store.index')"
          class="flex shrink-0 items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          aria-label="Colorado Supply store home"
        >
          <img :src="logo" alt="" class="h-9 w-auto dark:hidden" />
          <img :src="logoLight" alt="" class="hidden h-9 w-auto dark:block" />
        </Link>

        <!-- Account + cart (right on mobile row 1, far right on desktop) -->
        <div class="order-2 ml-auto flex items-center gap-1 lg:order-3 lg:ml-0">
          <Link
            :href="route('dashboard')"
            class="hidden min-h-11 items-center rounded-md px-3 text-sm font-medium text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:inline-flex"
          >
            Dashboard
          </Link>

          <Menu as="div" class="relative hidden lg:block">
            <MenuButton class="inline-flex min-h-11 items-center gap-1.5 rounded-md px-3 text-sm font-medium text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action">
              <span class="max-w-[10rem] truncate">{{ accountName }}</span>
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
            </MenuButton>
            <MenuItems class="absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-lg border border-st-line bg-st-panel py-1 shadow-2xl focus:outline-none">
              <div class="border-b border-st-line px-4 py-3">
                <p class="truncate text-sm font-semibold text-st-ink">{{ accountName }}</p>
                <p v-if="accountEmail" class="truncate text-sm text-st-muted">{{ accountEmail }}</p>
              </div>
              <MenuItem v-if="isCustomer" v-slot="{ active }">
                <Link :href="route('dashboard.reports')" :class="[active ? 'bg-st-sunk text-st-ink' : 'text-st-muted', 'block px-4 py-2.5 text-sm']">Order reports</Link>
              </MenuItem>
              <MenuItem v-if="isCustomer" v-slot="{ active }">
                <Link :href="route('profile.edit')" :class="[active ? 'bg-st-sunk text-st-ink' : 'text-st-muted', 'block px-4 py-2.5 text-sm']">Profile</Link>
              </MenuItem>
              <MenuItem v-if="isAdmin" v-slot="{ active }">
                <a :href="route('filament.admin.pages.dashboard')" :class="[active ? 'bg-st-sunk text-st-ink' : 'text-st-muted', 'block px-4 py-2.5 text-sm']">Admin panel</a>
              </MenuItem>
              <MenuItem v-slot="{ active }">
                <Link :href="route('logout')" method="post" as="button" :class="[active ? 'bg-st-sunk text-st-ink' : 'text-st-muted', 'block w-full px-4 py-2.5 text-left text-sm']">Log out</Link>
              </MenuItem>
            </MenuItems>
          </Menu>

          <Link
            :href="route('store.cart')"
            class="relative inline-flex min-h-11 items-center gap-2 rounded-md border border-st-line px-3 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
            :aria-label="cartCount ? `Cart, ${cartCount} items` : 'Cart'"
          >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.39c.51 0 .96.34 1.09.84l.38 1.44m0 0L6.86 12h11.07l2.07-6.72H5.11zM6.86 12 6 15.75h12.75M8.25 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm10.5 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
            <span class="hidden sm:inline">Cart</span>
            <span
              v-if="cartCount"
              class="st-num inline-flex min-w-6 items-center justify-center rounded bg-st-signal px-1.5 py-0.5 text-xs font-bold text-[#1a1300]"
            >{{ cartCount }}</span>
          </Link>

          <button
            type="button"
            class="inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action lg:hidden"
            aria-label="Open account menu"
            :aria-expanded="mobileMenuOpen"
            @click="mobileMenuOpen = true"
          >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
          </button>
        </div>

        <!-- Catalog search: full row on mobile, flexible middle column on desktop -->
        <form
          role="search"
          class="order-3 w-full lg:order-2 lg:max-w-3xl lg:flex-1"
          @submit.prevent="submit"
        >
          <label for="store-search" class="sr-only">Search the catalog</label>
          <div class="group relative flex items-center">
            <svg class="pointer-events-none absolute left-3.5 h-5 w-5 text-st-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
            </svg>
            <input
              id="store-search"
              ref="searchInput"
              :value="query"
              type="search"
              name="q"
              autocomplete="off"
              enterkeyhint="search"
              placeholder="Search by part number, product name, or spec"
              class="h-12 w-full rounded-lg border border-st-line bg-st-sunk pl-11 pr-24 text-base text-st-ink placeholder:text-st-muted focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40 [&::-webkit-search-cancel-button]:hidden"
              @input="onInput"
            />
            <div class="absolute right-2 flex items-center gap-1">
              <span v-if="searching" class="mr-1 h-4 w-4 animate-spin rounded-full border-2 border-st-line border-t-st-action" aria-hidden="true" />
              <button
                v-if="query"
                type="button"
                class="inline-flex h-9 items-center rounded px-2 text-sm text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                @click="clearSearch"
              >
                Clear
              </button>
              <kbd v-else class="hidden rounded border border-st-line px-1.5 py-0.5 text-xs text-st-muted sm:inline-block" title="Press / to search">/</kbd>
            </div>
          </div>
        </form>
      </div>
    </header>

    <main>
      <slot />
    </main>

    <Dialog class="lg:hidden" :open="mobileMenuOpen" @close="mobileMenuOpen = false">
      <div class="fixed inset-0 z-40 bg-black/60" aria-hidden="true" />
      <DialogPanel class="store-shell safe-y fixed inset-y-0 right-0 z-50 flex w-full max-w-drawer flex-col bg-st-panel text-st-ink shadow-2xl">
        <div class="flex h-16 items-center justify-between border-b border-st-line px-4">
          <div class="min-w-0">
            <p class="truncate text-base font-semibold">{{ accountName }}</p>
            <p v-if="accountEmail" class="truncate text-sm text-st-muted">{{ accountEmail }}</p>
          </div>
          <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink" aria-label="Close account menu" @click="mobileMenuOpen = false">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
          </button>
        </div>
        <nav class="flex flex-col p-2 text-base">
          <Link :href="route('store.index')" class="rounded-md px-3 py-3 hover:bg-st-sunk" @click="mobileMenuOpen = false">Catalog</Link>
          <Link :href="route('store.cart')" class="rounded-md px-3 py-3 hover:bg-st-sunk" @click="mobileMenuOpen = false">Cart</Link>
          <Link :href="route('dashboard')" class="rounded-md px-3 py-3 hover:bg-st-sunk" @click="mobileMenuOpen = false">Dashboard</Link>
          <Link v-if="isCustomer" :href="route('dashboard.reports')" class="rounded-md px-3 py-3 hover:bg-st-sunk" @click="mobileMenuOpen = false">Order reports</Link>
          <Link v-if="isCustomer" :href="route('profile.edit')" class="rounded-md px-3 py-3 hover:bg-st-sunk" @click="mobileMenuOpen = false">Profile</Link>
          <a v-if="isAdmin" :href="route('filament.admin.pages.dashboard')" class="rounded-md px-3 py-3 hover:bg-st-sunk">Admin panel</a>
          <Link :href="route('logout')" method="post" as="button" class="rounded-md px-3 py-3 text-left hover:bg-st-sunk" @click="mobileMenuOpen = false">Log out</Link>
        </nav>
      </DialogPanel>
    </Dialog>

    <CookieConsentBanner />
  </div>
</template>
