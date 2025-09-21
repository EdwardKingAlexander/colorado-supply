<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  lightImage: {
    type: String,
    default:
      'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2830&q=80&blend=fff&sat=-100&exp=15&blend-mode=overlay',
  },
  darkImage: {
    type: String,
    default:
      'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2830&q=80&blend=111827&sat=-100&exp=15&blend-mode=multiply',
  },
})
</script>

<template>
  <div class="relative isolate overflow-hidden pt-14">
    <!-- Background image (dark) -->
    <div
      class="absolute inset-0 -z-10 bg-cover bg-center not-dark:hidden"
      :style="{ backgroundImage: `url(${darkImage})` }"
    ></div>

    <!-- Background image (light) -->
    <div
      class="absolute inset-0 -z-10 bg-cover bg-center opacity-10 dark:hidden"
      :style="{ backgroundImage: `url(${lightImage})` }"
    ></div>

    <!-- Gradient decoration -->
    <div
      class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
      aria-hidden="true"
    >
      <div
        class="relative left-[calc(50%-11rem)] aspect-1155/678 w-144.5 
        -translate-x-1/2 rotate-30 bg-gradient-to-tr 
        from-primary-800 to-slate-700 opacity-25 
        sm:left-[calc(50%-30rem)] sm:w-288.75"
      />
    </div>

    <!-- Content -->
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
      <div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">
        <div class="text-center">
          <!-- Trust Signal -->
          <p class="text-sm font-medium text-primary-700 dark:text-primary-400 mb-4">
            Colorado-Based • SAM Registered • CAGE & DUNS Verified
          </p>

          <!-- Headline -->
          <h1 class="text-5xl font-bold tracking-tight text-gray-900 sm:text-7xl dark:text-white">
            Trusted Supply Solutions for Government and Industry
          </h1>

          <!-- Subheadline -->
          <p class="mt-6 text-lg sm:text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
            With over 10 years of experience in industrial supply chain management, we deliver
            dependable products and services to federal, state, and local agencies. Our mission is
            simple: reliable supply, competitive pricing, and on-time delivery every time.
          </p>

          <!-- CTAs -->
          <div class="mt-10 flex items-center justify-center gap-x-6">
            <a
              href="/capabilities"
              class="rounded-md bg-primary-700 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"
            >
              View Capabilities Statement
            </a>
            <a href="/contact" class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              Contact Us →
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
