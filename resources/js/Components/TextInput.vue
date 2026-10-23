<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="min-h-12 rounded-md border-gray-300 px-3 py-2 text-base leading-6 shadow-sm focus:border-primary-600 focus:ring-primary-600 [.store-shell_&]:shadow-none [.store-shell_&]:focus:border-st-action [.store-shell_&]:focus:ring-st-action/40"
        v-model="model"
        ref="input"
    />
</template>
