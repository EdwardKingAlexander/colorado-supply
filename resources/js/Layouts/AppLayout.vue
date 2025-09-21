<script setup>
import Navigation from '@/Components/Navigation.vue'

defineProps({
  appName: String,
})
</script>

<template>
  <div class="min-h-screen w-full m-0 p-0 bg-white dark:bg-gray-700">
    <!-- Navigation stays at the top -->
    <Navigation :appName="$page.props.appName" />

    <!-- Main content goes edge-to-edge -->
    <main class="w-full m-0 p-0">
      <slot />
    </main>
  </div>
</template>
