// Client-side mirror of App\Services\Store\ProductPriceResolver, used only for
// display. Checkout always re-prices on the server.

/**
 * Unit price for a quantity: company price wins; otherwise the highest tier
 * the quantity reaches; otherwise the base price. null = needs a quote.
 *
 * @param {{ company_price?: number, base_price?: number, tiers?: Array<{min_quantity: number, unit_price: number}> }} pricing
 * @param {number} quantity
 */
export function unitPriceFor(pricing, quantity) {
    if (!pricing) {
        return null;
    }

    if (typeof pricing.company_price === 'number') {
        return pricing.company_price;
    }

    const tier = (pricing.tiers ?? [])
        .filter((candidate) => candidate.min_quantity <= quantity)
        .at(-1);

    if (tier) {
        return tier.unit_price;
    }

    return typeof pricing.base_price === 'number' ? pricing.base_price : null;
}

/**
 * Clamp a typed quantity to a whole number no lower than the minimum order.
 * There is no upper bound: orders above stock are backordered.
 */
export function normalizeQuantity(value, minimum = 1) {
    const parsed = Math.floor(Number(value));

    return Number.isFinite(parsed) && parsed >= minimum ? parsed : minimum;
}

export const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
