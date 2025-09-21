<script setup>
defineProps({ appName: String })
</script>

<template>
  <div class="flex items-center justify-center h-screen">
    <h1 class="text-3xl font-bold">Welcome to {{ appName }}</h1>
  </div>
</template>
