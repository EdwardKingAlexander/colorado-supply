<script setup>
import { CpuChipIcon, BoltIcon, Cog6ToothIcon, AdjustmentsHorizontalIcon } from '@heroicons/vue/24/outline'

const categories = [
  {
    icon: CpuChipIcon,
    name: 'Industrial Electronics',
    items: 'Circuit boards, drives, PLCs, HMIs & touchscreens, power supplies, process controls',
  },
  {
    icon: BoltIcon,
    name: 'Servo Motors',
    items: 'AC & DC servo motors, continuous rotational, linear, and positional rotation types',
  },
  {
    icon: Cog6ToothIcon,
    name: 'AC & DC Motors',
    items: '3-phase, brushless, gear, stepper, and spindle motors',
  },
  {
    icon: AdjustmentsHorizontalIcon,
    name: 'Hydraulics & Pneumatics',
    items: 'Actuators, cylinders, pumps, valves, and manifolds',
  },
]
</script>

<template>
  <section id="repair-categories" class="scroll-mt-20 bg-gray-50 py-16 sm:py-24 dark:bg-gray-800">
    <div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8">
      <!-- Section Header -->
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">What We Repair</h2>
        <p class="mt-2 text-3xl font-bold leading-10 tracking-normal text-gray-900 sm:text-4xl dark:text-white">
          Equipment Categories
        </p>
        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
          A broad range of industrial equipment, backed by the same repair program regardless of category.
        </p>
      </div>

      <!-- Grid -->
      <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 sm:grid-cols-2 lg:max-w-none">
        <div
          v-for="category in categories"
          :key="category.name"
          class="rounded-xl bg-white dark:bg-gray-900 p-8 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10"
        >
          <component :is="category.icon" class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">{{ category.name }}</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">{{ category.items }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
