<script setup>
import { ref, computed } from 'vue'
import { Dialog, DialogPanel } from '@headlessui/vue'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'
import { usePage } from '@inertiajs/vue3'
import logo from '@images/logo-cleansed.svg'

const mobileMenuOpen = ref(false)
const page = usePage()

const isAdminAuthenticated = computed(() => page.props?.auth?.guards?.admin ?? false)
const adminDashboardHref = computed(() => {
  if (!isAdminAuthenticated.value) {
    return null
  }

  if (typeof route === 'function') {
    return route('filament.admin.pages.dashboard')
  }

  return '/admin'
})
</script>

<template>
  <header class="fixed top-0 inset-x-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-sm">
    <nav class="flex items-center justify-between p-2 lg:px-8" aria-label="Global">
      <!-- Logo -->
      <div class="flex lg:flex-1">
        <a href="#home" class="-m-1.5 p-1.5">
          <span class="sr-only">Colorado Supply & Procurement LLC</span>
          <img class="h-12 w-auto sm:h-14 lg:h-16 dark:hidden" :src="logo" alt="Colorado Supply & Procurement LLC" />
          <img class="h-12 w-auto sm:h-14 lg:h-16 hidden dark:block" :src="logo" alt="Colorado Supply & Procurement LLC" />
        </a>
      </div>

      <!-- Mobile menu button -->
      <div class="flex lg:hidden">
        <button
          type="button"
          class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-500 dark:text-gray-400"
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
      <div class="fixed inset-0 z-50" />
      <DialogPanel
        class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10 dark:bg-gray-900 dark:sm:ring-gray-100/10"
      >
        <div class="flex items-center justify-between">
          <a href="#home" class="-m-1.5 p-1.5" @click="mobileMenuOpen = false">
            <span class="sr-only">Colorado Supply & Procurement LLC</span>
            <img class="h-10 w-auto dark:hidden" :src="logo" alt="Colorado Supply & Procurement LLC" />
            <img class="h-10 w-auto hidden dark:block" :src="logo" alt="Colorado Supply & Procurement LLC" />
          </a>
          <button
            type="button"
            class="-m-2.5 rounded-md p-2.5 text-gray-700 dark:text-gray-400"
            @click="mobileMenuOpen = false"
          >
            <span class="sr-only">Close menu</span>
            <XMarkIcon class="size-6" aria-hidden="true" />
          </button>
        </div>

        <div class="mt-6 flow-root">
          <div class="-my-6 divide-y divide-gray-500/10 dark:divide-gray-500/25">
            <!-- Mobile Nav Links -->
            <div class="space-y-2 py-6">
              <a href="#home" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">
                Home
              </a>
              <a href="#about" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">
                About
              </a>
              <a href="#capabilities" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">
                Capabilities
              </a>
              <a href="#contact" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">
                Contact
              </a>
            </div>

            <!-- Mobile Auth Links -->
            <div class="py-6 space-y-2">
              <template v-if="isAdminAuthenticated">
                <a
                  v-if="adminDashboardHref"
                  :href="adminDashboardHref"
                  @click="mobileMenuOpen = false"
                  class="block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                >
                  Admin Dashboard
                </a>
              </template>
              <template v-else>
                <a :href="route('login')" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">
                  Log in
                </a>
                <a :href="route('register')" @click="mobileMenuOpen = false" class="block rounded-lg px-3 py-2.5 text-base font-semibold text-blue-700 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-white/5">
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




