/*
 * Push notifications service worker (ai/modules/will-call-notifications, phase 5).
 *
 * Shows pushes sent by laravel-notification-channels/webpush (the payload is
 * WebPushMessage::toArray(): title, body, icon, badge, tag, data.url) and
 * opens or focuses the right page when one is clicked. It does no caching.
 */

self.addEventListener('install', () => self.skipWaiting())
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()))

self.addEventListener('push', (event) => {
  let payload = {}

  try {
    payload = event.data ? event.data.json() : {}
  } catch {
    payload = { body: event.data ? event.data.text() : '' }
  }

  const { title = 'Colorado Supply', ...options } = payload

  event.waitUntil(self.registration.showNotification(title, {
    body: options.body,
    icon: options.icon || '/apple-touch-icon.png',
    badge: options.badge,
    tag: options.tag,
    data: options.data || {},
    requireInteraction: Boolean(options.requireInteraction),
  }))
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()

  const target = new URL(event.notification.data?.url || '/', self.location.origin)

  // Only ever open pages on this site.
  if (target.origin !== self.location.origin) {
    return
  }

  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true })
    const existing = windows.find((client) => client.url === target.href)

    if (existing) {
      return existing.focus()
    }

    return self.clients.openWindow(target.href)
  })())
})
