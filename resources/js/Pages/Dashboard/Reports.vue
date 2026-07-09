<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import ReportBuilderForm from '@/Components/Dashboard/ReportBuilderForm.vue'
import ReportPreviewTable from '@/Components/Dashboard/ReportPreviewTable.vue'

defineProps({
  filters: Object,
  columns: Array,
  rows: Array,
  row_count: Number,
})
</script>

<template>
  <Head title="Purchasing Reports" />

  <AuthenticatedLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Reports</p>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">Purchasing report builder</h1>
            <p class="mt-1 text-sm text-gray-600">Preview and export spending by month, location, sublocation, product, or order.</p>
          </div>
          <Link :href="route('dashboard')" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Back to dashboard
          </Link>
        </div>

        <ReportBuilderForm :filters="filters" />
        <ReportPreviewTable :columns="columns" :rows="rows" :row-count="row_count" />
      </div>
    </div>
  </AuthenticatedLayout>
</template>
