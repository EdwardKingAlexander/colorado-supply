<script setup>
import { onBeforeUnmount, watch } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import NotificationIcon from '@/Components/Notifications/NotificationIcon.vue'
import { useNotifications } from '@/Stores/useNotifications'

// Live notification pop-ups (ai/modules/will-call-notifications-complete, phase 3).
// Announced politely to screen readers; each one dismisses itself after
// 6 seconds unless the pointer or keyboard focus is on it.
const { state, dismissToast, markRead } = useNotifications()

const DURATION_MS = 6000
const timers = new Map()

const schedule = (toast) => {
  clearTimeout(timers.get(toast.toastId))
  timers.set(toast.toastId, setTimeout(() => dismissToast(toast.toastId), DURATION_MS))
}
const pause = (toast) => clearTimeout(timers.get(toast.toastId))

watch(() => state.toasts.map((toast) => toast.toastId), (ids, previous = []) => {
  state.toasts.filter((toast) => !previous.includes(toast.toastId)).forEach(schedule)
  for (const id of [...timers.keys()]) {
    if (!ids.includes(id)) {
      clearTimeout(timers.get(id))
      timers.delete(id)
    }
  }
})

onBeforeUnmount(() => timers.forEach((timer) => clearTimeout(timer)))

const open = (toast) => {
  markRead(toast)
  dismissToast(toast.toastId)
}
</script>

<template>
  <div
    role="status"
    aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 px-4 pb-20 sm:inset-x-auto sm:bottom-4 sm:right-4 sm:items-end sm:px-0 sm:pb-0"
  >
    <TransitionGroup
      enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
      enter-from-class="translate-y-2 opacity-0"
      leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
      leave-to-class="opacity-0"
    >
      <div
        v-for="toast in state.toasts"
        :key="toast.toastId"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-st-line bg-st-panel p-4 shadow-2xl"
        @mouseenter="pause(toast)"
        @mouseleave="schedule(toast)"
        @focusin="pause(toast)"
        @focusout="schedule(toast)"
      >
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-st-sunk text-st-ink">
          <NotificationIcon :name="toast.icon" class="h-5 w-5" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-semibold text-st-ink">{{ toast.title }}</p>
          <p v-if="toast.body" class="mt-0.5 line-clamp-2 text-sm text-st-muted">{{ toast.body }}</p>
          <a
            v-if="toast.url"
            :href="toast.url"
            class="inline-flex min-h-11 items-center text-sm font-semibold text-st-action hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
            @click="open(toast)"
          >View</a>
        </div>
        <button
          type="button"
          class="-m-1 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          aria-label="Dismiss notification"
          @click="dismissToast(toast.toastId)"
        >
          <XMarkIcon class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
