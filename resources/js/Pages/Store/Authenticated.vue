<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
  products: Array,
})
</script>

<template>
  <AppLayout :appName="$page.props.appName">
    <Head>
      <title>Store | Colorado Supply & Procurement</title>
      <meta name="description" content="Browse our catalog of industrial supplies and equipment with exclusive pricing.">
    </Head>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-800">
      <!-- Header Section -->
      <div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl font-bold mb-2">Welcome Back!</h1>
          <p class="text-xl text-blue-100">
            Browse our personalized product recommendations
          </p>
        </div>
      </div>

      <!-- Products Section -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Featured Products for You
          </h2>
          <p class="text-gray-600 dark:text-gray-300">
            Based on your previous orders and preferences
          </p>
        </div>

        <!-- Products Grid -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div
            v-for="product in products"
            :key="product.id"
            class="bg-white dark:bg-gray-700 rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300"
          >
            <!-- Product Image -->
            <div class="aspect-w-16 aspect-h-9 bg-gray-200 dark:bg-gray-600">
              <img
                :src="product.image"
                :alt="product.name"
                class="w-full h-48 object-cover"
              />
            </div>

            <!-- Product Details -->
            <div class="p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {{ product.name }}
              </h3>
              <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                {{ product.description }}
              </p>

              <!-- Price and Action -->
              <div class="flex items-center justify-between">
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  ${{ product.price.toFixed(2) }}
                </span>
                <button
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors duration-200 text-sm font-medium"
                >
                  Add to Cart
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State (if no products) -->
        <div
          v-if="!products || products.length === 0"
          class="text-center py-12"
        >
          <svg
            class="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
            />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
            No products available
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Check back soon for new products.
          </p>
        </div>

        <!-- Additional Info Banner -->
        <div class="mt-12 bg-blue-50 dark:bg-gray-700 rounded-lg p-6">
          <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                Need something specific?
              </h3>
              <p class="text-gray-600 dark:text-gray-300">
                Contact us for custom quotes and bulk ordering options.
              </p>
            </div>
            <a
              href="/contact"
              class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition whitespace-nowrap"
            >
              Contact Us
            </a>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
