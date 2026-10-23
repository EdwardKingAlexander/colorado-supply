<script setup>
import AccountNav from '@/Components/Account/AccountNav.vue'
import StoreLayout from '@/Layouts/StoreLayout.vue'

// Signed-in account pages: the shared store header plus an account sub-nav
// (ai/modules/customer-portal-ui-rebrand-complete, phase 2).
defineProps({
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  // Reading-width pages (profile, notifications) keep content to a narrower column.
  narrow: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <StoreLayout>
    <div class="border-b border-st-line bg-st-panel">
      <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
        <AccountNav />
      </div>
    </div>

    <div class="mx-auto max-w-screen-2xl px-4 pb-12 pt-6 sm:px-6 sm:pt-8 lg:px-8">
      <div :class="narrow ? 'max-w-4xl' : ''">
        <header v-if="title" class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div class="min-w-0">
            <h1 class="st-display text-2xl font-extrabold leading-tight text-st-ink sm:text-3xl">{{ title }}</h1>
            <p v-if="description || $slots.description" class="mt-1.5 max-w-2xl text-base text-st-muted">
              <slot name="description">{{ description }}</slot>
            </p>
          </div>
          <div v-if="$slots.actions" class="grid grid-cols-1 gap-2 xs:grid-cols-2 sm:flex sm:shrink-0">
            <slot name="actions" />
          </div>
        </header>

        <slot />
      </div>
    </div>
  </StoreLayout>
</template>
