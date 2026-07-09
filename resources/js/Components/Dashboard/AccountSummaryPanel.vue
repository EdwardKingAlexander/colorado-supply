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
  <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Account</p>
        <h2 class="mt-1 text-lg font-semibold text-gray-900">{{ account.name }}</h2>
        <p class="text-sm text-gray-600">{{ account.email }}</p>
      </div>
      <span
        class="rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="account.profile_complete ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'"
      >
        {{ account.profile_complete ? 'Complete' : 'Needs setup' }}
      </span>
    </div>

    <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
      <div>
        <dt class="text-gray-500">Company</dt>
        <dd class="mt-1 font-medium text-gray-900">{{ account.company?.name ?? 'Not assigned' }}</dd>
      </div>
      <div>
        <dt class="text-gray-500">Locations</dt>
        <dd class="mt-1 font-medium text-gray-900">{{ account.locations_count }} / {{ account.sublocations_count }} sub</dd>
      </div>
    </dl>

    <div class="mt-5 flex flex-wrap gap-2">
      <Link :href="route('profile.edit')" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
        Edit profile
      </Link>
      <Link :href="route('store.index')" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-800">
        Browse store
      </Link>
    </div>
  </section>
</template>
