<script setup>
import { ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'
import pipelineImageUrl from '@images/optimized/pipeline-1600.webp'
import pipelineMobileImageUrl from '@images/optimized/pipeline-900.webp'

const props = defineProps({
  lightImage: {
    type: String,
    default: pipelineImageUrl,
  },
  lightImageSrcset: {
    type: String,
    default: `${pipelineMobileImageUrl} 900w, ${pipelineImageUrl} 1600w`,
  },
  darkImage: {
    type: String,
    default: pipelineImageUrl,
  },
  darkImageSrcset: {
    type: String,
    default: `${pipelineMobileImageUrl} 900w, ${pipelineImageUrl} 1600w`,
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
  <div id="home" class="relative isolate flex min-h-[88svh] items-center overflow-hidden pb-12 pt-24 sm:min-h-[92svh] sm:pb-16">
    <!-- Dark mode background -->
    <div class="absolute inset-0 -z-10 hidden dark:block">
      <img
        :src="darkImage"
        :srcset="darkImageSrcset"
        sizes="100vw"
        alt=""
        width="1600"
        height="1067"
        fetchpriority="high"
        loading="eager"
        decoding="async"
        class="absolute inset-0 size-full object-cover object-center"
      />
      <div class="absolute inset-0 bg-black/50"></div>
    </div>

    <!-- Light mode background -->
    <div class="absolute inset-0 -z-10 dark:hidden">
      <img
        :src="lightImage"
        :srcset="lightImageSrcset"
        sizes="100vw"
        alt=""
        width="1600"
        height="1067"
        fetchpriority="high"
        loading="eager"
        decoding="async"
        class="absolute inset-0 size-full object-cover object-center"
      />
      <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <!-- Content -->
    <div class="mobile-page-gutter mx-auto w-full max-w-7xl lg:px-8">
      <div class="mx-auto max-w-2xl text-center">
        <p class="mb-4 text-base font-extrabold leading-6 text-primary-900 dark:text-primary-200">
          Colorado-Based • SAM Registered • CAGE & DUNS Verified
        </p>

        <h1 class="text-4xl font-bold leading-tight tracking-normal text-gray-950 sm:text-6xl lg:text-7xl dark:text-white">
          Reliable Government Supply Chain Solutions
        </h1>

        <p class="mx-auto mt-6 max-w-2xl text-base leading-7 text-gray-800 sm:mt-8 sm:text-xl sm:leading-8 dark:text-gray-200">
          With over 10 years of experience in industrial supply chain management, we deliver
          dependable products and services to federal, state, and local agencies. Our mission is
          simple: reliable supply, competitive pricing, and on-time delivery every time.
        </p>

        <div class="mx-auto mt-8 flex w-full max-w-md flex-col items-stretch justify-center gap-3 sm:mt-10 sm:max-w-none sm:flex-row sm:items-center sm:gap-4">
          <button
            @click="openModal"
            class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-primary-700 px-5 py-3 text-base font-semibold text-white shadow-md transition-colors hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:w-auto"
          >
            View Capabilities Statement
          </button>
          <a href="#contact" class="inline-flex min-h-12 w-full items-center justify-center rounded-md px-5 py-3 text-base font-semibold text-gray-950 hover:bg-white/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:w-auto dark:text-white dark:hover:bg-black/20">
            Contact Us →
          </a>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Modal :show="isModalOpen" max-width="2xl" @close="closeModal">
              <div class="relative overflow-hidden bg-white dark:bg-gray-900">
                <!-- Header -->
                <div class="flex min-h-16 items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                  <h2 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white sm:text-xl">
                    Capabilities Statement
                  </h2>
                  <button
                    @click="closeModal"
                    class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                    aria-label="Close modal"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>

                <!-- PDF Viewer -->
                <div class="relative bg-gray-100 dark:bg-gray-800">
                  <div v-if="pdfLoaded" class="h-[min(68svh,48rem)] w-full">
                    <iframe
                      src="/docs/Colorado_Supply_Capabilities_Statement.pdf"
                      class="w-full h-full border-0"
                      title="Capabilities Statement PDF"
                    ></iframe>
                  </div>
                  <div v-else class="flex h-[min(68svh,48rem)] items-center justify-center">
                    <div class="text-center">
                      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-700"></div>
                      <p class="mt-4 text-gray-600 dark:text-gray-400">Loading PDF...</p>
                    </div>
                  </div>
                </div>

                <!-- Footer -->
                <div class="safe-bottom border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm leading-5 text-gray-600 dark:text-gray-300">
                      Colorado Supply & Procurement LLC
                    </p>
                    <a
                      href="/docs/Colorado_Supply_Capabilities_Statement.pdf"
                      download
                      class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-md bg-primary-700 px-4 py-3 text-base font-semibold text-white shadow-sm transition-colors hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 sm:w-auto"
                    >
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                      </svg>
                      Download PDF
                    </a>
                  </div>
                </div>
              </div>
    </Modal>
  </div>
</template>
