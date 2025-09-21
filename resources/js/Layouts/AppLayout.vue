<script setup>
import Navigation from '@/Components/Navigation.vue'
</script>

<template>
  <div>
    <Navigation :appName="$page.props.appName" />
    <main class="p-6">
      <slot />
    </main>
  </div>
</template>
