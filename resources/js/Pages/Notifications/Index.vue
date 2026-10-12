<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'
import StoreLayout from '@/Layouts/StoreLayout.vue'
import NotificationIcon from '@/Components/Notifications/NotificationIcon.vue'
import PushOptIn from '@/Components/Notifications/PushOptIn.vue'
import SmsOptIn from '@/Components/Notifications/SmsOptIn.vue'
import { useNotifications } from '@/Stores/useNotifications'

// Full notifications list (ai/modules/will-call-notifications, phase 3).
const props = defineProps({
  notifications: { type: Array, default: () => [] },
  unread_count: { type: Number, default: 0 },
  next_page: { type: Number, default: null },
  filter: { type: String, default: 'all' },
})

const { state, hasMore, load, markRead, markUnread, remove, markAllRead } = useNotifications()

// Server-rendered first page; later pages and live arrivals go through the store.
onMounted(() => {
  state.items = props.notifications
  state.unread = props.unread_count
  state.nextPage = props.next_page
  state.loaded = true
})

const items = computed(() => (state.loaded ? state.items : props.notifications))
const visible = computed(() => (props.filter === 'unread' ? items.value.filter((item) => !item.read_at) : items.value))

const groupOf = (item) => {
  const created = new Date(item.created_at)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const weekAgo = new Date(today)
  weekAgo.setDate(today.getDate() - 6)

  if (created >= today) return 'Today'
  if (created >= weekAgo) return 'This week'
  return 'Earlier'
}

const groups = computed(() => {
  const order = ['Today', 'This week', 'Earlier']
  const buckets = Object.fromEntries(order.map((name) => [name, []]))
  visible.value.forEach((item) => buckets[groupOf(item)].push(item))
  return order.filter((name) => buckets[name].length).map((name) => ({ name, items: buckets[name] }))
})

const tabClass = (name) => (props.filter === name
  ? 'border-st-action text-st-ink'
  : 'border-transparent text-st-muted hover:text-st-ink')
</script>

<template>
  <Head title="Notifications" />

  <StoreLayout>
    <div class="mobile-page-gutter mx-auto max-w-3xl space-y-6 py-6 sm:py-8">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="st-display text-2xl font-extrabold text-st-ink sm:text-3xl">Notifications</h1>
        <button
          v-if="state.unread"
          type="button"
          class="min-h-11 rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          @click="markAllRead"
        >Mark all read</button>
      </div>

      <PushOptIn />
      <SmsOptIn />

      <nav aria-label="Filter notifications" class="flex gap-4 border-b border-st-line">
        <Link
          :href="route('notifications.index')"
          :class="tabClass('all')"
          class="inline-flex min-h-11 items-center border-b-2 px-1 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          :aria-current="filter === 'all' ? 'page' : undefined"
        >All</Link>
        <Link
          :href="route('notifications.index', { filter: 'unread' })"
          :class="tabClass('unread')"
          class="inline-flex min-h-11 items-center border-b-2 px-1 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          :aria-current="filter === 'unread' ? 'page' : undefined"
        >Unread<span v-if="state.unread" class="ml-1.5 rounded-full bg-st-sunk px-2 text-xs">{{ state.unread }}</span></Link>
      </nav>

      <p v-if="!groups.length" class="rounded-lg border border-st-line bg-st-panel px-4 py-10 text-center text-base text-st-muted">
        {{ filter === 'unread' ? 'No unread notifications.' : 'No notifications yet. Order and pickup updates will appear here.' }}
      </p>

      <section v-for="group in groups" :key="group.name" :aria-labelledby="`group-${group.name}`" class="space-y-2">
        <h2 :id="`group-${group.name}`" class="text-sm font-semibold uppercase tracking-wide text-st-muted">{{ group.name }}</h2>
        <ul class="divide-y divide-st-line overflow-hidden rounded-lg border border-st-line bg-st-panel">
          <li v-for="item in group.items" :key="item.id" class="flex items-start gap-3 p-4" :class="{ 'bg-st-action/5': !item.read_at }">
            <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-st-sunk text-st-ink">
              <NotificationIcon :name="item.icon" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-base font-semibold text-st-ink">
                <a v-if="item.url" :href="item.url" class="hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action" @click="markRead(item)">{{ item.title }}</a>
                <template v-else>{{ item.title }}</template>
                <span v-if="!item.read_at" class="sr-only"> (unread)</span>
              </p>
              <p v-if="item.body" class="mt-0.5 text-sm text-st-ink/85">{{ item.body }}</p>
              <p class="mt-1 text-xs text-st-muted">
                <time :datetime="item.created_at">{{ item.created_human }}</time>
              </p>
              <div class="mt-1 flex flex-wrap gap-x-2">
                <button
                  type="button"
                  class="min-h-11 rounded-md px-2 text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                  @click="item.read_at ? markUnread(item) : markRead(item)"
                >{{ item.read_at ? 'Mark unread' : 'Mark read' }}</button>
                <button
                  type="button"
                  class="min-h-11 rounded-md px-2 text-sm font-semibold text-st-muted hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
                  :aria-label="`Delete notification: ${item.title}`"
                  @click="remove(item)"
                >Delete</button>
              </div>
            </div>
            <span v-if="!item.read_at" class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-st-action" aria-hidden="true" />
          </li>
        </ul>
      </section>

      <div v-if="hasMore" class="flex justify-center">
        <button
          type="button"
          class="min-h-11 rounded-md border border-st-line px-6 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action disabled:opacity-60"
          :disabled="state.loading"
          @click="load({ filter })"
        >{{ state.loading ? 'Loading…' : 'Load more' }}</button>
      </div>
    </div>
  </StoreLayout>
</template>
