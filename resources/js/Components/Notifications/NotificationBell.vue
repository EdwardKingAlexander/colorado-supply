<script setup>
import { Link } from '@inertiajs/vue3'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { BellIcon } from '@heroicons/vue/24/outline'
import NotificationIcon from '@/Components/Notifications/NotificationIcon.vue'
import { useNotifications } from '@/Stores/useNotifications'

// Store header bell (ai/modules/will-call-notifications-complete, phase 3).
const { state, load, markRead, markAllRead } = useNotifications()

const root = ref(null)
const button = ref(null)
const open = ref(false)

const toggle = () => {
  open.value = !open.value
  if (open.value) {
    load({ reset: true })
  }
}

const close = (returnFocus = false) => {
  open.value = false
  if (returnFocus) {
    button.value?.focus()
  }
}

const follow = (item) => {
  markRead(item)
  close()
}

const onDocumentClick = (event) => {
  if (open.value && root.value && !root.value.contains(event.target)) {
    close()
  }
}

const onKeydown = (event) => {
  if (open.value && event.key === 'Escape') {
    close(true)
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  document.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div ref="root" class="relative">
    <button
      ref="button"
      type="button"
      class="relative inline-flex h-11 w-11 items-center justify-center rounded-md text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
      :aria-expanded="open"
      aria-controls="notification-panel"
      :aria-label="state.unread ? `Notifications, ${state.unread} unread` : 'Notifications'"
      @click="toggle"
    >
      <BellIcon class="h-6 w-6" aria-hidden="true" />
      <span
        v-if="state.unread"
        class="st-num absolute right-0.5 top-0.5 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white"
        aria-hidden="true"
      >{{ state.unread > 99 ? '99+' : state.unread }}</span>
    </button>

    <div
      v-if="open"
      id="notification-panel"
      class="fixed inset-x-2 top-16 z-50 overflow-hidden rounded-lg border border-st-line bg-st-panel shadow-2xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-96"
    >
      <div class="flex items-center justify-between border-b border-st-line px-4 py-1">
        <h2 class="text-base font-semibold text-st-ink">Notifications</h2>
        <button
          v-if="state.unread"
          type="button"
          class="min-h-11 rounded-md px-2 text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          @click="markAllRead"
        >Mark all read</button>
      </div>

      <p v-if="state.loading && !state.items.length" class="px-4 py-6 text-center text-sm text-st-muted">Loading…</p>
      <p v-else-if="state.error && !state.items.length" class="px-4 py-6 text-center text-sm text-red-700 dark:text-red-400">Notifications could not be loaded.</p>
      <p v-else-if="!state.items.length" class="px-4 py-6 text-center text-sm text-st-muted">You're all caught up.</p>

      <ul v-else class="max-h-[60vh] divide-y divide-st-line overflow-y-auto">
        <li v-for="item in state.items.slice(0, 15)" :key="item.id">
          <component
            :is="item.url ? 'a' : 'button'"
            :href="item.url || undefined"
            :type="item.url ? undefined : 'button'"
            class="flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-st-sunk focus:bg-st-sunk focus:outline-none"
            :class="{ 'bg-st-action/5': !item.read_at }"
            @click="follow(item)"
          >
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-st-sunk text-st-ink">
              <NotificationIcon :name="item.icon" class="h-5 w-5" />
            </span>
            <span class="min-w-0 flex-1">
              <span class="block text-sm font-semibold text-st-ink">
                {{ item.title }}<span v-if="!item.read_at" class="sr-only"> (unread)</span>
              </span>
              <span v-if="item.body" class="mt-0.5 line-clamp-2 block text-sm text-st-muted">{{ item.body }}</span>
              <span class="mt-1 block text-xs text-st-muted">{{ item.created_human }}</span>
            </span>
            <span v-if="!item.read_at" class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-st-action" aria-hidden="true" />
          </component>
        </li>
      </ul>

      <div class="border-t border-st-line">
        <Link
          :href="route('notifications.index')"
          class="flex min-h-11 items-center justify-center text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-st-action"
          @click="close()"
        >See all notifications</Link>
      </div>
    </div>
  </div>
</template>
