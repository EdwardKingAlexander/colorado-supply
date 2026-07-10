<script setup>
import Navigation from '@/Components/Navigation.vue'

defineProps({
  appName: String,
})
</script>

<template>
  <div class="m-0 min-h-screen w-full min-w-0 bg-white p-0 dark:bg-gray-700">
    <!-- Navigation stays at the top -->
    <Navigation :appName="$page.props.appName" />

    <!-- Main content goes edge-to-edge -->
    <main class="m-0 w-full min-w-0 p-0">
      <slot />
    </main>
  </div>
</template>
