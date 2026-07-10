<script setup>
import { Link } from '@inertiajs/vue3'
import logo from '@images/logo-cleansed.svg'
import pipelineImageUrl from '@images/optimized/pipeline-1600.webp'
import pipelineMobileImageUrl from '@images/optimized/pipeline-900.webp'

defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  eyebrow: {
    type: String,
    default: 'Colorado Supply & Procurement',
  },
  secondaryActionLabel: {
    type: String,
    default: '',
  },
  secondaryActionHref: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div class="min-h-screen bg-gray-950 text-white">
    <header class="absolute inset-x-0 top-0 z-20">
      <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8" aria-label="Auth">
        <Link href="/" class="flex items-center gap-3">
          <span class="sr-only">Colorado Supply & Procurement home</span>
          <img class="h-12 w-auto bg-white/95 p-1 shadow-sm" :src="logo" alt="" aria-hidden="true" width="145" height="48" />
          <span class="hidden text-sm font-semibold text-white sm:inline">
            Colorado Supply & Procurement
          </span>
        </Link>

        <div class="flex items-center gap-4">
          <Link href="/" class="text-sm font-semibold text-gray-200 hover:text-amber-300">
            Home
          </Link>
          <Link
            v-if="secondaryActionHref && secondaryActionLabel"
            :href="secondaryActionHref"
            class="text-sm font-semibold text-amber-300 hover:text-amber-200"
          >
            {{ secondaryActionLabel }}
          </Link>
        </div>
      </nav>
    </header>

    <main class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(420px,520px)]">
      <section class="relative hidden overflow-hidden lg:block" aria-label="Colorado Supply account access">
        <img
          :src="pipelineImageUrl"
          :srcset="`${pipelineMobileImageUrl} 900w, ${pipelineImageUrl} 1600w`"
          sizes="60vw"
          alt=""
          width="1600"
          height="1067"
          class="absolute inset-0 h-full w-full object-cover"
        />
        <div class="absolute inset-0 bg-gray-950/65"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-gray-950/50 via-transparent to-amber-950/35"></div>

        <div class="relative z-10 flex min-h-screen items-end px-10 pb-14 pt-28 xl:px-16">
          <div class="max-w-2xl">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-amber-300">
              Government-focused procurement
            </p>
            <h1 class="mt-5 text-4xl font-bold text-white">
              Account access for quotes, orders, and supply chain support.
            </h1>
            <p class="mt-5 max-w-xl text-base leading-7 text-gray-200">
              Manage purchasing workflows with a Colorado-based industrial supply partner built for public-sector and B2B procurement.
            </p>
          </div>
        </div>
      </section>

      <section class="flex min-h-screen items-center bg-white px-5 pb-10 pt-28 text-gray-900 sm:px-6 lg:px-12">
        <div class="mx-auto w-full max-w-md">
          <div class="mb-8 lg:hidden">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-amber-700">
              Government-focused procurement
            </p>
            <p class="mt-3 text-sm leading-6 text-gray-600">
              Secure account access for quotes, orders, and supply chain support.
            </p>
          </div>

          <div class="border-l-4 border-amber-500 pl-5">
            <p class="text-sm font-semibold text-amber-700">
              {{ eyebrow }}
            </p>
            <h2 class="mt-2 text-2xl font-bold text-gray-950">
              {{ title }}
            </h2>
            <p v-if="subtitle" class="mt-3 text-sm leading-6 text-gray-600">
              {{ subtitle }}
            </p>
          </div>

          <div class="mt-8">
            <slot />
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
