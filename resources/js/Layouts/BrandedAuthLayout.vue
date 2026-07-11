<script setup>
import CookieConsentBanner from '@/Components/Privacy/CookieConsentBanner.vue'
import { Link } from '@inertiajs/vue3'
import logo from '@images/logo-cleansed.svg'
import logoLight from '@images/logo-cleansed-light.svg'
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
    <header class="safe-top absolute inset-x-0 top-0 z-20">
      <!-- lg:pr matches the 520px form column so the links stay over the dark hero -->
      <nav class="flex h-16 items-center justify-between gap-2 px-4 sm:px-6 lg:px-8 lg:pr-[552px]" aria-label="Auth">
        <Link href="/" class="flex min-h-12 min-w-12 items-center rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
          <span class="sr-only">Colorado Supply & Procurement home</span>
          <!-- Below lg the dark hero column is hidden and this header sits on the white form panel -->
          <img class="h-10 w-auto sm:h-12 lg:hidden" :src="logo" alt="" aria-hidden="true" width="193" height="64" />
          <img class="hidden h-12 w-auto sm:h-14 lg:block lg:h-16" :src="logoLight" alt="" aria-hidden="true" width="193" height="64" />
        </Link>

        <div class="flex min-w-0 items-center gap-1 sm:gap-2">
          <Link href="/" class="hidden min-h-12 items-center rounded-md px-3 text-base font-semibold text-gray-700 hover:bg-gray-100 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 sm:inline-flex lg:text-gray-200 lg:hover:bg-white/10 lg:hover:text-amber-300">
            Home
          </Link>
          <Link
            v-if="secondaryActionHref && secondaryActionLabel"
            :href="secondaryActionHref"
            class="inline-flex min-h-12 items-center rounded-md px-3 text-base font-semibold text-amber-700 hover:bg-amber-50 hover:text-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 lg:text-amber-300 lg:hover:bg-white/10 lg:hover:text-amber-200"
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

      <section class="safe-bottom flex min-h-screen items-center bg-white px-4 pb-10 pt-24 text-gray-900 sm:px-6 sm:pt-28 lg:px-12">
        <div class="mx-auto w-full max-w-md">
          <div class="mb-8 lg:hidden">
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-amber-700">
              Government-focused procurement
            </p>
            <p class="mt-3 text-base leading-6 text-gray-600">
              Secure account access for quotes, orders, and supply chain support.
            </p>
          </div>

          <div class="border-l-4 border-amber-500 pl-5">
            <p class="text-sm font-semibold text-amber-700">
              {{ eyebrow }}
            </p>
            <h2 class="mt-2 text-3xl font-bold leading-10 text-gray-950">
              {{ title }}
            </h2>
            <p v-if="subtitle" class="mt-3 text-base leading-6 text-gray-600">
              {{ subtitle }}
            </p>
          </div>

          <div class="mt-8">
            <slot />
          </div>
        </div>
      </section>
    </main>

    <CookieConsentBanner />
  </div>
</template>
