<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    external: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <a
        v-if="external"
        :href="href"
        class="flex min-h-12 w-full items-center px-4 py-3 text-start text-base leading-6 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
    >
        <slot />
    </a>
    <Link
        v-else
        :href="href"
        class="flex min-h-12 w-full items-center px-4 py-3 text-start text-base leading-6 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
    >
        <slot />
    </Link>
</template>
