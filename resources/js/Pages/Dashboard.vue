<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import AccountSummaryPanel from '@/Components/Dashboard/AccountSummaryPanel.vue'
import DashboardEmptyState from '@/Components/Dashboard/DashboardEmptyState.vue'
import DashboardKpiGrid from '@/Components/Dashboard/DashboardKpiGrid.vue'
import DateRangeControl from '@/Components/Dashboard/DateRangeControl.vue'
import RecentOrdersTable from '@/Components/Dashboard/RecentOrdersTable.vue'
import SpendBarChart from '@/Components/Dashboard/SpendBarChart.vue'
import TopItemsTable from '@/Components/Dashboard/TopItemsTable.vue'

defineProps({
  filters: Object,
  account: Object,
  summary: Object,
  charts: Object,
  recent_orders: Array,
  top_items: Array,
  locations: Array,
})
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="mobile-page-gutter mx-auto max-w-7xl space-y-6 py-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Customer dashboard</p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Purchasing overview</h1>
            <p class="mt-1 text-base text-gray-600">
              {{ filters.start_date }} through {{ filters.end_date }}
            </p>
          </div>
          <div class="grid grid-cols-1 gap-2 xs:grid-cols-2 sm:flex">
            <Link :href="route('dashboard.reports')" class="inline-flex min-h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-3 text-base font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
              Build report
            </Link>
            <Link :href="route('store.index')" class="inline-flex min-h-12 items-center justify-center rounded-md bg-gray-900 px-4 py-3 text-base font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
              Browse store
            </Link>
          </div>
        </div>

        <DateRangeControl :filters="filters" :locations="locations" />
        <DashboardKpiGrid :summary="summary" />

        <DashboardEmptyState v-if="summary.orders_count === 0" :account="account" />

        <div class="grid gap-6 xl:grid-cols-3">
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
    </div>
  </AuthenticatedLayout>
</template>
