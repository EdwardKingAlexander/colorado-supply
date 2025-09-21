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
const industries = [
  {
    name: 'Aerospace & Defense',
    description: 'Supplying precision components, fasteners, and specialized materials for aerospace and defense projects.',
    image: 'https://images.unsplash.com/photo-1517976487492-5750f3195933?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  },
  {
    name: 'Construction & Infrastructure',
    description: 'Providing tools, safety gear, and materials for government and commercial construction projects.',
    image: 'https://images.unsplash.com/photo-1581092335525-7e3ef22c85f3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  },
  {
    name: 'Food Processing',
    description: 'Specialized equipment, MRO supplies, and safety products tailored for food production environments.',
    image: 'https://images.unsplash.com/photo-1584270354949-1e35b71e78d9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  },
  {
    name: 'Manufacturing & Industrial',
    description: 'Wide range of OEM and MRO solutions for diverse industrial manufacturing needs.',
    image: 'https://images.unsplash.com/photo-1581091870622-3b5c9c8c8c4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  },
  {
    name: 'Automotive & Repair',
    description: 'Fasteners, tools, and repair shop essentials for automotive and fleet maintenance operations.',
    image: 'https://images.unsplash.com/photo-1615484477634-7a9b1d4d68f5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  },
  {
    name: 'Energy & Utilities',
    description: 'Supplying fluid power, welding, and transmission solutions for energy and utility sectors.',
    image: 'https://images.unsplash.com/photo-1509395176047-4a66953fd231?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
  },
]
</script>
