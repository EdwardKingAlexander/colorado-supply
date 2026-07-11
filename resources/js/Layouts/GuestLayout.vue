<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import CookieConsentBanner from '@/Components/Privacy/CookieConsentBanner.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="safe-y flex min-h-screen flex-col items-center bg-gray-100 px-4 pb-8 pt-6 sm:justify-center sm:px-6 sm:pt-8"
    >
        <div>
            <Link href="/" class="flex min-h-12 min-w-12 items-center justify-center rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <ApplicationLogo class="h-16 w-16 fill-current text-gray-500 sm:h-20 sm:w-20" />
            </Link>
        </div>

        <div
            class="mt-6 w-full max-w-md overflow-hidden rounded-lg bg-white px-4 py-5 shadow-md sm:px-6"
        >
            <slot />
        </div>

        <CookieConsentBanner />
    </div>
</template>
