<script setup>
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <nav class="bg-white shadow-md">
    <div class="container mx-auto flex justify-between items-center px-6 py-4">
      <!-- Logo -->
      <Link href="/" class="text-xl font-bold text-blue-600">
        {{ $page.props.appName }}
      </Link>

      <!-- Links -->
      <div class="space-x-6 flex items-center">
        <Link href="/products" class="text-gray-700 hover:text-blue-600">Products</Link>
        <Link href="/services" class="text-gray-700 hover:text-blue-600">Services</Link>
        <Link href="/about" class="text-gray-700 hover:text-blue-600">About</Link>
        <Link href="/contact" class="text-gray-700 hover:text-blue-600">Contact</Link>
      </div>

      <!-- Auth -->
      <div class="space-x-4">
        <Link
          v-if="!$page.props.auth?.user"
          href="/login"
          class="text-gray-700 hover:text-blue-600"
        >
          Log in
        </Link>
        <Link
          v-if="!$page.props.auth?.user"
          href="/register"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          Register
        </Link>
        <Link
          v-else
          href="/dashboard"
          class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900"
        >
          Dashboard
        </Link>
      </div>
    </div>
  </nav>
</template>
