<script setup>
import AccountLayout from '@/Layouts/AccountLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    company: Object,
    locations: Array,
});
</script>

<template>
    <Head title="Company Home" />

    <AccountLayout
        :title="company?.name ?? 'Company'"
        :description="company ? 'Shop the catalog for a specific location.' : ''"
        narrow
    >
        <div v-if="!company" class="rounded-lg border border-dashed border-st-line bg-st-panel p-8 text-center">
            <h2 class="st-display text-xl font-bold text-st-ink">No company on this account</h2>
            <p class="mx-auto mt-2 max-w-xl text-base text-st-muted">
                Your account isn't linked to a company yet. Contact your Colorado Supply representative to get it set up.
            </p>
        </div>

        <section v-else class="st-panel" aria-labelledby="company-locations">
            <div class="flex items-baseline justify-between border-b border-st-line px-5 py-4">
                <h2 id="company-locations" class="text-base font-semibold text-st-ink">Locations</h2>
                <p class="st-num text-sm text-st-muted">{{ locations.length }}</p>
            </div>
            <ul class="divide-y divide-st-line">
                <li v-for="location in locations" :key="location.id">
                    <Link
                        :href="route('store.location.index', { location: location.slug })"
                        class="group flex min-h-[52px] items-center px-5 py-3 text-base font-semibold text-st-ink hover:bg-st-sunk focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-st-action"
                    >
                        <span class="truncate">{{ location.name }}</span>
                        <span class="st-leader" aria-hidden="true" />
                        <span class="shrink-0 text-sm text-st-action group-hover:underline">Shop this location</span>
                    </Link>
                </li>
                <li v-if="locations.length === 0" class="px-5 py-8 text-center text-sm text-st-muted">
                    No locations have been added for this company yet.
                </li>
            </ul>
        </section>
    </AccountLayout>
</template>
