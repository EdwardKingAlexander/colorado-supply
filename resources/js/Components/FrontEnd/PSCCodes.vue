<script setup>
import { ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const isOpen = defineModel({ type: Boolean, default: false })

const pscCodes = [
  // Fasteners & Hardware
  { code: '5305', description: 'Screws' },
  { code: '5306', description: 'Bolts' },
  { code: '5307', description: 'Studs' },
  { code: '5310', description: 'Nuts and Washers' },
  { code: '5315', description: 'Nails, Machine Keys, and Pins' },
  { code: '5320', description: 'Rivets' },
  { code: '5325', description: 'Fastening Devices' },

  // Gaskets, Packing & Sealing
  { code: '5330', description: 'Packing and Gasket Materials' },
  { code: '5331', description: 'O-Rings' },
  { code: '5335', description: 'Metal Screening' },

  // Welding Equipment
  { code: '3431', description: 'Electric Arc Welding Equipment' },
  { code: '3432', description: 'Welding Positioners and Manipulators' },
  { code: '3433', description: 'Gas Welding, Heat Cutting, and Metalizing Equipment' },
  { code: '3439', description: 'Miscellaneous Welding Equipment' },

  // Plumbing & Piping
  { code: '4710', description: 'Pipe and Tube' },
  { code: '4720', description: 'Hose and Tubing, Flexible' },
  { code: '4730', description: 'Hose, Pipe, Tube, Lubrication, and Railing Fittings' },
  { code: '4820', description: 'Valves, Nonpowered' },
  { code: '4810', description: 'Valves, Powered' },

  // Tools & Machinery
  { code: '5130', description: 'Hand Tools, Power Driven' },
  { code: '5133', description: 'Drill Bits, Counterbores, and Countersinks' },
  { code: '5136', description: 'Taps, Dies, and Collets' },
  { code: '5140', description: 'Hand Tools, Edged, Nonpowered' },
  { code: '5180', description: 'Sets, Kits, and Outfits of Hand Tools' },
  { code: '3405', description: 'Sawing Machines' },
  { code: '3416', description: 'Drilling and Boring Machines' },
  { code: '3417', description: 'Grinding Machines' },
  { code: '3418', description: 'Cutting and Forming Machines' },

  // Abrasives & Cutting Tools
  { code: '5345', description: 'Disks and Stones, Abrasive' },
  { code: '5350', description: 'Abrasive Materials' },
  { code: '3460', description: 'Machine Tools, Portable' },

  // Bearings & Power Transmission
  { code: '3110', description: 'Bearings, Antifriction, Unmounted' },
  { code: '3120', description: 'Bearings, Plain, Unmounted' },
  { code: '3010', description: 'Torque Converters and Speed Changers' },
  { code: '3020', description: 'Gears, Pulleys, Sprockets, and Transmission Chain' },

  // Electrical & Electronics
  { code: '5961', description: 'Semiconductors and Hardware Devices' },
  { code: '5962', description: 'Electronic Microcircuits, Digital' },
  { code: '5975', description: 'Electrical Hardware and Supplies' },
  { code: '5977', description: 'Electrical Contact Brushes and Electrodes' },
  { code: '5995', description: 'Cable, Cord, and Wire Assemblies: Communication Equipment' },
  { code: '5998', description: 'Electrical and Electronic Assemblies, Boards, Cards, and Associated Hardware' },
  { code: '6150', description: 'Miscellaneous Electric Power and Distribution Equipment' },

  // Pumps & Compressors
  { code: '4320', description: 'Power and Hand Pumps' },
  { code: '4310', description: 'Compressors and Vacuum Pumps' },

  // Lubricants & Chemicals
  { code: '9150', description: 'Oils and Greases: Cutting, Lubricating, and Hydraulic' },
  { code: '6850', description: 'Miscellaneous Chemical Specialties' },
  { code: '8030', description: 'Preservative and Sealing Compounds' },

  // Filters & Strainers
  { code: '4330', description: 'Centrifugals, Separators, and Pressure and Vacuum Filters' },
  { code: '4730', description: 'Strainers and In-Line Filter Elements' },

  // Motors & Generators
  { code: '6105', description: 'Motors, Electrical' },
  { code: '6115', description: 'Generators and Generator Sets, Electrical' },
  { code: '6116', description: 'Fuel Cell Power Units, Components and Accessories' },

  // Safety Equipment
  { code: '4240', description: 'Safety and Rescue Equipment' },
  { code: '8415', description: 'Clothing, Special Purpose' },
  { code: '8465', description: 'Individual Safety and Protection Equipment' },

  // Measuring & Testing
  { code: '6625', description: 'Electrical and Electronic Properties Measuring and Testing Instruments' },
  { code: '6630', description: 'Chemical Analysis Instruments' },
  { code: '6635', description: 'Physical Properties Testing and Inspection Equipment' },
  { code: '5210', description: 'Measuring Tools, Craftsmen' },

  // Maintenance & Repair
  { code: '4940', description: 'Maintenance and Repair Shop Equipment' },
  { code: '3439', description: 'Miscellaneous Maintenance and Repair Shop Specialized Equipment' },

  // Materials & Raw Stock
  { code: '9505', description: 'Wire, Nonelectrical' },
  { code: '9515', description: 'Bars and Rods' },
  { code: '9520', description: 'Structural Shapes' },
  { code: '9525', description: 'Wire Cloth and Other Screen' },
  { code: '9530', description: 'Nonmetallic Fabricated Materials' },
  { code: '9535', description: 'Rubber Fabricated Materials' },
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
                    Product Service Codes (PSC)
                  </h2>
                  <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Complete list of PSC codes we serve
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div
                    v-for="psc in pscCodes"
                    :key="psc.code"
                    class="flex gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-amber-500 dark:hover:border-amber-500 transition-colors"
                  >
                    <div class="flex-shrink-0">
                      <span class="inline-flex items-center justify-center px-3 py-1 text-sm font-semibold text-amber-700 bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 rounded-md">
                        {{ psc.code }}
                      </span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ psc.description }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="sticky bottom-0 px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Total: <span class="font-semibold text-gray-900 dark:text-white">{{ pscCodes.length }}</span> PSC Codes
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
