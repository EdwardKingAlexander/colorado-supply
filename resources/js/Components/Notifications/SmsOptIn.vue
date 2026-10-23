<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ChatBubbleLeftEllipsisIcon } from '@heroicons/vue/24/outline'

// Text alerts for ready-for-pickup and reminders (ai/modules/will-call-notifications-complete,
// phase 6). Shown only once SMS is live on the server. Consent is an
// unchecked box with the exact wording that gets recorded.
const page = usePage()
const consentText = computed(() => page.props.notificationBell?.smsConsent ?? null)

const status = ref({ state: 'loading' })
const phone = ref('')
const consent = ref(false)
const code = ref('')
const busy = ref(false)
const errors = ref({})

const load = async () => {
  const { data } = await axios.get(route('sms-subscription.show'))
  status.value = data
}

onMounted(() => {
  if (consentText.value) {
    load().catch(() => { status.value = { state: 'off' } })
  }
})

const call = async (request) => {
  busy.value = true
  errors.value = {}
  try {
    const { data } = await request()
    status.value = data
    code.value = ''
  } catch (error) {
    errors.value = error.response?.status === 422
      ? Object.fromEntries(Object.entries(error.response.data.errors ?? {}).map(([key, messages]) => [key, messages[0]]))
      : { general: 'Something went wrong. Please try again.' }
  } finally {
    busy.value = false
  }
}

const sendCode = () => call(() => axios.post(route('sms-subscription.store'), { phone: phone.value, consent: consent.value }))
const verify = () => call(() => axios.post(route('sms-subscription.verify'), { code: code.value }))
const stop = () => call(() => axios.delete(route('sms-subscription.destroy')))
const restart = () => { status.value = { state: 'off' } }
</script>

<template>
  <section v-if="consentText" aria-labelledby="sms-heading" class="space-y-3 rounded-lg border border-st-line bg-st-panel p-4 sm:p-6">
    <div class="flex items-start gap-3">
      <ChatBubbleLeftEllipsisIcon class="mt-0.5 h-6 w-6 shrink-0 text-st-muted" aria-hidden="true" />
      <div class="min-w-0 flex-1 space-y-1">
        <h2 id="sms-heading" class="text-base font-semibold text-st-ink">Text alerts</h2>
        <p class="text-sm text-st-muted">A text when your will call order is ready, and a reminder if it's still waiting.</p>
      </div>
    </div>

    <p v-if="status.state === 'loading'" class="text-sm text-st-muted">Loading…</p>

    <div v-else-if="status.state === 'on'" class="flex flex-wrap items-center gap-3">
      <span class="text-sm font-semibold text-st-ok">On for {{ status.phone }}</span>
      <button type="button" class="min-h-11 rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action disabled:opacity-60" :disabled="busy" @click="stop">Stop texts</button>
    </div>

    <div v-else-if="status.state === 'opted_out'" class="space-y-2 text-sm text-st-ink/85">
      <p>You replied STOP from {{ status.phone }}, so we won't text it. Text START to our number to turn texts back on, or use a different number.</p>
      <button type="button" class="min-h-11 rounded-md border border-st-line px-4 text-sm font-semibold text-st-ink hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action" @click="restart">Use a different number</button>
    </div>

    <form v-else-if="status.state === 'pending'" class="space-y-3" @submit.prevent="verify">
      <p class="text-sm text-st-ink/85">We texted a 6-digit code to {{ status.phone }}.</p>
      <div>
        <label for="sms-code" class="block text-sm font-semibold text-st-ink">Code</label>
        <input
          id="sms-code"
          v-model="code"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="6"
          class="mt-1 block w-40 rounded-md border-st-line bg-st-bg text-base text-st-ink focus:border-st-action focus:ring-st-action"
          :aria-invalid="Boolean(errors.code)"
          aria-describedby="sms-code-error"
        />
        <p v-if="errors.code" id="sms-code-error" class="mt-1 text-sm text-red-700 dark:text-red-400">{{ errors.code }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button type="submit" class="min-h-11 rounded-md bg-st-fill px-4 text-sm font-semibold text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action focus-visible:ring-offset-2 disabled:opacity-60" :disabled="busy">Verify</button>
        <button type="button" class="min-h-11 rounded-md px-3 text-sm font-semibold text-st-muted hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action" @click="restart">Change number or resend</button>
      </div>
    </form>

    <form v-else class="space-y-3" @submit.prevent="sendCode">
      <div>
        <label for="sms-phone" class="block text-sm font-semibold text-st-ink">Mobile number</label>
        <input
          id="sms-phone"
          v-model="phone"
          type="tel"
          inputmode="tel"
          autocomplete="tel-national"
          placeholder="(303) 555-0100"
          class="mt-1 block w-full max-w-xs rounded-md border-st-line bg-st-bg text-base text-st-ink focus:border-st-action focus:ring-st-action"
          :aria-invalid="Boolean(errors.phone)"
          aria-describedby="sms-phone-error"
        />
        <p v-if="errors.phone" id="sms-phone-error" class="mt-1 text-sm text-red-700 dark:text-red-400">{{ errors.phone }}</p>
      </div>
      <label class="flex cursor-pointer items-start gap-3 text-sm text-st-ink/85">
        <input v-model="consent" type="checkbox" class="mt-0.5 h-5 w-5 shrink-0 rounded border-st-line" aria-describedby="sms-consent-error" />
        <span>{{ consentText }} <a :href="route('sms-terms')" class="font-semibold text-st-action underline" target="_blank" rel="noopener">Text message terms<span class="sr-only"> (opens in a new tab)</span></a></span>
      </label>
      <p v-if="errors.consent" id="sms-consent-error" class="text-sm text-red-700 dark:text-red-400">{{ errors.consent }}</p>
      <button type="submit" class="min-h-11 rounded-md bg-st-fill px-4 text-sm font-semibold text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action focus-visible:ring-offset-2 disabled:opacity-60" :disabled="busy">Text me a code</button>
    </form>

    <p v-if="errors.general" class="text-sm text-red-700 dark:text-red-400" role="alert">{{ errors.general }}</p>
  </section>
</template>
