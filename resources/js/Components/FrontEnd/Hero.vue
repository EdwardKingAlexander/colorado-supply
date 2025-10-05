<script setup>
import { ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import pipelineImageUrl from '@images/pipeline.jpg'

const props = defineProps({
  lightImage: {
    type: String,
    default: pipelineImageUrl,
  },
  darkImage: {
    type: String,
    default: pipelineImageUrl,
  },
})

const isModalOpen = ref(false)
const pdfLoaded = ref(false)

const openModal = () => {
  isModalOpen.value = true
  pdfLoaded.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}
</script>

<template>
  <div class="relative isolate overflow-hidden min-h-[100dvh] flex items-center pt-24 safe-top">
    <!-- Dark mode background -->
    <div class="absolute inset-0 -z-10 hidden dark:block">
      <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${darkImage})` }"></div>
      <div class="absolute inset-0 bg-black/50"></div>
    </div>

    <!-- Light mode background -->
    <div class="absolute inset-0 -z-10 dark:hidden">
      <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${lightImage})` }"></div>
      <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <!-- Decorative Gradient -->
    <div
      class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
      aria-hidden="true"
    >
      <div
        class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.0625rem] 
        -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr 
        from-primary-800 to-slate-700 opacity-25 
        sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
      />
    </div>

    <!-- Content -->
    <div class="mx-auto max-w-7xl px-6 lg:px-8 w-full">
      <div class="mx-auto max-w-2xl text-center">
        <p class="text-md font-extrabold text-primary-700 dark:text-primary-400 mb-4">
          Colorado-Based • SAM Registered • CAGE & DUNS Verified
        </p>

        <h1 class="text-5xl font-bold tracking-tight text-gray-900 sm:text-7xl dark:text-white">
          Reliable Government Supply Chain Solutions
        </h1>

        <p class="mt-10 text-lg leading-8 sm:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
          With over 10 years of experience in industrial supply chain management, we deliver
          dependable products and services to federal, state, and local agencies. Our mission is
          simple: reliable supply, competitive pricing, and on-time delivery every time.
        </p>

        <div class="mt-10 flex items-center justify-center gap-x-6">
          <button
            @click="openModal"
            class="rounded-md bg-primary-700 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 transition-colors"
          >
            View Capabilities Statement
          </button>
          <a href="#contact" class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            Contact Us →
          </a>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="isModalOpen"
          class="fixed inset-0 z-50 overflow-y-auto"
          @click.self="closeModal"
        >
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>

          <!-- Modal Content -->
          <div class="flex min-h-full items-center justify-center p-4">
            <Transition
              enter-active-class="transition-all duration-300 ease-out"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition-all duration-200 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-if="isModalOpen"
                class="relative w-full max-w-6xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl overflow-hidden"
                @click.stop
              >
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                  <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Capabilities Statement
                  </h2>
                  <button
                    @click="closeModal"
                    class="rounded-lg p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-200 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                    aria-label="Close modal"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>

                <!-- PDF Viewer -->
                <div class="relative bg-gray-100 dark:bg-gray-800">
                  <div v-if="pdfLoaded" class="w-full h-[80vh]">
                    <iframe
                      src="/docs/Colorado_Supply_Capabilities_Statement.pdf"
                      class="w-full h-full border-0"
                      title="Capabilities Statement PDF"
                    ></iframe>
                  </div>
                  <div v-else class="flex items-center justify-center h-[80vh]">
                    <div class="text-center">
                      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-700"></div>
                      <p class="mt-4 text-gray-600 dark:text-gray-400">Loading PDF...</p>
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                  <div class="flex items-center justify-between gap-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      Colorado Supply & Procurement LLC
                    </p>
                    <a
                      href="/docs/Colorado_Supply_Capabilities_Statement.pdf"
                      download
                      class="inline-flex items-center gap-2 rounded-md bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 transition-colors"
                    >
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                      </svg>
                      Download PDF
                    </a>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

