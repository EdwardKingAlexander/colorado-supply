import axios from 'axios'

// Browser push helpers (ai/modules/will-call-notifications-complete, phase 5).
// Permission is only ever requested from a click, never on page load.

const SW_URL = '/sw.js'

const toKey = (base64) => {
  const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(padded)
  return Uint8Array.from(raw, (char) => char.charCodeAt(0))
}

export const isIos = () => typeof navigator !== 'undefined'
  && (/iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1))

export const isInstalled = () => typeof window !== 'undefined'
  && (window.matchMedia?.('(display-mode: standalone)').matches || window.navigator.standalone === true)

/**
 * 'unsupported' | 'ios-install' | 'denied' | 'off' | 'on'
 */
export const pushStatus = async () => {
  if (typeof window === 'undefined') {
    return 'unsupported'
  }

  const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window

  if (!supported) {
    // iPhone/iPad only expose push to sites added to the Home Screen.
    return isIos() && !isInstalled() ? 'ios-install' : 'unsupported'
  }

  if (Notification.permission === 'denied') {
    return 'denied'
  }

  const registration = await navigator.serviceWorker.getRegistration(SW_URL)
  const subscription = await registration?.pushManager.getSubscription()

  return subscription && Notification.permission === 'granted' ? 'on' : 'off'
}

/**
 * Ask permission, subscribe this browser and save it to the account.
 * Returns the new status.
 */
export const enablePush = async (publicKey) => {
  const permission = await Notification.requestPermission()
  if (permission !== 'granted') {
    return permission === 'denied' ? 'denied' : 'off'
  }

  const registration = await navigator.serviceWorker.register(SW_URL)
  await navigator.serviceWorker.ready

  const subscription = (await registration.pushManager.getSubscription())
    ?? await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toKey(publicKey) })

  const json = subscription.toJSON()
  await axios.post(route('push-subscriptions.store'), {
    endpoint: json.endpoint,
    keys: json.keys,
    content_encoding: (PushManager.supportedContentEncodings ?? ['aes128gcm'])[0],
  })

  return 'on'
}

/**
 * Stop push on this browser and remove it from the account.
 */
export const disablePush = async () => {
  const registration = await navigator.serviceWorker.getRegistration(SW_URL)
  const subscription = await registration?.pushManager.getSubscription()

  if (subscription) {
    await axios.delete(route('push-subscriptions.destroy'), { data: { endpoint: subscription.endpoint } })
    await subscription.unsubscribe()
  }

  return 'off'
}
