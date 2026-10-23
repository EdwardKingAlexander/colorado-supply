<script setup>
import AccountLayout from '@/Layouts/AccountLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AccountSummaryPanel from '@/Components/Dashboard/AccountSummaryPanel.vue'
import DashboardEmptyState from '@/Components/Dashboard/DashboardEmptyState.vue'
import DashboardKpiGrid from '@/Components/Dashboard/DashboardKpiGrid.vue'
import DateRangeControl from '@/Components/Dashboard/DateRangeControl.vue'
import RecentOrdersTable from '@/Components/Dashboard/RecentOrdersTable.vue'
import SpendBarChart from '@/Components/Dashboard/SpendBarChart.vue'
import TopItemsTable from '@/Components/Dashboard/TopItemsTable.vue'

const props = defineProps({
  account: Object,
  filters: Object,
  locations: Array,
  summary: Object,
  charts: Object,
  recent_orders: Array,
  top_items: Array,
})

const dateFormat = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' })
const formatDate = (value) => {
  const date = value ? new Date(`${value}T00:00:00Z`) : null
  return date && !Number.isNaN(date.getTime()) ? dateFormat.format(date) : value
}

const scope = computed(() => {
  const location = props.locations?.find((item) => String(item.id) === String(props.filters?.location_id ?? ''))
  return location ? location.name : 'all locations'
})
</script>

<template>
  <Head title="Dashboard" />

  <AccountLayout title="Purchasing overview">
    <template #description>
      {{ formatDate(filters.start_date) }} to {{ formatDate(filters.end_date) }}, {{ scope }}
    </template>
    <template #actions>
      <Link :href="route('dashboard.reports')" class="st-btn-secondary">Build report</Link>
      <Link :href="route('store.index')" class="st-btn-primary">Browse store</Link>
    </template>

    <div class="space-y-6">
      <DateRangeControl :filters="filters" :locations="locations" />
      <DashboardKpiGrid :summary="summary" />

      <!-- No orders in range: one empty state instead of four empty charts and tables. -->
      <div v-if="summary.orders_count === 0" class="grid gap-6 xl:grid-cols-3">
        <DashboardEmptyState :account="account" class="xl:col-span-2" />
        <AccountSummaryPanel :account="account" />
      </div>

      <div v-else class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
          <SpendBarChart title="Spend over time" :rows="charts.spend_over_time" />
          <SpendBarChart title="Spend by location" :rows="charts.spend_by_location" />
          <RecentOrdersTable :orders="recent_orders" />
        </div>
        <div class="space-y-6">
          <AccountSummaryPanel :account="account" />
          <SpendBarChart title="Payment status" :rows="charts.payment_status_breakdown.map(row => ({ label: row.label, total: row.total }))" />
          <TopItemsTable :items="top_items" />
        </div>
      </div>
    </div>
  </AccountLayout>
</template>
