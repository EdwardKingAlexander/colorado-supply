<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
  contactEmail: {
    type: String,
    default: 'Edward@cogovsupply.com',
  },
})
</script>

<template>
  <AppLayout :appName="$page.props.appName">
    <Head>
      <title>Store Temporarily Unavailable | Colorado Supply &amp; Procurement</title>
    </Head>

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 pb-16 pt-28 dark:bg-gray-900 sm:px-6">
      <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-lg ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-white/10">
        <!-- Branded header band -->
        <div class="flex flex-col items-center px-6 py-8 text-center" style="background-color: #16334c;">
          <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full" style="background-color: rgba(233, 194, 81, 0.15);">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="#e9c251" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 9l1.5-5h15L21 9" />
              <path d="M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9z" />
              <path d="M9 20v-5h6v5" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-white">
            Our store is temporarily unavailable
          </h1>
          <p class="mt-1 text-sm font-medium" style="color: #e9c251;">
            We&rsquo;ll be back online soon
          </p>
        </div>

        <!-- Body -->
        <div class="px-6 py-8 text-center sm:px-8">
          <p class="text-base leading-7 text-gray-600 dark:text-gray-300">
            We&rsquo;re currently performing updates to serve you better and expect
            to be back shortly. Thank you for your patience &mdash; your account and
            order history are safe and will be waiting for you when we return.
          </p>

          <div class="my-7 border-t border-gray-200 dark:border-white/10" />

          <p class="text-base leading-7 text-gray-700 dark:text-gray-200">
            Have an order to place or an urgent request in the meantime? Our team
            is happy to help you directly.
          </p>

          <a
            :href="`mailto:${contactEmail}`"
            class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-md px-6 py-3 text-base font-semibold text-white transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto"
            style="background-color: #16334c;"
          >
            Email {{ contactEmail }}
          </a>

          <div class="mt-6">
            <Link
              :href="route('dashboard')"
              class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
              &larr; Return to your dashboard
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
