<template>
  <div id="credentials" class="relative isolate overflow-hidden bg-white py-24 sm:py-32 dark:bg-gray-900">
    <!-- Background images -->
    <img
      src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2830&q=80&blend=111827&sat=-100&exp=15&blend-mode=screen"
      alt=""
      class="absolute inset-0 -z-10 size-full object-cover object-right opacity-10 md:object-center dark:hidden"
    />
    <img
      src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2830&q=80&blend=111827&sat=-100&exp=15&blend-mode=multiply"
      alt=""
      class="absolute inset-0 -z-10 size-full object-cover object-right not-dark:hidden md:object-center"
    />

    <!-- Gradient decorations -->
    <div class="hidden sm:absolute sm:-top-10 sm:right-1/2 sm:-z-10 sm:mr-10 sm:block sm:transform-gpu sm:blur-3xl">
      <div
        class="aspect-1097/845 w-274.25 bg-linear-to-tr from-amber-400 to-indigo-600 opacity-15 dark:opacity-20"
        style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"
      />
    </div>
    <div class="absolute -top-52 left-1/2 -z-10 -translate-x-1/2 transform-gpu blur-3xl sm:-top-112 sm:ml-16 sm:translate-x-0">
      <div
        class="aspect-1097/845 w-274.25 bg-linear-to-tr from-amber-400 to-indigo-600 opacity-15 dark:opacity-20"
        style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"
      />
    </div>

    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <!-- Header -->
      <div class="mx-auto max-w-2xl lg:mx-0">
        <h2 class="text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl dark:text-white">
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
          class="flex flex-col gap-y-4 rounded-xl bg-white/30 p-6 ring-1 ring-gray-900/5 backdrop-blur-sm dark:bg-white/5 dark:inset-ring dark:inset-ring-white/5"
        >
          <div class="flex gap-x-4">
            <component :is="card.icon" class="h-7 w-7 flex-none text-amber-600 dark:text-amber-400" aria-hidden="true" />
            <div class="text-base/7">
              <h3 class="font-semibold text-gray-900 dark:text-white">{{ card.name }}</h3>
              <p class="mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ card.description }}</p>
            </div>
          </div>

          <!-- Optional link (only for PSC Codes card) -->
          <div v-if="card.link" class="mt-2">
            <a
              :href="card.link"
              class="text-sm font-semibold text-amber-600 hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
            >
              View full code list →
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ShieldCheckIcon,
  ClipboardDocumentCheckIcon,
  IdentificationIcon,
  BriefcaseIcon,
  DocumentTextIcon,
  TrophyIcon,
  TagIcon,
} from '@heroicons/vue/24/outline'

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
    link: '/compliance', // 🔗 Update this route once you build the compliance page
  },
]
</script>
