<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import Modal from '@/Components/Modal.vue'
import { normalizeQuantity } from '@/Support/pricing'

// Product-page "Request a Quote": creates a draft Quote for sales to price
// (POST store.product.quote).
const props = defineProps({
  product: { type: Object, required: true },
  quantity: { type: Number, default: 1 },
})

const open = defineModel('open', { type: Boolean, default: false })

const minQuantity = () => props.product.minimum_order_quantity ?? 1
const form = ref({ quantity: props.quantity, note: '' })
const errors = ref({})
const submitting = ref(false)
const quoteNumber = ref(null)

// Each opening starts from the quantity chosen on the page.
watch(open, (isOpen) => {
  if (isOpen) {
    form.value = { quantity: normalizeQuantity(props.quantity, minQuantity()), note: '' }
    errors.value = {}
    quoteNumber.value = null
  }
})

const submit = async () => {
  submitting.value = true
  errors.value = {}

  try {
    const { data } = await axios.post(route('store.product.quote', props.product.id), {
      quantity: normalizeQuantity(form.value.quantity, minQuantity()),
      note: form.value.note || null,
    })
    quoteNumber.value = data.quote_number
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors ?? {}
    } else if (error.response?.status === 429) {
      errors.value = { form: ['Too many quote requests. Please wait a minute and try again.'] }
    } else {
      errors.value = { form: ['Something went wrong sending your request. Please try again.'] }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Modal :show="open" max-width="lg" @close="open = false">
    <div class="bg-st-panel p-5 text-st-ink sm:p-6">
      <template v-if="quoteNumber">
        <h2 class="st-display text-xl font-extrabold">Quote requested</h2>
        <p class="mt-3 text-sm text-st-ink/85" role="status">
          Your request <span class="st-num font-semibold">{{ quoteNumber }}</span> was sent to our sales team.
          We'll follow up with pricing and availability.
        </p>
        <div class="mt-6 flex justify-end">
          <button
            type="button"
            class="min-h-12 rounded-md bg-st-fill px-6 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2"
            @click="open = false"
          >
            Done
          </button>
        </div>
      </template>

      <form v-else novalidate @submit.prevent="submit">
        <h2 class="st-display text-xl font-extrabold">Request a quote</h2>
        <p class="mt-1 text-sm text-st-muted">{{ product.name }}<span v-if="product.sku"> · <span class="st-num">{{ product.sku }}</span></span></p>

        <div class="mt-5 space-y-4">
          <div>
            <label for="quote-quantity" class="block text-sm font-semibold">Quantity ({{ product.unit_of_measure ?? 'EA' }})</label>
            <input
              id="quote-quantity"
              v-model.number="form.quantity"
              type="number"
              :min="product.minimum_order_quantity ?? 1"
              inputmode="numeric"
              required
              :aria-invalid="Boolean(errors.quantity)"
              :aria-describedby="errors.quantity ? 'quote-quantity-error' : undefined"
              class="st-num mt-1 h-12 w-40 rounded-md border border-st-line bg-st-sunk px-3 text-base text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"
            />
            <p v-if="errors.quantity" id="quote-quantity-error" class="mt-1 text-sm text-red-400">{{ errors.quantity[0] }}</p>
          </div>

          <div>
            <label for="quote-note" class="block text-sm font-semibold">Notes <span class="font-normal text-st-muted">(optional)</span></label>
            <textarea
              id="quote-note"
              v-model="form.note"
              rows="3"
              maxlength="1000"
              placeholder="Delivery date, spec requirements, contract number…"
              class="mt-1 w-full rounded-md border border-st-line bg-st-sunk px-3 py-2 text-base text-st-ink focus:border-st-action focus:outline-none focus:ring-2 focus:ring-st-action/40"
            />
            <p v-if="errors.note" class="mt-1 text-sm text-red-400">{{ errors.note[0] }}</p>
          </div>

          <p v-if="errors.form" class="text-sm text-red-400" role="alert">{{ errors.form[0] }}</p>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="min-h-12 rounded-md border border-st-line px-6 py-3 text-base font-semibold text-st-ink hover:bg-st-sunk focus:outline-none focus:ring-2 focus:ring-st-action"
            @click="open = false"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="min-h-12 rounded-md bg-st-fill px-6 py-3 text-base font-semibold text-white hover:bg-st-fill/85 focus:outline-none focus:ring-2 focus:ring-st-action focus:ring-offset-2 disabled:opacity-60"
          >
            {{ submitting ? 'Sending…' : 'Send request' }}
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>
