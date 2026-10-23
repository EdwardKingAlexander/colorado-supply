<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  orders: {
    type: Array,
    default: () => [],
  },
})

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })
</script>

<template>
  <section class="st-panel">
    <div class="border-b border-st-line px-5 py-4">
      <h2 class="text-base font-semibold text-st-ink">Recent orders</h2>
    </div>
    <table class="responsive-data-table st-table min-w-full divide-y divide-st-line text-sm">
      <thead>
        <tr>
          <th scope="col">Order</th>
          <th scope="col">Date</th>
          <th scope="col">Status</th>
          <th scope="col">Payment</th>
          <th scope="col" class="!text-right">Total</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-st-line">
        <tr v-for="order in orders" :key="order.id">
          <td data-label="Order" class="whitespace-nowrap font-semibold text-st-ink">{{ order.order_number }}</td>
          <td data-label="Date" class="whitespace-nowrap text-st-muted">{{ order.created_at }}</td>
          <td data-label="Status" class="whitespace-nowrap text-st-ink">{{ order.status_label }}</td>
          <td data-label="Payment" class="whitespace-nowrap text-st-ink">{{ order.payment_status_label }}</td>
          <td data-label="Total" class="st-num whitespace-nowrap text-right font-semibold text-st-ink">{{ currency.format(order.grand_total) }}</td>
        </tr>
        <tr v-if="orders.length === 0">
          <td colspan="5" class="!py-8 text-center text-st-muted">
            No orders yet.
            <Link :href="route('store.index')" class="font-semibold text-st-action hover:underline">Browse the store</Link>
            to place your first one.
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
