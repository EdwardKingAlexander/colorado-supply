<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message" aria-live="polite">
        <p class="text-sm leading-5 text-red-700">
            {{ message }}
        </p>
    </div>
</template>
