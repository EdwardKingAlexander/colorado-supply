<script setup>
import { ref, computed } from 'vue'
import { Dialog, DialogPanel } from '@headlessui/vue'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'
import { usePage } from '@inertiajs/vue3'
import logo from '@images/logo-cleansed.svg'
import logoLight from '@images/logo-cleansed-light.svg'

const mobileMenuOpen = ref(false)
const page = usePage()

const isAdminAuthenticated = computed(() => page.props?.auth?.guards?.admin ?? false)
const isUserAuthenticated = computed(() => Boolean(page.props?.auth?.user))
const shouldShowStoreLink = computed(() => isUserAuthenticated.value || isAdminAuthenticated.value)
const adminDashboardHref = computed(() => {
  if (!isAdminAuthenticated.value) {
    return null
  }

  if (typeof route === 'function') {
    return route('filament.admin.pages.dashboard')
  }

  return '/admin'
})
const storeHref = computed(() => {
  if (!shouldShowStoreLink.value) {
    return null
  }

  if (typeof route === 'function') {
    return route('store.index')
  }

  return '/store'
})
</script>

<template>
  <header class="safe-top fixed inset-x-0 top-0 z-50 border-b border-gray-200/80 bg-white/95 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-gray-900/95">
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Global">
      <!-- Logo -->
      <div class="flex lg:flex-1">
        <a href="#home" class="flex min-h-12 items-center py-1 focus:outline-none focus:ring-2 focus:ring-amber-600 focus:ring-offset-2">
          <span class="sr-only">Colorado Supply & Procurement LLC</span>
          <img class="h-11 w-auto dark:hidden" :src="logo" alt="" aria-hidden="true" width="193" height="64" />
          <img class="hidden h-11 w-auto dark:block" :src="logoLight" alt="" aria-hidden="true" width="193" height="64" />
        </a>
      </div>

      <!-- Mobile menu button -->
      <div class="flex lg:hidden">
        <button
          type="button"
          class="inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-600 dark:text-gray-200 dark:hover:bg-white/10"
          :aria-expanded="mobileMenuOpen"
          aria-controls="public-mobile-menu"
          @click="mobileMenuOpen = true"
        >
          <span class="sr-only">Open main menu</span>
          <Bars3Icon class="size-6" aria-hidden="true" />
        </button>
      </div>

      <!-- Desktop links -->
      <div class="hidden lg:flex lg:gap-x-12">
        <a href="#home" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">Home</a>
        <a href="#about" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">About</a>
        <a href="#capabilities" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">Capabilities</a>
        <a :href="route('repair-services.index')" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">Repair Services</a>
        <a href="#contact" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">Contact</a>
      </div>

      <!-- Desktop auth links -->
      <div class="hidden lg:flex lg:flex-1 lg:justify-end gap-x-6">
        <template v-if="isAdminAuthenticated">
          <a
            v-if="adminDashboardHref"
            :href="adminDashboardHref"
            class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400"
          >
            Admin Dashboard
          </a>
          <a
            v-if="storeHref"
            :href="storeHref"
            class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400"
          >
            Store
          </a>
        </template>
        <template v-else-if="shouldShowStoreLink">
          <a
            v-if="storeHref"
            :href="storeHref"
            class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400"
          >
            Store
          </a>
        </template>
        <template v-else>
          <a :href="route('login')" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">
            Log in <span aria-hidden="true">&rarr;</span>
          </a>
          <a :href="route('register')" class="text-sm font-semibold text-blue-700 dark:text-blue-400 hover:underline">
            Register
          </a>
        </template>
      </div>
    </nav>

    <!-- Mobile menu -->
    <Dialog class="lg:hidden" @close="mobileMenuOpen = false" :open="mobileMenuOpen">
      <div class="fixed inset-0 z-50 bg-gray-950/55" aria-hidden="true" />
      <DialogPanel
        id="public-mobile-menu"
        class="safe-y fixed inset-y-0 right-0 z-50 w-full max-w-drawer overflow-y-auto bg-white px-4 py-3 shadow-2xl ring-1 ring-gray-900/10 dark:bg-gray-900 dark:ring-gray-100/10 sm:px-6"
      >
        <div class="flex h-14 items-center justify-between">
          <a href="#home" class="flex min-h-12 items-center py-1 focus:outline-none focus:ring-2 focus:ring-amber-600" @click="mobileMenuOpen = false">
            <span class="sr-only">Colorado Supply & Procurement LLC</span>
            <img class="h-10 w-auto dark:hidden" :src="logo" alt="" aria-hidden="true" width="120" height="40" />
            <img class="hidden h-10 w-auto dark:block" :src="logoLight" alt="" aria-hidden="true" width="120" height="40" />
          </a>
          <button
            type="button"
            class="inline-flex h-12 w-12 items-center justify-center rounded-md text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-600 dark:text-gray-200 dark:hover:bg-white/10"
            @click="mobileMenuOpen = false"
          >
            <span class="sr-only">Close menu</span>
            <XMarkIcon class="size-6" aria-hidden="true" />
          </button>
        </div>

        <div class="mt-4 flow-root">
          <div class="divide-y divide-gray-500/10 dark:divide-gray-500/25">
            <!-- Mobile Nav Links -->
            <div class="space-y-1 py-4">
              <a href="#home" @click="mobileMenuOpen = false" class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5">
                Home
              </a>
              <a href="#about" @click="mobileMenuOpen = false" class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5">
                About
              </a>
              <a href="#capabilities" @click="mobileMenuOpen = false" class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5">
                Capabilities
              </a>
              <a :href="route('repair-services.index')" @click="mobileMenuOpen = false" class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5">
                Repair Services
              </a>
              <a href="#contact" @click="mobileMenuOpen = false" class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5">
                Contact
              </a>
            </div>

            <!-- Mobile Auth Links -->
            <div class="space-y-1 py-4">
              <template v-if="isAdminAuthenticated">
                <a
                  v-if="adminDashboardHref"
                  :href="adminDashboardHref"
                  @click="mobileMenuOpen = false"
                  class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5"
                >
                  Admin Dashboard
                </a>
                <a
                  v-if="storeHref"
                  :href="storeHref"
                  @click="mobileMenuOpen = false"
                  class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5"
                >
                  Store
                </a>
              </template>
              <template v-else-if="shouldShowStoreLink">
                <a
                  v-if="storeHref"
                  :href="storeHref"
                  @click="mobileMenuOpen = false"
                  class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5"
                >
                  Store
                </a>
              </template>
              <template v-else>
                <a :href="route('login')" @click="mobileMenuOpen = false" class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-white dark:hover:bg-white/5">
                  Log in
                </a>
                <a :href="route('register')" @click="mobileMenuOpen = false" class="flex min-h-[52px] items-center rounded-md px-3 py-3 text-base font-semibold text-blue-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-600 dark:text-blue-400 dark:hover:bg-white/5">
                  Register
                </a>
              </template>
            </div>
          </div>
        </div>
      </DialogPanel>
    </Dialog>
  </header>
</template>
