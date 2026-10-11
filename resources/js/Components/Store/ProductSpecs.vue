<script setup>
import { computed } from 'vue'

// Grouped specifications and documents. Groups with no rows, and the whole
// section when nothing exists, are omitted.
const props = defineProps({
  product: { type: Object, required: true },
})

const inches = (mm) => `${mm.toLocaleString()} mm (${(mm / 25.4).toFixed(2)} in)`
const weight = (g) => (g >= 453.6
  ? `${g.toLocaleString()} g (${(g / 453.592).toFixed(2)} lb)`
  : `${g.toLocaleString()} g (${(g / 28.3495).toFixed(2)} oz)`)

const GENERAL_LABELS = { material: 'Material', finish: 'Finish', country_of_origin: 'Country of origin' }
const DIMENSION_ROWS = [
  ['length_mm', 'Length', inches],
  ['width_mm', 'Width', inches],
  ['height_mm', 'Height', inches],
  ['weight_g', 'Weight', weight],
]
const CLASSIFICATION_LABELS = { unspsc: 'UNSPSC', psc_fsc: 'PSC / FSC', naics_code: 'NAICS' }
const BARCODE_LABELS = { gtin: 'GTIN', upc: 'UPC', ean: 'EAN' }

const groups = computed(() => {
  const specs = props.product.specs ?? {}
  const general = specs.general ?? {}
  const dimensions = specs.dimensions ?? {}
  const classification = props.product.classification ?? {}
  const identifiers = props.product.identifiers ?? {}

  return [
    {
      title: 'General',
      rows: Object.entries(GENERAL_LABELS)
        .filter(([key]) => general[key])
        .map(([key, label]) => ({ label, value: general[key] })),
    },
    {
      title: 'Dimensions',
      rows: DIMENSION_ROWS
        .filter(([key]) => dimensions[key])
        .map(([key, label, format]) => ({ label, value: format(dimensions[key]) })),
    },
    {
      title: 'Specifications',
      rows: (specs.attributes ?? []).map((attribute) => ({ label: attribute.name, value: attribute.value })),
    },
    {
      title: 'Classification',
      rows: [
        ...Object.entries(CLASSIFICATION_LABELS).filter(([key]) => classification[key]).map(([key, label]) => ({ label, value: classification[key] })),
        ...Object.entries(BARCODE_LABELS).filter(([key]) => identifiers[key]).map(([key, label]) => ({ label, value: identifiers[key] })),
      ],
    },
  ].filter((group) => group.rows.length)
})

const details = computed(() => props.product.specs?.details ?? null)
const documents = computed(() => props.product.documents ?? [])
const hasAnything = computed(() => groups.value.length || details.value || documents.value.length)
</script>

<template>
  <section v-if="hasAnything" aria-labelledby="specs-heading" class="space-y-6">
    <h2 id="specs-heading" class="st-display text-xl font-extrabold text-st-ink">Specifications</h2>

    <div v-if="groups.length" class="grid gap-6 md:grid-cols-2">
      <div v-for="group in groups" :key="group.title">
        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-st-muted">{{ group.title }}</h3>
        <dl class="divide-y divide-st-line rounded-lg border border-st-line bg-st-bg">
          <div v-for="row in group.rows" :key="row.label" class="grid grid-cols-2 gap-4 px-4 py-2.5 text-sm">
            <dt class="font-medium text-st-ink/85">{{ row.label }}</dt>
            <dd class="st-num break-words text-st-ink">{{ row.value }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div v-if="details">
      <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-st-muted">Additional specifications</h3>
      <p class="whitespace-pre-line text-sm leading-relaxed text-st-ink/85">{{ details }}</p>
    </div>

    <div v-if="documents.length">
      <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-st-muted">Documents</h3>
      <ul class="flex flex-wrap gap-2">
        <li v-for="document in documents" :key="document.type">
          <a
            :href="document.url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex min-h-11 items-center gap-2 rounded-md border border-st-line px-4 py-2 text-sm font-semibold text-st-ink hover:bg-st-sunk focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          >
            <svg class="h-4 w-4 text-st-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" /><path d="M14 3v5h5" />
            </svg>
            {{ document.label }}
            <span class="sr-only">(opens in a new tab)</span>
          </a>
        </li>
      </ul>
    </div>
  </section>
</template>
