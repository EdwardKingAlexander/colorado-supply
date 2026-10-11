import { reactive, computed, watch } from 'vue'
import { normalizeQuantity, unitPriceFor } from '@/Support/pricing'

const STORAGE_KEY = 'colorado-supply.cart'

// Display pricing kept per line so the price follows quantity tiers as the
// quantity changes. Checkout re-prices every line on the server regardless.
const linePricing = (pricing) => {
  if (!pricing || typeof pricing !== 'object') {
    return null
  }

  return {
    company_price: typeof pricing.company_price === 'number' ? pricing.company_price : undefined,
    base_price: typeof pricing.base_price === 'number' ? pricing.base_price : undefined,
    tiers: Array.isArray(pricing.tiers) ? pricing.tiers : [],
  }
}

const normalizeItem = (item) => {
  const minQuantity = Math.max(1, Number(item.minQuantity) || 1)
  const quantity = normalizeQuantity(item.quantity, minQuantity)
  const pricing = linePricing(item.pricing)
  const pricedFromTiers = pricing ? unitPriceFor(pricing, quantity) : null

  return {
    id: item.id,
    productId: item.productId ?? item.id,
    name: item.name ?? 'Unnamed Item',
    sku: item.sku ?? null,
    slug: item.slug ?? null,
    url: item.url ?? null,
    unit: item.unit ?? null,
    minQuantity,
    pricing,
    price: pricedFromTiers ?? (Number(item.price) || 0),
    quantity,
    location_id: item.location_id ?? null,
  }
}

const loadInitialItems = () => {
  if (typeof window === 'undefined') {
    return []
  }

  try {
    const stored = window.localStorage.getItem(STORAGE_KEY)

    if (!stored) {
      return []
    }

    const parsed = JSON.parse(stored)

    if (!Array.isArray(parsed)) {
      return []
    }

    return parsed
      .filter((item) => item && typeof item.id !== 'undefined')
      .map(normalizeItem)
  } catch (error) {
    console.warn('Failed to parse cart storage', error)
    return []
  }
}

const state = reactive({
  items: loadInitialItems(),
})

const persistItems = (items) => {
  if (typeof window === 'undefined') {
    return
  }

  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
  } catch (error) {
    console.warn('Failed to persist cart storage', error)
  }
}

if (typeof window !== 'undefined') {
  watch(
    () => state.items,
    (items) => {
      persistItems(items)
    },
    { deep: true },
  )
}

const findItem = (id) => state.items.find((cartItem) => cartItem.id === id)

// Quantity changes re-derive the unit price when the line carries tier pricing.
// clamp=false is for live typing: the minimum order is enforced on commit.
const applyQuantity = (item, quantity, clamp = true) => {
  item.quantity = clamp ? normalizeQuantity(quantity, item.minQuantity ?? 1) : quantity

  if (item.pricing) {
    item.price = unitPriceFor(item.pricing, item.quantity) ?? item.price
  }
}

export function useCartStore() {
  /**
   * Add a product (catalog row or product page payload). Quantities above
   * stock are allowed: they are backordered.
   */
  const addItem = (product, quantity = 1, location_id = null) => {
    if (!product || typeof product.id === 'undefined') {
      return
    }

    const minQuantity = Math.max(1, Number(product.minimum_order_quantity) || 1)
    const existing = findItem(product.id)

    if (existing) {
      applyQuantity(existing, existing.quantity + normalizeQuantity(quantity, 1))

      if (!existing.location_id && location_id) {
        existing.location_id = location_id
      }

      return
    }

    state.items.push(normalizeItem({
      id: product.id,
      productId: product.id,
      name: product.name,
      sku: product.sku,
      slug: product.slug,
      url: product.url,
      unit: product.unit_of_measure ?? product.unit,
      minQuantity,
      pricing: product.pricing,
      price: product.price,
      quantity: normalizeQuantity(quantity, minQuantity),
      location_id,
    }))
  }

  const removeItem = (id) => {
    const index = state.items.findIndex((item) => item.id === id)

    if (index === -1) {
      return
    }

    state.items.splice(index, 1)
  }

  const clearCart = () => {
    if (!state.items.length) {
      return
    }

    state.items.splice(0, state.items.length)
  }

  const setQuantity = (id, quantity) => {
    const item = findItem(id)

    if (item) {
      applyQuantity(item, quantity)
    }
  }

  // While the shopper types: any whole number ≥ 1 updates prices instantly.
  const previewQuantity = (id, value) => {
    const item = findItem(id)
    const typed = Math.floor(Number(value))

    if (item && value !== '' && Number.isFinite(typed) && typed >= 1) {
      applyQuantity(item, typed, false)
    }
  }

  const incrementQuantity = (id) => {
    const item = findItem(id)

    if (item) {
      applyQuantity(item, item.quantity + 1)
    }
  }

  const decrementQuantity = (id) => {
    const item = findItem(id)

    if (item && item.quantity > (item.minQuantity ?? 1)) {
      applyQuantity(item, item.quantity - 1)
    }
  }

  const updateItemLocation = (id, newLocationId) => {
    const item = findItem(id)

    if (!item) {
      return
    }

    item.location_id = newLocationId
  }

  const itemCount = computed(() => state.items.reduce((total, item) => total + item.quantity, 0))

  const total = computed(() => state.items.reduce((sum, item) => sum + item.price * item.quantity, 0))

  return {
    items: state.items,
    itemCount,
    total,
    totalCost: total,
    addItem,
    removeItem,
    clearCart,
    setQuantity,
    previewQuantity,
    incrementQuantity,
    decrementQuantity,
    updateItemLocation,
  }
}
