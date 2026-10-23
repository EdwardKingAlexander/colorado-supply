<script setup>
import AccountLayout from '@/Layouts/AccountLayout.vue'
import { Head } from '@inertiajs/vue3'
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

  <AccountLayout
    title="Purchasing reports"
    description="Preview and export spending by month, location, sublocation, product or order."
  >
    <div class="space-y-6">
      <ReportBuilderForm :filters="filters" />
      <ReportPreviewTable :columns="columns" :rows="rows" :row-count="row_count" />
    </div>
  </AccountLayout>
</template>
