<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  account: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <section class="st-panel p-5">
    <div class="flex items-start justify-between gap-4">
      <div class="min-w-0">
        <h2 class="truncate text-lg font-semibold text-st-ink">{{ account.name }}</h2>
        <p class="truncate text-sm text-st-muted">{{ account.email }}</p>
      </div>
      <span
        class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-st-line px-2.5 py-1 text-xs font-semibold text-st-ink"
      >
        <span class="h-2 w-2 rounded-full" :class="account.profile_complete ? 'bg-st-ok' : 'bg-st-warn'" aria-hidden="true" />
        {{ account.profile_complete ? 'Profile complete' : 'Needs setup' }}
      </span>
    </div>

    <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-st-line pt-4 text-sm">
      <div>
        <dt class="text-st-muted">Company</dt>
        <dd class="mt-1 font-semibold text-st-ink">{{ account.company?.name ?? 'Not assigned' }}</dd>
      </div>
      <div>
        <dt class="text-st-muted">Locations</dt>
        <dd class="st-num mt-1 font-semibold text-st-ink">{{ account.locations_count }} ({{ account.sublocations_count }} sub)</dd>
      </div>
    </dl>

    <Link :href="route('profile.edit')" class="st-btn-secondary mt-5 w-full">Edit profile</Link>
  </section>
</template>
