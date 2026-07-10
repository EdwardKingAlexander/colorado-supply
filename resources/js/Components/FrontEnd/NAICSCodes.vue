<script setup>
import { ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const isOpen = defineModel({ type: Boolean, default: false })

const naicsCodes = [
  // Primary
  {
    code: '423840',
    description: 'Industrial Supplies Merchant Wholesalers',
    isPrimary: true,
    details: 'Wholesale distribution of industrial supplies including fasteners, welding equipment, plumbing supplies, and MRO products.'
  },

  // Supporting Codes
  {
    code: '423830',
    description: 'Industrial Machinery and Equipment Merchant Wholesalers',
    isPrimary: false,
    details: 'Distribution of industrial machinery, equipment, and related parts and supplies.'
  },
  {
    code: '423710',
    description: 'Hardware Merchant Wholesalers',
    isPrimary: false,
    details: 'Wholesale of hardware products, hand tools, and related items.'
  },
  {
    code: '423720',
    description: 'Plumbing and Heating Equipment and Supplies (Hydronics) Merchant Wholesalers',
    isPrimary: false,
    details: 'Distribution of plumbing, heating equipment, valves, pipes, fittings, and related supplies.'
  },
  {
    code: '332710',
    description: 'Machine Shops',
    isPrimary: false,
    details: 'CNC machining, custom manufacturing, fabrication, and precision manufacturing services through contractor network.'
  },
  {
    code: '423860',
    description: 'Transportation Equipment and Supplies (except Motor Vehicle) Merchant Wholesalers',
    isPrimary: false,
    details: 'Distribution of transportation-related equipment and supplies.'
  },
  {
    code: '423990',
    description: 'Other Miscellaneous Durable Goods Merchant Wholesalers',
    isPrimary: false,
    details: 'Wholesale of miscellaneous durable goods not elsewhere classified.'
  },
  {
    code: '811310',
    description: 'Commercial and Industrial Machinery and Equipment (except Automotive and Electronic) Repair and Maintenance',
    isPrimary: false,
    details: 'Industrial equipment repair and maintenance services through skilled contractor network.'
  },
  {
    code: '423450',
    description: 'Medical, Dental, and Hospital Equipment and Supplies Merchant Wholesalers',
    isPrimary: false,
    details: 'Distribution of medical and hospital equipment and supplies.'
  },
  {
    code: '423730',
    description: 'Warm Air Heating and Air-Conditioning Equipment and Supplies Merchant Wholesalers',
    isPrimary: false,
    details: 'Wholesale of HVAC equipment and related supplies.'
  },
  {
    code: '423810',
    description: 'Construction and Mining (except Oil Well) Machinery and Equipment Merchant Wholesalers',
    isPrimary: false,
    details: 'Distribution of construction and mining machinery and equipment.'
  },
  {
    code: '541330',
    description: 'Engineering Services',
    isPrimary: false,
    details: 'Engineering consultation and support services through professional network.'
  },
  {
    code: '541614',
    description: 'Process, Physical Distribution, and Logistics Consulting Services',
    isPrimary: false,
    details: 'Supply chain, logistics, and procurement consulting services.'
  },
  {
    code: '423820',
    description: 'Farm and Garden Machinery and Equipment Merchant Wholesalers',
    isPrimary: false,
    details: 'Distribution of agricultural and garden machinery and equipment.'
  },
]

const closeModal = () => {
  isOpen.value = false
}
</script>

<template>
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
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="closeModal"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>

        <!-- Modal Content -->
        <div class="safe-y flex min-h-full items-end justify-center p-4 sm:items-center">
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="isOpen"
              class="relative w-full max-w-5xl overflow-hidden rounded-lg bg-white shadow-2xl dark:bg-gray-900"
              @click.stop
            >
              <!-- Header -->
              <div class="sticky top-0 z-10 flex min-h-16 items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                <div>
                  <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    NAICS Codes
                  </h2>
                  <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    North American Industry Classification System codes we serve
                  </p>
                </div>
                <button
                  @click="closeModal"
                  class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-md text-gray-600 transition-colors hover:bg-gray-200 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                  aria-label="Close modal"
                >
                  <XMarkIcon class="h-6 w-6" />
                </button>
              </div>

              <!-- Content -->
              <div class="max-h-[68svh] overflow-y-auto p-4 sm:p-6">
                <div class="space-y-4">
                  <div
                    v-for="naics in naicsCodes"
                    :key="naics.code"
                    class="rounded-lg border p-4 transition-all sm:p-5"
                    :class="naics.isPrimary
                      ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-500 dark:border-amber-500'
                      : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-400'"
                  >
                    <div class="flex flex-col items-start gap-3 sm:flex-row sm:gap-4">
                      <div class="flex-shrink-0">
                        <span
                          class="inline-flex items-center justify-center px-3 py-1 text-sm font-semibold rounded-md"
                          :class="naics.isPrimary
                            ? 'text-amber-800 bg-amber-200 dark:text-amber-200 dark:bg-amber-800'
                            : 'text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30'"
                        >
                          {{ naics.code }}
                        </span>
                        <div v-if="naics.isPrimary" class="mt-2">
                          <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-amber-800 bg-amber-200 dark:text-amber-200 dark:bg-amber-800 rounded">
                            PRIMARY
                          </span>
                        </div>
                      </div>
                      <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                          {{ naics.description }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                          {{ naics.details }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="safe-bottom sticky bottom-0 border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Total: <span class="font-semibold text-gray-900 dark:text-white">{{ naicsCodes.length }}</span> NAICS Codes
                    <span class="ml-2 text-amber-600 dark:text-amber-400">(1 Primary, {{ naicsCodes.length - 1 }} Supporting)</span>
                  </p>
                  <button
                    @click="closeModal"
                    class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-gray-200 px-4 py-3 text-base font-semibold text-gray-900 transition-colors hover:bg-gray-300 sm:w-auto dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600"
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
