<template>
  <div id="credentials" class="relative isolate overflow-hidden bg-white py-16 sm:py-24 dark:bg-gray-900">
    <!-- Background images -->
    <img
      :src="complianceImageUrl"
      alt=""
      width="1400"
      height="1050"
      loading="lazy"
      decoding="async"
      class="absolute inset-0 -z-10 size-full object-cover object-right opacity-10 md:object-center dark:hidden"
    />
    <img
      :src="complianceImageUrl"
      alt=""
      width="1400"
      height="1050"
      loading="lazy"
      decoding="async"
      class="absolute inset-0 -z-10 size-full object-cover object-right opacity-45 not-dark:hidden md:object-center"
    />
    <div class="absolute inset-0 -z-10 hidden bg-gray-950/65 dark:block"></div>

    <div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8">
      <!-- Header -->
      <div class="mx-auto max-w-2xl lg:mx-0">
        <h2 class="text-3xl font-semibold leading-10 tracking-normal text-gray-900 sm:text-5xl dark:text-white">
          Credentials & Compliance
        </h2>
        <p class="mt-8 text-lg font-medium text-pretty text-gray-600 sm:text-xl/8 dark:text-gray-400">
          Fully registered and compliant to work with federal, state, and local government agencies nationwide.
        </p>
      </div>

      <!-- Cards grid -->
      <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-6 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3 lg:gap-8">
        <div
          v-for="card in cards"
          :key="card.name"
          class="flex flex-col gap-y-4 rounded-lg bg-white/90 p-5 shadow-sm ring-1 ring-gray-900/10 backdrop-blur-md sm:p-6 dark:bg-gray-950/85 dark:ring-white/15"
        >
          <div class="flex gap-x-4">
            <component :is="card.icon" class="h-7 w-7 flex-none text-amber-600 dark:text-amber-400" aria-hidden="true" />
            <div class="text-base/7">
              <h3 class="font-semibold text-gray-900 dark:text-white">{{ card.name }}</h3>
              <p class="mt-2 text-gray-700 dark:text-gray-200 whitespace-pre-line">{{ card.description }}</p>
            </div>
          </div>

          <!-- Optional link for modal cards -->
          <div v-if="card.hasModal" class="mt-2">
            <button
              @click="openModal(card.modalType)"
              class="inline-flex min-h-12 items-center rounded-md px-3 py-2 text-base font-semibold text-amber-700 transition-colors hover:bg-amber-50 hover:text-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-600 dark:text-amber-300 dark:hover:bg-white/10 dark:hover:text-amber-200"
            >
              View all codes →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Components -->
    <NAICSCodes v-model="showNAICSModal" />
    <PSCCodes v-model="showPSCModal" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import {
  ShieldCheckIcon,
  ClipboardDocumentCheckIcon,
  IdentificationIcon,
  BriefcaseIcon,
  DocumentTextIcon,
  TrophyIcon,
  TagIcon,
} from '@heroicons/vue/24/outline'
import NAICSCodes from './NAICSCodes.vue'
import PSCCodes from './PSCCodes.vue'
import complianceImageUrl from '@images/optimized/compliance-bg-1400.webp'

const showNAICSModal = ref(false)
const showPSCModal = ref(false)

const cards = [
  {
    name: 'CAGE Code',
    description: '15NL2',
    icon: ShieldCheckIcon,
  },
  {
    name: 'SAM.gov Status',
    description: 'Active Registration',
    icon: ClipboardDocumentCheckIcon,
  },
  {
    name: 'EIN',
    description: '39-3537490',
    icon: IdentificationIcon,
  },
  {
    name: 'Business Type',
    description: 'Single-Member LLC | Small Business Concern',
    icon: BriefcaseIcon,
  },
  {
    name: 'NAICS (Primary & Supporting)',
    description:
      '423840 – Industrial Supplies Merchant Wholesalers (Primary)\n' +
      '423830 – Industrial Machinery & Equipment Merchant Wholesalers\n' +
      '423710 – Hardware Merchant Wholesalers\n' +
      '423720 – Plumbing & Heating Equipment & Supplies Wholesalers\n' +
      '332710 – Machine Shops (CNC)',
    icon: DocumentTextIcon,
    hasModal: true,
    modalType: 'naics',
  },
  {
    name: 'Experience',
    description: '14+ years in supply chain & procurement',
    icon: TrophyIcon,
  },
  {
    name: 'Top PSC Codes',
    description:
      '5305 – Screws • 5306 – Bolts • 5310 – Nuts & Washers • 5330 – Packing & Gaskets\n' +
      '3431 – Electric Arc Welding Equip • 3433 – Gas Welding/Heat Cutting Equip\n' +
      '4730 – Hose/Pipe/Tube/Fittings • 4820 – Valves, Nonpowered',
    icon: TagIcon,
    hasModal: true,
    modalType: 'psc',
  },
]

const openModal = (modalType) => {
  if (modalType === 'naics') {
    showNAICSModal.value = true
  } else if (modalType === 'psc') {
    showPSCModal.value = true
  }
}
</script>
