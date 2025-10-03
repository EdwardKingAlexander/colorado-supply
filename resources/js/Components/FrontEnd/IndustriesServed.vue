<template>
  <section id="industries" class="relative isolate overflow-hidden bg-white py-24 sm:py-32 dark:bg-gray-900">
    <!-- Background decoration -->
    <div
      class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
      aria-hidden="true"
    >
      <div
        class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[72.1875rem] -translate-x-1/2 rotate-[30deg]
        bg-gradient-to-tr from-amber-400 to-indigo-600 opacity-20 sm:left-[calc(50%-30rem)] sm:w-[90rem]"
      />
    </div>

    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <!-- Section header -->
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Industries We Serve</h2>
        <p class="mt-2 text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
          Broad Experience, Proven Reliability
        </p>
        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
          Our expertise spans multiple sectors, ensuring dependable procurement and supply solutions for diverse
          government and commercial needs.
        </p>
      </div>

      <!-- Cards -->
      <div class="mx-auto mt-16 grid max-w-2xl grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 lg:max-w-none">
        <div
          v-for="industry in industries"
          :key="industry.name"
          class="overflow-hidden rounded-xl shadow bg-white dark:bg-gray-800"
        >
          <img :src="industry.image" alt="" class="h-48 w-full object-cover" />
          <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ industry.name }}</h3>
            <p class="mt-3 text-gray-600 dark:text-gray-400">{{ industry.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>

import aerospaceImageUrl from '@images/industries/aerospace.jpg';
import constructionImageUrl from '@images/industries/construction-site.jpg';
import foodProcessingImageUrl from '@images/industries/food-processing.jpg';
import manufacturingImageUrl from '@images/industries/manufacturing.jpg';
import automotiveImageUrl from '@images/industries/automotive.jpg';
import energyImageUrl from '@images/industries/energy.jpg';

const industries = [
  {
    name: 'Aerospace & Defense',
    description: 'Supplying precision components, fasteners, and specialized materials for aerospace and defense projects.',
    image: aerospaceImageUrl,
  },
  {
    name: 'Construction & Infrastructure',
    description: 'Providing tools, safety gear, and materials for government and commercial construction projects.',
    image: constructionImageUrl,
  },
  {
    name: 'Food Processing',
    description: 'Specialized equipment, MRO supplies, and safety products tailored for food production environments.',
    image: foodProcessingImageUrl,
  },
  {
    name: 'Manufacturing & Industrial',
    description: 'Wide range of OEM and MRO solutions for diverse industrial manufacturing needs.',
    image: manufacturingImageUrl,
  },
  {
    name: 'Automotive & Repair',
    description: 'Fasteners, tools, and repair shop essentials for automotive and fleet maintenance operations.',
    image: automotiveImageUrl,
  },
  {
    name: 'Energy & Utilities',
    description: 'Supplying fluid power, welding, and transmission solutions for energy and utility sectors.',
    image: energyImageUrl,
  },
]
</script>
