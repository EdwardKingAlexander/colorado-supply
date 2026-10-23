<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-3 text-base font-semibold leading-6 tracking-normal text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 [.store-shell_&]:border-st-line [.store-shell_&]:bg-st-panel [.store-shell_&]:text-st-ink [.store-shell_&]:shadow-none [.store-shell_&]:hover:border-st-action [.store-shell_&]:hover:bg-st-sunk [.store-shell_&]:focus-visible:ring-offset-st-bg disabled:cursor-not-allowed disabled:opacity-50"
    >
        <slot />
    </button>
</template>
