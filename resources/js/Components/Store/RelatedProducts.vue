<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { currency } from '@/Support/pricing'

// Substitutes, accessories and related products; each group only when it has
// items, the whole section only when any group does.
const props = defineProps({
  related: { type: Object, default: () => ({}) },
})

const GROUPS = [
  ['substitutes', 'Substitutes', 'Interchangeable alternatives'],
  ['accessories', 'Accessories', 'Often bought with this item'],
  ['related', 'Related products', null],
]

const groups = computed(() => GROUPS
  .map(([key, title, hint]) => ({ key, title, hint, items: props.related?.[key] ?? [] }))
  .filter((group) => group.items.length))

const priceLabel = (item) => (typeof item.pricing?.unit_price === 'number'
  ? `${currency.format(item.pricing.unit_price)} / ${item.unit_of_measure ?? 'EA'}`
  : 'Request a quote')
</script>

<template>
  <section v-if="groups.length" aria-labelledby="related-heading" class="space-y-6">
    <h2 id="related-heading" class="sr-only">Related products</h2>
    <div v-for="group in groups" :key="group.key">
      <div class="mb-3 flex items-baseline gap-3">
        <h3 class="st-display text-xl font-extrabold text-st-ink">{{ group.title }}</h3>
        <p v-if="group.hint" class="text-sm text-st-muted">{{ group.hint }}</p>
      </div>
      <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <li v-for="item in group.items" :key="item.id">
          <Link
            :href="item.url"
            class="flex h-full gap-3 rounded-lg border border-st-line bg-st-bg p-3 transition-colors hover:border-st-action focus:outline-none focus-visible:ring-2 focus-visible:ring-st-action"
          >
            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-md border border-st-line bg-white">
              <img v-if="item.image" :src="item.image" alt="" class="h-full w-full object-contain" loading="lazy" decoding="async" width="64" height="64" />
              <svg v-else class="h-full w-full p-3 text-st-muted/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
                <path stroke-linejoin="round" d="M12 2.75 20 7.25v9.5l-8 4.5-8-4.5v-9.5l8-4.5Z" />
              </svg>
            </div>
            <div class="min-w-0">
              <p class="line-clamp-2 text-sm font-semibold text-st-ink">{{ item.name }}</p>
              <p v-if="item.sku" class="st-num mt-0.5 truncate text-xs text-st-muted">{{ item.sku }}</p>
              <p class="st-num mt-1 text-sm text-st-ink/85">{{ priceLabel(item) }}</p>
            </div>
          </Link>
        </li>
      </ul>
    </div>
  </section>
</template>
