<script setup>
import Navigation from '@/Components/Navigation.vue'

defineProps({
  appName: String,
})
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar -->
    <Navigation />

    <!-- Hero / Welcome Content -->
    <div class="flex flex-col items-center justify-center h-[80vh] text-center">
      <h1 class="text-5xl font-bold text-gray-900 mb-6">
        Welcome to hell {{ appName }}
      </h1>
      <p class="text-lg text-gray-600 mb-8 max-w-xl">
        Your modern Laravel + Inertia + Vue + Filament application is ready.
      </p>
      <div class="space-x-4">
        <a
          href="/products"
          class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Explore Products
        </a>
        <a
          href="/services"
          class="px-6 py-3 bg-gray-800 text-white rounded-lg hover:bg-gray-900"
        >
          Our Services
        </a>
      </div>
    </div>
  </div>
</template>
