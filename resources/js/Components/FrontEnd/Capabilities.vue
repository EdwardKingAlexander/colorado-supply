<script setup>
import { WrenchScrewdriverIcon, TruckIcon, Cog6ToothIcon, CubeIcon, BuildingOffice2Icon, UsersIcon } from '@heroicons/vue/24/outline'
</script>

<template>
  <section id="capabilities" class="scroll-mt-20 bg-white py-16 sm:py-24 dark:bg-gray-900">
    <div class="mobile-page-gutter mx-auto max-w-7xl lg:px-8">
      <!-- Section Header -->
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-base font-semibold text-amber-600 dark:text-amber-400">Our Expertise</h2>
        <p class="mt-2 text-3xl font-bold leading-10 tracking-normal text-gray-900 sm:text-4xl dark:text-white">
          Capabilities
        </p>
        <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
          Colorado Supply & Procurement LLC delivers dependable products and services across industries,
          supporting government agencies and contractors nationwide.
        </p>
      </div>

      <!-- Grid -->
      <div class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-10 sm:grid-cols-2 lg:mt-16 lg:grid-cols-3 lg:gap-12">
        <!-- Industrial Supplies -->
        <div class="flex flex-col items-start">
          <WrenchScrewdriverIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Industrial Supplies</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Full range of MRO & OEM products, including fasteners, welding, plumbing, filtration, and cutting tools.
          </p>
        </div>

        <!-- Procurement & Sourcing -->
        <div class="flex flex-col items-start">
          <TruckIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Procurement & Sourcing</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Nationwide vendor network with competitive pricing and reliable sourcing strategies.
          </p>
        </div>

        <!-- Custom Manufacturing -->
        <div class="flex flex-col items-start">
          <Cog6ToothIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Custom Manufacturing</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            CNC machining, fabrication, and industrial electronic repair through a skilled contractor network.
          </p>
        </div>

        <!-- Inventory Solutions -->
        <div class="flex flex-col items-start">
          <CubeIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Inventory Solutions</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Stock management, consignment, and vendor-managed inventory to keep your operation running.
          </p>
        </div>

        <!-- Gov Contracting Support -->
        <div class="flex flex-col items-start">
          <BuildingOffice2Icon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Government Contracting</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Proven experience working with federal, state, and local agencies and their contractors.
          </p>
        </div>

        <!-- Partnership Services -->
        <div class="flex flex-col items-start">
          <UsersIcon class="h-10 w-10 text-amber-600 dark:text-amber-400" />
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">Partnership Services</h3>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Access to a trusted subcontractor network offering specialized skills across industries.
          </p>
        </div>
      </div>

      <!-- Call to Action -->
      <div class="mt-16 text-center sm:mt-20">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Ready to work with us?</h3>
        <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
          Download our Capabilities Statement or contact us today to discuss how we can support your next project.
        </p>
        <div class="mx-auto mt-8 flex max-w-md flex-col justify-center gap-3 sm:max-w-none sm:flex-row sm:items-center">
          <a
            href="/docs/Colorado_Supply_Capabilities_Statement.pdf"
            download
            class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-amber-700 px-5 py-3 text-base font-semibold text-white shadow hover:bg-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 sm:w-auto"
          >
            Download Capabilities Statement
          </a>
          <a
            href="#contact"
            class="inline-flex min-h-12 w-full items-center justify-center rounded-md px-5 py-3 text-base font-semibold text-gray-900 hover:bg-gray-100 hover:text-amber-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700 sm:w-auto dark:text-white dark:hover:bg-white/10 dark:hover:text-amber-300"
          >
            Contact Us →
          </a>
        </div>
      </div>
    </div>
  </section>
</template>
