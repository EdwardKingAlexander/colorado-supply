<script setup>
import logo from '@images/logo-cleansed.svg'
import logoLight from '@images/logo-cleansed-light.svg'

// `auto` swaps with the dark class; pin `light`/`dark` when the surface
// behind the logo ignores the theme (e.g. a light card on a dark page).
defineProps({
  surface: {
    type: String,
    default: 'auto',
    validator: (value) => ['auto', 'light', 'dark'].includes(value),
  },
})
</script>

<template>
  <span class="inline-flex shrink-0 items-center">
    <img v-if="surface !== 'dark'" :src="logo" alt="Colorado Supply & Procurement" class="h-full w-auto" :class="{ 'dark:hidden': surface === 'auto' }" />
    <img v-if="surface !== 'light'" :src="logoLight" alt="Colorado Supply & Procurement" class="h-full w-auto" :class="{ 'hidden dark:block': surface === 'auto' }" />
  </span>
</template>
