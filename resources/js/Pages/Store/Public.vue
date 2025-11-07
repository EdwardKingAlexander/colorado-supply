<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <AppLayout :appName="$page.props.appName">
    <Head>
      <title>Store | Colorado Supply & Procurement</title>
      <meta name="description" content="Browse our catalog of industrial supplies and equipment for government agencies and commercial customers.">
    </Head>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-800">
      <!-- Hero Section -->
      <div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to Our Store</h1>
          <p class="text-xl md:text-2xl text-blue-100 mb-8">
            Quality industrial supplies for government agencies and commercial customers
          </p>
          <p class="text-lg text-blue-50">
            Sign in to access exclusive pricing and personalized product recommendations
          </p>
        </div>
      </div>

      <!-- Store Information Section -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid md:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md p-6">
            <div class="text-blue-600 dark:text-blue-400 mb-4">
              <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Quality Products</h3>
            <p class="text-gray-600 dark:text-gray-300">
              We offer only the highest quality industrial supplies and equipment from trusted manufacturers.
            </p>
          </div>

          <!-- Feature 2 -->
          <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md p-6">
            <div class="text-blue-600 dark:text-blue-400 mb-4">
              <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Competitive Pricing</h3>
            <p class="text-gray-600 dark:text-gray-300">
              Get access to government contract pricing and exclusive discounts when you create an account.
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md p-6">
            <div class="text-blue-600 dark:text-blue-400 mb-4">
              <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Fast Delivery</h3>
            <p class="text-gray-600 dark:text-gray-300">
              Quick turnaround times and reliable delivery to meet your project deadlines.
            </p>
          </div>
        </div>

        <!-- Call to Action -->
        <div class="mt-12 text-center">
          <div class="bg-blue-50 dark:bg-gray-700 rounded-lg p-8">
            <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">
              Ready to Get Started?
            </h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
              Create an account to browse our full catalog and access exclusive pricing
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
              <a
                href="/register"
                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition"
              >
                Create Account
              </a>
              <a
                href="/login"
                class="inline-flex items-center justify-center px-6 py-3 border border-blue-600 text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 dark:bg-gray-800 dark:text-blue-400 dark:hover:bg-gray-700 transition"
              >
                Sign In
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
