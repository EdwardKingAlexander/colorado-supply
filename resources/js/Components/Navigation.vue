<script setup>
import { ref } from 'vue'
import { Dialog, DialogPanel } from '@headlessui/vue'
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline'
import logo from '/public/img/logo-cleansed.svg'

const mobileMenuOpen = ref(false)
</script>

<template>
  <header class="fixed top-0 inset-x-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md shadow-sm">
    <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
      <!-- Logo -->
      <div class="flex lg:flex-1">
        <a href="#home" class="-m-1.5 p-1.5">
          <span class="sr-only">Colorado Supply & Procurement LLC</span>
          <img class="h-20 w-auto dark:hidden" :src="logo" alt="Logo" />
          <img class="h-20 w-auto hidden dark:block" :src="logo" alt="Logo" />
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
        <a :href="route('login')" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">
          Log in <span aria-hidden="true">&rarr;</span>
        </a>
        <a :href="route('register')" class="text-sm font-semibold text-blue-700 dark:text-blue-400 hover:underline">
          Register
        </a>
      </div>
    </nav>

    <!-- Mobile menu -->
    <Dialog class="lg:hidden" @close="mobileMenuOpen = false" :open="mobileMenuOpen">
      <div class="fixed inset-0 z-50" />
      <DialogPanel
        class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10 dark:bg-gray-900 dark:sm:ring-gray-100/10"
      >
        <div class="flex items-center justify-between">
          <a href="#home" class="-m-1.5 p-1.5">
            <span class="sr-only">Colorado Supply & Procurement LLC</span>
            <img class="h-12 w-auto dark:hidden" :src="logo" alt="Logo" />
            <img class="h-12 w-auto hidden dark:block" :src="logo" alt="Logo" />
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
            <div class="space-y-2 py-6">
              <a href="#home" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Home</a>
              <a href="#about" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">About</a>
              <a href="#capabilities" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Capabilities</a>
              <a href="#contact" class="block rounded-lg px-3 py-2 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Contact</a>
            </div>
            <div class="py-6 space-y-2">
              <a :href="route('login')" class="block rounded-lg px-3 py-2.5 text-base font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Log in</a>
              <a :href="route('register')" class="block rounded-lg px-3 py-2.5 text-base font-semibold text-blue-700 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-white/5">Register</a>
            </div>
          </div>
        </div>
      </DialogPanel>
    </Dialog>
  </header>
</template>
