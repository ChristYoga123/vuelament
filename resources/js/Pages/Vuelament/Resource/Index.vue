<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import VTable from '@/components/vuelament/VTable.vue'

const props = defineProps({
  resource: Object,
  tableSchema: Object,
  data: Object,
  filters: Object,
})
</script>

<template>
  <DashboardLayout :title="tableSchema?.title || resource.label" :description="resource.description">
    <VTable />
  </DashboardLayout>
</template>
