<script setup>
const steps = [
  {
    number: '1',
    title: 'Submit Your Request',
    description: 'Tell us about your equipment and the issue you\'re experiencing using the form below.',
  },
  {
    number: '2',
    title: 'Free Evaluation & Quote',
    description: 'We review your submission and provide a no-obligation quote, typically within 24 hours.',
  },
  {
    number: '3',
    title: 'Ship It to Us, Free',
    description: 'Once you approve, we arrange free shipping to our repair facility — no cost to you.',
  },
  {
    number: '4',
    title: '3-5 Day Repair — or a Sourced Alternative',
    description: 'Once your unit arrives, most repairs are completed in 3-5 business days. If it can\'t be repaired, we\'ll quote a suitable replacement instead.',
  },
  {
    number: '5',
    title: 'Fast, No-Cost Return',
    description: 'Your equipment ships back at no charge, backed by a 2-year in-service warranty.',
  },
]
</script>

<template>
  <section id="repair-process" class="bg-white dark:bg-gray-900 py-24 sm:py-32 scroll-mt-20">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <!-- Section Header -->
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">How It Works</h2>
        <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
          Simple, Transparent Process
        </p>
      </div>

      <!-- Steps -->
      <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-12 sm:grid-cols-2 lg:max-w-none lg:grid-cols-5">
        <div v-for="step in steps" :key="step.number" class="flex flex-col items-start">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-700 text-lg font-bold text-white">
            {{ step.number }}
          </div>
          <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ step.title }}</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">{{ step.description }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
