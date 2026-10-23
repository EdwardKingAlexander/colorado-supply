import axios from 'axios'
import { computed, reactive } from 'vue'
import { getEcho } from '@/Support/echo'

// One shared notification state for the bell, toasts and the notifications
// page (ai/modules/will-call-notifications-complete, phase 3). Live over Reverb when
// available; otherwise the unread count is refreshed every minute.
const POLL_MS = 60_000
const MAX_TOASTS = 3

const state = reactive({
  items: [],
  unread: 0,
  nextPage: 1,
  loaded: false,
  loading: false,
  error: false,
  toasts: [],
  live: false,
})

let started = false
let pollTimer = null
let toastId = 0

const replace = (id, changes) => {
  state.items = state.items.map((item) => (item.id === id ? { ...item, ...changes } : item))
}

const load = async ({ reset = false, filter = 'all' } = {}) => {
  if (state.loading || (!reset && state.loaded && state.nextPage === null)) {
    return
  }

  state.loading = true
  state.error = false

  try {
    const page = reset ? 1 : state.nextPage ?? 1
    const { data } = await axios.get(route('notifications.index'), { params: { page, filter } })
    state.items = reset || page === 1 ? data.notifications : [...state.items, ...data.notifications]
    state.unread = data.unread_count
    state.nextPage = data.next_page
    state.loaded = true
  } catch {
    state.error = true
  } finally {
    state.loading = false
  }
}

const markRead = async (item) => {
  if (item.read_at) {
    return
  }
  replace(item.id, { read_at: new Date().toISOString() })
  state.unread = Math.max(0, state.unread - 1)
  try {
    const { data } = await axios.post(route('notifications.mark-read', { notification: item.id }))
    state.unread = data.unread_count
  } catch {
    // Not fatal: the next load corrects it.
  }
}

const markUnread = async (item) => {
  replace(item.id, { read_at: null })
  const { data } = await axios.post(route('notifications.mark-unread', { notification: item.id }))
  state.unread = data.unread_count
}

const remove = async (item) => {
  state.items = state.items.filter((existing) => existing.id !== item.id)
  const { data } = await axios.delete(route('notifications.destroy', { notification: item.id }))
  state.unread = data.unread_count
}

const markAllRead = async () => {
  const now = new Date().toISOString()
  state.items = state.items.map((item) => ({ ...item, read_at: item.read_at ?? now }))
  state.unread = 0
  await axios.post(route('notifications.read'))
}

const dismissToast = (toastKey) => {
  state.toasts = state.toasts.filter((toast) => toast.toastId !== toastKey)
}

const receive = (payload) => {
  const item = {
    id: payload.id,
    type: payload.type,
    title: payload.title ?? payload.label ?? 'Update',
    body: payload.body ?? null,
    url: payload.url ?? payload.tracker_url ?? null,
    icon: payload.icon ?? null,
    read_at: null,
    created_at: new Date().toISOString(),
    created_human: 'Just now',
  }

  if (!state.items.some((existing) => existing.id === item.id)) {
    state.items = [item, ...state.items]
    state.unread += 1
  }

  state.toasts = [...state.toasts, { ...item, toastId: ++toastId }].slice(-MAX_TOASTS)
}

const startPolling = () => {
  if (pollTimer) {
    return
  }
  pollTimer = setInterval(() => {
    if (document.visibilityState === 'visible') {
      load({ reset: true })
    }
  }, POLL_MS)
}

/**
 * Called once from the layout with the shared Inertia props.
 */
const start = async ({ userId, unread, live }) => {
  state.unread = unread ?? 0

  if (started || !userId || typeof window === 'undefined') {
    return
  }
  started = true

  const echo = live ? await getEcho() : null

  if (!echo) {
    startPolling()
    return
  }

  const channel = echo.private(`App.Models.User.${userId}`)
  channel.notification(receive)
  channel.error?.(() => startPolling())
  state.live = true
}

export const useNotifications = () => ({
  state,
  hasMore: computed(() => state.nextPage !== null),
  start,
  load,
  markRead,
  markUnread,
  remove,
  markAllRead,
  dismissToast,
  receive,
})
