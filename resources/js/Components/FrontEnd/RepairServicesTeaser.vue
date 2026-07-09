<script setup>
import { WrenchScrewdriverIcon } from '@heroicons/vue/24/outline'
</script>

<template>
  <section id="repair-services-teaser" class="bg-gray-50 dark:bg-gray-800 py-24 sm:py-32 scroll-mt-20">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <div class="mx-auto max-w-3xl rounded-2xl bg-white dark:bg-gray-900 p-10 shadow-sm ring-1 ring-gray-900/5 dark:ring-white/10 sm:p-12">
        <div class="flex flex-col items-center text-center">
          <WrenchScrewdriverIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h2 class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
            Industrial Equipment Repair Services
          </h2>
          <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
            We also repair industrial electronics, servo motors, AC &amp; DC motors, and hydraulics &amp; pneumatics —
            for up to 50% less than buying new, backed by a 2-year warranty and a free, risk-free evaluation.
          </p>
          <a
            :href="route('repair-services.index')"
            class="mt-8 inline-flex items-center rounded-md bg-amber-700 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700"
          >
            Explore Repair Services →
          </a>
        </div>
      </div>
    </div>
  </section>
</template>
