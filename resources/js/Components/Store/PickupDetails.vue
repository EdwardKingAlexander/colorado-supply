<script setup>
import { computed } from 'vue'

// Will-call pickup details for an order (Order::pickupDetails()). The pickup
// code is only present once the order is ready. `variant` matches the page:
// 'store' inside StoreLayout (st-* tokens), 'plain' on standalone pages.
const props = defineProps({
  pickup: { type: Object, required: true },
  variant: { type: String, default: 'store' },
})

const location = computed(() => props.pickup.location ?? {})
const status = computed(() => props.pickup.status?.value)
const mapUrl = computed(() => (location.value.address
  ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location.value.address)}`
  : null))

const message = computed(() => ({
  awaiting_pick: "We're pulling your order now. We'll let you know when it's ready. Please wait for that message before coming in.",
  ready_for_pickup: 'Your order is ready. Bring your pickup code to the counter.',
  picked_up: props.pickup.picked_up_at ? `Picked up ${props.pickup.picked_up_at}.` : 'Picked up.',
}[status.value] ?? null))

const styles = computed(() => (props.variant === 'store'
  ? {
      box: 'border-st-line bg-st-panel',
      heading: 'text-st-ink',
      text: 'text-st-ink/85',
      muted: 'text-st-muted',
      link: 'text-st-action',
      code: 'border-st-signal bg-st-signal/10 text-st-ink',
    }
  : {
      box: 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800',
      heading: 'text-gray-900 dark:text-white',
      text: 'text-gray-700 dark:text-gray-200',
      muted: 'text-gray-500 dark:text-gray-400',
      link: 'text-blue-700 dark:text-blue-300',
      code: 'border-amber-400 bg-amber-50 text-gray-900 dark:bg-amber-900/30 dark:text-white',
    }))
</script>

<template>
  <section aria-labelledby="pickup-heading" class="space-y-3 rounded-lg border p-4 text-left shadow-sm sm:p-6" :class="styles.box">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 id="pickup-heading" class="text-lg font-semibold" :class="styles.heading">Will Call pickup</h2>
      <span v-if="pickup.status" class="rounded-full border px-3 py-1 text-sm font-semibold" :class="[styles.box, styles.text]">
        {{ pickup.status.label }}
      </span>
    </div>

    <p v-if="message" class="text-base" :class="styles.text">{{ message }}</p>

    <div v-if="pickup.code" class="rounded-md border-2 px-4 py-3" :class="styles.code">
      <p class="text-sm font-semibold uppercase tracking-wide">Pickup code</p>
      <p class="font-mono text-2xl font-bold tracking-widest">{{ pickup.code }}</p>
    </div>

    <dl class="space-y-2 text-base">
      <div>
        <dt class="sr-only">Location</dt>
        <dd class="font-semibold" :class="styles.heading">{{ location.name }}</dd>
      </div>
      <div v-if="location.address">
        <dt class="sr-only">Address</dt>
        <dd :class="styles.text">
          {{ location.address }}
          <a
            v-if="mapUrl"
            :href="mapUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="ml-1 inline-flex min-h-11 items-center font-semibold underline-offset-2 hover:underline"
            :class="styles.link"
          >Map<span class="sr-only"> (opens in a new tab)</span></a>
        </dd>
      </div>
      <div v-if="location.hours">
        <dt class="text-sm font-semibold" :class="styles.muted">Hours</dt>
        <dd class="whitespace-pre-line" :class="styles.text">{{ location.hours }}</dd>
      </div>
      <div v-if="location.phone">
        <dt class="text-sm font-semibold" :class="styles.muted">Phone</dt>
        <dd>
          <a :href="`tel:${location.phone}`" class="inline-flex min-h-11 items-center font-semibold" :class="styles.link">{{ location.phone }}</a>
        </dd>
      </div>
      <div v-if="location.instructions">
        <dt class="text-sm font-semibold" :class="styles.muted">Instructions</dt>
        <dd class="whitespace-pre-line" :class="styles.text">{{ location.instructions }}</dd>
      </div>
    </dl>
  </section>
</template>
