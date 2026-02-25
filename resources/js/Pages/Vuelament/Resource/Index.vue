<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Table from '@/components/vuelament/Table.vue'

const props = defineProps({
  resource: Object,
  tableSchema: Object,
  data: Object,
  filters: Object,
})
</script>

<template>
  <DashboardLayout :title="tableSchema?.title || resource.label" :description="resource.description">
    <Table />
  </DashboardLayout>
</template>
