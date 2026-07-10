<script setup>
import {
  CurrencyDollarIcon,
  ShieldCheckIcon,
  ClipboardDocumentCheckIcon,
  TruckIcon,
  ClockIcon,
  ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'
</script>

<template>
  <section id="repair-advantages" class="scroll-mt-20 bg-white py-16 sm:py-24 dark:bg-gray-900">
    <div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8">
      <!-- Section Header -->
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Why Repair With Us</h2>
        <p class="mt-2 text-3xl font-bold leading-10 tracking-normal text-gray-900 sm:text-4xl dark:text-white">
          The Colorado Supply Repair Advantage
        </p>
        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
          A straightforward, low-risk way to get critical equipment back in service.
        </p>
      </div>

      <!-- Grid -->
      <div class="mx-auto mt-16 max-w-4xl grid grid-cols-1 gap-12 sm:grid-cols-2 lg:grid-cols-3">
        <div class="flex flex-col items-start">
          <CurrencyDollarIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Save Up to 50%</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Repair costs a fraction of buying new, without sacrificing performance or reliability.
          </p>
        </div>

        <div class="flex flex-col items-start">
          <ShieldCheckIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">2-Year In-Service Warranty</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Every repair is backed by a full two-year warranty for peace of mind.
          </p>
        </div>

        <div class="flex flex-col items-start">
          <ClipboardDocumentCheckIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Free, Risk-Free Evaluation</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            We diagnose your equipment before any work begins. No surprises, no obligation.
          </p>
        </div>

        <div class="flex flex-col items-start">
          <TruckIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">No Freight or Upfront Cost</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            We cover shipping both ways and never bill you before you approve the work.
          </p>
        </div>

        <div class="flex flex-col items-start">
          <ClockIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">3-5 Day Repair Turnaround</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Once your equipment reaches our facility, most repairs are completed in 3-5 business days —
            shipping time to and from us is separate. Rush service is available when you can't wait.
          </p>
        </div>

        <div class="flex flex-col items-start">
          <ArrowsRightLeftIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">We Source Alternatives</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            If a unit is beyond repair, we'll find and quote a suitable replacement. No dead ends.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
