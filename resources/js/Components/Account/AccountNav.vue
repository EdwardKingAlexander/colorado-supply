<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useNotifications } from '@/Stores/useNotifications'

// Account sub-nav under the store header (ai/modules/customer-portal-ui-rebrand-complete, phase 2).
const page = usePage()
const notifications = useNotifications()

const tabs = computed(() => {
  const user = page.props.auth?.user

  if (!user) {
    return [{ label: 'Overview', route: 'dashboard', active: ['dashboard'] }]
  }

  return [
    { label: 'Overview', route: 'dashboard', active: ['dashboard'] },
    { label: 'Reports', route: 'dashboard.reports', active: ['dashboard.reports'] },
    { label: 'Notifications', route: 'notifications.index', active: ['notifications.*'], badge: notifications.state.unread },
    user.company_id ? { label: 'Company', route: 'company.index', active: ['company.*'] } : null,
    { label: 'Profile', route: 'profile.edit', active: ['profile.*'] },
  ].filter(Boolean)
})

const isActive = (tab) => tab.active.some((name) => route().current(name))
</script>

<template>
  <nav aria-label="Account" class="-mb-px flex gap-6 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
    <Link
      v-for="tab in tabs"
      :key="tab.route"
      :href="route(tab.route)"
      class="inline-flex min-h-12 shrink-0 items-center gap-2 border-b-2 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-st-action"
      :class="isActive(tab) ? 'border-st-action text-st-ink' : 'border-transparent text-st-muted hover:border-st-line hover:text-st-ink'"
      :aria-current="isActive(tab) ? 'page' : undefined"
    >
      {{ tab.label }}
      <span
        v-if="tab.badge"
        class="st-num inline-flex min-w-5 items-center justify-center rounded-full bg-st-signal px-1.5 text-xs font-bold text-[#1a1300]"
      >
        {{ tab.badge > 99 ? '99+' : tab.badge }}<span class="sr-only"> unread</span>
      </span>
    </Link>
  </nav>
</template>
