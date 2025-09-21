<script setup>
import Navigation from '@/Components/Navigation.vue'
</script>

<template>
  <div>
    <Navigation />
    <main class="p-6">
      <slot />
    </main>
  </div>
</template>
