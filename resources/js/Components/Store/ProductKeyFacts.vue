<script setup>
import { computed, ref } from 'vue'
import { copyText } from '@/Support/clipboard'

// Part numbers and ordering facts, each identifier with a copy button.
// Rows only appear when the product has the value.
const props = defineProps({
  product: { type: Object, required: true },
})

const formatNsn = (nsn) => (/^\d{13}$/.test(nsn) ? `${nsn.slice(0, 4)}-${nsn.slice(4, 6)}-${nsn.slice(6, 9)}-${nsn.slice(9)}` : nsn)

const facts = computed(() => {
  const ids = props.product.identifiers ?? {}
  const unit = props.product.unit_of_measure ?? 'EA'
  const minimum = props.product.minimum_order_quantity ?? 1

  return [
    { key: 'sku', label: 'SKU', value: props.product.sku, copy: true },
    { key: 'mpn', label: 'MPN', value: ids.mpn, copy: true },
    { key: 'part_number', label: 'Part number', value: ids.part_number, copy: true },
    { key: 'nsn', label: 'NSN', value: ids.nsn ? formatNsn(ids.nsn) : null, copy: true },
    { key: 'cage_code', label: 'CAGE', value: ids.cage_code, copy: true },
    { key: 'unit', label: 'Unit', value: unit, copy: false },
    { key: 'minimum', label: 'Minimum order', value: minimum > 1 ? `${minimum.toLocaleString()} ${unit}` : null, copy: false },
  ].filter((fact) => fact.value)
})

const copied = ref(null)
let resetTimer

const copy = async (fact) => {
  if (await copyText(fact.value)) {
    copied.value = fact.key
    clearTimeout(resetTimer)
    resetTimer = setTimeout(() => { copied.value = null }, 2000)
  }
}
</script>

<template>
  <div>
  <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
    <div v-for="fact in facts" :key="fact.key" class="flex min-w-0 items-center justify-between gap-2 border-b border-st-line/60 pb-2">
      <dt class="shrink-0 text-st-muted">{{ fact.label }}</dt>
      <dd class="flex min-w-0 items-center gap-1">
        <span class="st-num truncate font-medium text-st-ink">{{ fact.value }}</span>
        <button
          v-if="fact.copy"
          type="button"
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-st-muted hover:bg-st-sunk hover:text-st-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          :aria-label="`Copy ${fact.label} ${fact.value}`"
          @click="copy(fact)"
        >
          <svg v-if="copied !== fact.key" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="9" y="9" width="11" height="11" rx="2" />
            <path d="M5 15V5a2 2 0 0 1 2-2h10" />
          </svg>
          <svg v-else class="h-4 w-4 text-st-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="m5 12 5 5L20 7" />
          </svg>
        </button>
      </dd>
    </div>
  </dl>
  <p class="sr-only" aria-live="polite">{{ copied ? 'Copied to clipboard' : '' }}</p>
  </div>
</template>
