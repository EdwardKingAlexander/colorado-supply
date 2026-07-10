<script setup>
defineProps({
  orders: {
    type: Array,
    default: () => [],
  },
})

const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })
</script>

<template>
  <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-5 py-4">
      <h2 class="text-base font-semibold text-gray-900">Recent orders</h2>
    </div>
    <div>
      <table class="responsive-data-table min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
          <tr>
            <th class="px-5 py-3">Order</th>
            <th class="px-5 py-3">Date</th>
            <th class="px-5 py-3">Status</th>
            <th class="px-5 py-3">Payment</th>
            <th class="px-5 py-3 text-right">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="order in orders" :key="order.id">
            <td data-label="Order" class="whitespace-nowrap px-5 py-3 font-medium text-gray-900">{{ order.order_number }}</td>
            <td data-label="Date" class="whitespace-nowrap px-5 py-3 text-gray-600">{{ order.created_at }}</td>
            <td data-label="Status" class="whitespace-nowrap px-5 py-3 text-gray-600">{{ order.status_label }}</td>
            <td data-label="Payment" class="whitespace-nowrap px-5 py-3 text-gray-600">{{ order.payment_status_label }}</td>
            <td data-label="Total" class="whitespace-nowrap px-5 py-3 text-right font-medium text-gray-900">{{ currency.format(order.grand_total) }}</td>
          </tr>
          <tr v-if="orders.length === 0">
            <td colspan="5" class="px-5 py-8 text-center text-gray-500">No orders in this account yet.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
