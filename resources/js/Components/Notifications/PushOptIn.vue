<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { DevicePhoneMobileIcon } from '@heroicons/vue/24/outline'
import { disablePush, enablePush, isIos, pushStatus } from '@/Support/push'

// "Turn on notifications on this device" (ai/modules/will-call-notifications,
// phase 5). `compact` is the one-line prompt shown after a will call order.
const props = defineProps({
  compact: { type: Boolean, default: false },
})

const page = usePage()
const publicKey = computed(() => page.props.notificationBell?.pushKey ?? null)

const status = ref('checking')
const busy = ref(false)
const error = ref(null)
const devices = ref([])
const dismissed = ref(false)

const DISMISS_KEY = 'colorado-supply.push-prompt-dismissed'

const loadDevices = async () => {
  if (props.compact) return
  try {
    const { data } = await axios.get(route('push-subscriptions.index'))
    devices.value = data.devices
  } catch {
    devices.value = []
  }
}

onMounted(async () => {
  try {
    dismissed.value = props.compact && localStorage.getItem(DISMISS_KEY) === '1'
  } catch {
    dismissed.value = false
  }
  status.value = publicKey.value ? await pushStatus() : 'unavailable'
  await loadDevices()
})

const turnOn = async () => {
  busy.value = true
  error.value = null
  try {
    status.value = await enablePush(publicKey.value)
    await loadDevices()
  } catch {
    error.value = 'Notifications could not be turned on. Please try again.'
  } finally {
    busy.value = false
  }
}

const turnOff = async () => {
  busy.value = true
  error.value = null
  try {
    status.value = await disablePush()
    await loadDevices()
  } catch {
    error.value = 'Notifications could not be turned off. Please try again.'
  } finally {
    busy.value = false
  }
}

const removeDevice = async (device) => {
  const { data } = await axios.delete(route('push-subscriptions.destroy'), { data: { id: device.id } })
  devices.value = data.devices
  status.value = await pushStatus()
}

const dismiss = () => {
  dismissed.value = true
  try {
    localStorage.setItem(DISMISS_KEY, '1')
  } catch {
    // Storage blocked: the prompt just comes back next time.
  }
}

const showCompact = computed(() => props.compact && !dismissed.value && ['off', 'ios-install'].includes(status.value))
const iosSteps = computed(() => isIos())
</script>

<template>
  <!-- Compact prompt after an order -->
  <div v-if="compact">
    <div v-if="showCompact" class="flex flex-wrap items-center gap-3 rounded-lg border border-st-line bg-st-panel p-4 text-left">
      <DevicePhoneMobileIcon class="h-6 w-6 shrink-0 text-st-muted" aria-hidden="true" />
      <p class="min-w-0 flex-1 text-sm text-st-ink">
        <template v-if="status === 'ios-install'">To get pickup alerts on this iPhone, tap Share, then <strong>Add to Home Screen</strong>, and open the site from there.</template>
        <template v-else>Get a pop-up on this device when your order is ready?</template>
      </p>
      <div class="flex gap-2">
        <button
          v-if="status === 'off'"
          type="button"
          class="min-h-11 rounded-md bg-st-fill px-4 text-sm font-semibold text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action focus-visible:ring-offset-2 disabled:opacity-60"
          :disabled="busy"
          @click="turnOn"
        >Turn on</button>
        <button
          type="button"
          class="min-h-11 rounded-md px-3 text-sm font-semibold text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          @click="dismiss"
        >Not now</button>
      </div>
      <p v-if="error" class="w-full text-sm text-red-700 dark:text-red-400" role="alert">{{ error }}</p>
    </div>
  </div>

  <!-- Full card on the notifications page -->
  <section v-else aria-labelledby="push-heading" class="space-y-3 rounded-lg border border-st-line bg-st-panel p-4 sm:p-6">
    <div class="flex items-start gap-3">
      <DevicePhoneMobileIcon class="mt-0.5 h-6 w-6 shrink-0 text-st-muted" aria-hidden="true" />
      <div class="min-w-0 flex-1 space-y-1">
        <h2 id="push-heading" class="text-base font-semibold text-st-ink">Notifications on this device</h2>
        <p class="text-sm text-st-muted">Get a pop-up on your phone or computer, even when this site isn't open.</p>
      </div>
    </div>

    <p v-if="status === 'checking'" class="text-sm text-st-muted">Checking this browser…</p>
    <p v-else-if="status === 'unavailable'" class="text-sm text-st-muted">Device notifications aren't available yet.</p>
    <p v-else-if="status === 'unsupported'" class="text-sm text-st-muted">This browser doesn't support notifications. Try Chrome, Edge, Firefox or Safari.</p>
    <div v-else-if="status === 'ios-install'" class="space-y-1 text-sm text-st-ink/85">
      <p class="font-semibold text-st-ink">On iPhone and iPad, add this site to your Home Screen first:</p>
      <ol class="list-decimal space-y-0.5 pl-5">
        <li>Tap the <strong>Share</strong> button in Safari.</li>
        <li>Choose <strong>Add to Home Screen</strong>.</li>
        <li>Open Colorado Supply from your Home Screen and turn notifications on here.</li>
      </ol>
    </div>
    <p v-else-if="status === 'denied'" class="text-sm text-st-ink/85">
      Notifications are blocked for this site. Allow them in your browser's site settings<template v-if="iosSteps"> (Settings › Notifications on iPhone)</template>, then come back.
    </p>
    <div v-else class="flex flex-wrap items-center gap-3">
      <span class="text-sm font-semibold" :class="status === 'on' ? 'text-st-ok' : 'text-st-muted'">
        {{ status === 'on' ? 'On for this device' : 'Off for this device' }}
      </span>
      <button
        v-if="status === 'off'"
        type="button"
        class="min-h-11 rounded-md bg-st-fill px-4 text-sm font-semibold text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action focus-visible:ring-offset-2 disabled:opacity-60"
        :disabled="busy"
        @click="turnOn"
      >Turn on notifications</button>
      <button
        v-else
        type="button"
        class="min-h-11 rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action disabled:opacity-60"
        :disabled="busy"
        @click="turnOff"
      >Turn off for this device</button>
    </div>
    <p v-if="error" class="text-sm text-red-700 dark:text-red-400" role="alert">{{ error }}</p>

    <div v-if="devices.length" class="border-t border-st-line pt-3">
      <h3 class="text-sm font-semibold text-st-ink">Devices with notifications on</h3>
      <ul class="mt-1 divide-y divide-st-line">
        <li v-for="device in devices" :key="device.id" class="flex items-center justify-between gap-3 py-1">
          <span class="min-w-0 text-sm text-st-ink/85">{{ device.label }}<span v-if="device.added" class="text-st-muted"> · added {{ device.added }}</span></span>
          <button
            type="button"
            class="min-h-11 shrink-0 rounded-md px-2 text-sm font-semibold text-st-muted hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
            :aria-label="`Remove ${device.label}`"
            @click="removeDevice(device)"
          >Remove</button>
        </li>
      </ul>
    </div>
  </section>
</template>
