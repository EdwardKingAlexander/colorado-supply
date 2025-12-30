import { reactive, computed, watch } from 'vue'

const STORAGE_KEY = 'colorado-supply.quote'

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
      .map((item) => ({
        id: item.id,
        name: item.name ?? 'Unnamed Item',
        price: Number(item.price) || 0,
        quantity: Number(item.quantity) > 0 ? Number(item.quantity) : 1,
        slug: item.slug ?? null,
        location_id: item.location_id ?? null, // Add location_id here
      }))
  } catch (error) {
    console.warn('Failed to parse quote storage', error)
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
    console.warn('Failed to persist quote storage', error)
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

export function useQuoteStore() {
  const addItem = (product, quantity = 1, location_id = null) => { // Add location_id parameter
    if (!product || typeof product.id === 'undefined') {
      return
    }

    const qty = Number(quantity) > 0 ? Number(quantity) : 1
    const normalizedPrice = Number(product.price) || 0

    const existing = state.items.find((item) => item.id === product.id)

    if (existing) {
      // If item exists, update quantity and ensure location_id is set if it was null
      existing.quantity += qty
      if (!existing.location_id && location_id) {
          existing.location_id = location_id;
      }
      return
    }

    state.items.push({
      id: product.id,
      productId: product.id ?? null,
      name: product.name ?? 'Unnamed Item',
      price: normalizedPrice,
      quantity: qty,
      slug: product.slug ?? null,
      location_id: location_id, // Store location_id here
    })
  }

  const removeItem = (id) => {
    const index = state.items.findIndex((item) => item.id === id)

    if (index === -1) {
      return
    }

    state.items.splice(index, 1)
  }

  const clearQuote = () => {
    if (!state.items.length) {
      return
    }

    state.items.splice(0, state.items.length)
  }

  const incrementQuantity = (id) => {
    const item = state.items.find((quoteItem) => quoteItem.id === id)

    if (!item) {
      return
    }

    item.quantity += 1
  }

  const decrementQuantity = (id) => {
    const item = state.items.find((quoteItem) => quoteItem.id === id)

    if (!item || item.quantity <= 1) {
      return
    }

    item.quantity -= 1
  }

  const updateItemLocation = (id, newLocationId) => {
    const item = state.items.find((quoteItem) => quoteItem.id === id)

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
    clearQuote,
    incrementQuantity,
    decrementQuantity,
    updateItemLocation,
  }
}
