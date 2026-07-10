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
    external: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() =>
    props.active
        ? 'flex min-h-[52px] w-full items-center border-l-4 border-indigo-500 bg-indigo-50 py-3 pe-4 ps-4 text-start text-base font-medium leading-6 text-indigo-700 transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500'
        : 'flex min-h-[52px] w-full items-center border-l-4 border-transparent py-3 pe-4 ps-4 text-start text-base font-medium leading-6 text-gray-700 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500',
);
</script>

<template>
    <a v-if="external" :href="href" :class="classes">
        <slot />
    </a>
    <Link v-else :href="href" :class="classes">
        <slot />
    </Link>
</template>
