<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex min-h-12 items-center border-b-2 border-indigo-500 px-2 text-base font-medium leading-6 text-gray-900 transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500'
        : 'inline-flex min-h-12 items-center border-b-2 border-transparent px-2 text-base font-medium leading-6 text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
