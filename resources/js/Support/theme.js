// Theme for signed-in portal pages (ai/modules/customer-portal-ui-rebrand-complete).
// Portal pages follow a per-browser Light / Dark / System choice, default dark.
// Public marketing and auth pages stay forced dark. The first paint is handled
// by the inline script in resources/views/app.blade.php; layouts call these on
// mount so client-side Inertia visits between the two kinds of page stay right.
import { ref } from 'vue'

const STORAGE_KEY = 'cs-theme'
const PREFERENCES = ['light', 'dark', 'system']
const META_COLORS = { portalDark: '#0E1624', portalLight: '#F3F5F8', site: '#111827' }

export const themePreference = ref('dark')

let systemQuery = null
let portalActive = false

const readStored = () => {
  try {
    const stored = window.localStorage.getItem(STORAGE_KEY)
    return PREFERENCES.includes(stored) ? stored : 'dark'
  } catch {
    return 'dark'
  }
}

const setMeta = (color) => {
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content', color)
}

const prefersDark = () => window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? true

const render = () => {
  if (typeof document === 'undefined' || !portalActive) {
    return
  }

  const dark = themePreference.value === 'system' ? prefersDark() : themePreference.value !== 'light'
  document.documentElement.classList.toggle('dark', dark)
  setMeta(dark ? META_COLORS.portalDark : META_COLORS.portalLight)
}

const watchSystem = () => {
  if (systemQuery || typeof window === 'undefined' || !window.matchMedia) {
    return
  }

  systemQuery = window.matchMedia('(prefers-color-scheme: dark)')
  systemQuery.addEventListener?.('change', () => {
    if (themePreference.value === 'system') {
      render()
    }
  })
}

export function applyPortalTheme() {
  if (typeof window === 'undefined') {
    return
  }

  portalActive = true
  themePreference.value = readStored()
  watchSystem()
  render()
}

export function forceSiteDark() {
  if (typeof document === 'undefined') {
    return
  }

  portalActive = false
  document.documentElement.classList.add('dark')
  setMeta(META_COLORS.site)
}

export function setThemePreference(preference) {
  if (!PREFERENCES.includes(preference)) {
    return
  }

  themePreference.value = preference

  try {
    window.localStorage.setItem(STORAGE_KEY, preference)
  } catch {
    // Storage blocked (private mode): the choice lasts for this page view.
  }

  render()
}
