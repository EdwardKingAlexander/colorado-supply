// Laravel Echo over Reverb for live notifications
// (ai/modules/will-call-notifications, phase 3). Created lazily in the
// browser only (never during SSR), and only when Reverb is configured.
let echo = null
let attempted = false

export const getEcho = async () => {
  if (attempted || typeof window === 'undefined') {
    return echo
  }

  attempted = true

  const key = import.meta.env.VITE_REVERB_APP_KEY
  if (!key) {
    return null
  }

  try {
    const [{ default: Echo }, { default: Pusher }] = await Promise.all([import('laravel-echo'), import('pusher-js')])
    window.Pusher = Pusher

    const port = Number(import.meta.env.VITE_REVERB_PORT) || undefined
    echo = new Echo({
      broadcaster: 'reverb',
      key,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: port ?? 80,
      wssPort: port ?? 443,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
    })
  } catch {
    // Live updates are a bonus: the bell still polls without them.
    echo = null
  }

  return echo
}
