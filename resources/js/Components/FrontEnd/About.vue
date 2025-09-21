<template>
  <div id="about" class="relative bg-white dark:bg-gray-900 scroll-mt-20">
    <div class="mx-auto max-w-7xl lg:flex lg:justify-between lg:px-8 xl:justify-end">
      <!-- Image side -->
      <div class="lg:flex lg:w-1/2 lg:shrink lg:grow-0 xl:absolute xl:inset-y-0 xl:right-1/2 xl:w-1/2">
        <div class="relative h-80 lg:-ml-8 lg:h-auto lg:w-full lg:grow xl:ml-0">
          <img
            class="absolute inset-0 size-full bg-gray-50 object-cover dark:bg-gray-800"
            src="https://images.unsplash.com/photo-1559136555-9303baea8ebd?ixlib=rb-4.0.3&auto=format&fit=crop&w=2560&q=80"
            alt="Industrial supplies warehouse"
          />
        </div>
      </div>

      <!-- Content side -->
      <div class="px-6 lg:contents">
        <div
          class="mx-auto max-w-2xl pt-16 pb-24 sm:pt-20 sm:pb-32 lg:mr-0 lg:ml-8 lg:w-full lg:max-w-lg lg:flex-none lg:pt-32 xl:w-1/2"
        >
          <p class="text-base/7 font-semibold text-amber-600 dark:text-amber-400">Who We Are</p>
          <h1
            class="mt-2 text-4xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-5xl dark:text-white"
          >
            About Colorado Supply & Procurement LLC
          </h1>
          <p class="mt-6 text-xl/8 text-gray-700 dark:text-gray-300">
            Colorado-based with nationwide reach, we bring over a decade of industrial supply chain experience,
            delivering dependable goods and services tailored to the needs of federal, state, and local agencies.
          </p>

          <div class="mt-10 max-w-xl text-base/7 text-gray-600 lg:max-w-none dark:text-gray-400">
            <p>
              We specialize in sourcing and delivering MRO and OEM supplies across categories including fasteners,
              welding, plumbing, power transmission, fluid power, filtration, and cutting tools. Our broad vendor
              relationships allow us to provide a wide range of industrial goods and services quickly and reliably.
            </p>

            <ul role="list" class="mt-8 space-y-8 text-gray-600 dark:text-gray-400">
              <li class="flex gap-x-3">
                <CloudArrowUpIcon
                  class="mt-1 size-5 flex-none text-amber-600 dark:text-amber-400"
                  aria-hidden="true"
                />
                <span>
                  <strong class="font-semibold text-gray-900 dark:text-white">Nationwide Reach.</strong> Based in
                  Colorado, but trusted by agencies and contractors across the U.S.
                </span>
              </li>
              <li class="flex gap-x-3">
                <LockClosedIcon
                  class="mt-1 size-5 flex-none text-amber-600 dark:text-amber-400"
                  aria-hidden="true"
                />
                <span>
                  <strong class="font-semibold text-gray-900 dark:text-white">Trusted Experience.</strong> Over 10 years
                  in supply chain management with expertise across multiple industries.
                </span>
              </li>
              <li class="flex gap-x-3">
                <ServerIcon
                  class="mt-1 size-5 flex-none text-amber-600 dark:text-amber-400"
                  aria-hidden="true"
                />
                <span>
                  <strong class="font-semibold text-gray-900 dark:text-white">Wide Capabilities.</strong> From industrial
                  consumables to CNC machining and procurement support, we deliver complete solutions.
                </span>
              </li>
            </ul>

            <p class="mt-8">
              Founded after more than 10 years in the industrial supply chain industry, Colorado Supply & Procurement
              LLC was created with a simple mission: <span class="italic">to bridge the gap between large-scale supply
              capabilities and the personalized service of a local small business.</span> We understand the challenges
              agencies and contractors face when sourcing critical materials — and we pride ourselves on being the
              partner who delivers what you need, when you need it.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { CloudArrowUpIcon, LockClosedIcon, ServerIcon } from '@heroicons/vue/20/solid'
</script>
