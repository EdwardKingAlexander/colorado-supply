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
              v-if="isOpen"
              class="relative w-full max-w-5xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl overflow-hidden"
              @click.stop
            >
              <!-- Header -->
              <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
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
                  class="rounded-lg p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-200 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                  aria-label="Close modal"
                >
                  <XMarkIcon class="h-6 w-6" />
                </button>
              </div>

              <!-- Content -->
              <div class="max-h-[70vh] overflow-y-auto p-6">
                <div class="space-y-4">
                  <div
                    v-for="naics in naicsCodes"
                    :key="naics.code"
                    class="p-5 rounded-lg border transition-all"
                    :class="naics.isPrimary
                      ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-500 dark:border-amber-500'
                      : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-400'"
                  >
                    <div class="flex items-start gap-4">
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
              <div class="sticky bottom-0 px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Total: <span class="font-semibold text-gray-900 dark:text-white">{{ naicsCodes.length }}</span> NAICS Codes
                    <span class="ml-2 text-amber-600 dark:text-amber-400">(1 Primary, {{ naicsCodes.length - 1 }} Supporting)</span>
                  </p>
                  <button
                    @click="closeModal"
                    class="rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 transition-colors"
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
