<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

const props = defineProps({
  page: { type: Object, default: () => ({}) },
  record: { type: Object, default: null },
  reportStats: { type: Object, default: null },
})
</script>

<template>
  <DashboardLayout :title="page.title || 'Report Page'">
    <div class="grid gap-6 md:grid-cols-2">
      <!-- Tampilkan Record (Data Model Eloquent) -->
      <Card>
        <CardHeader>
          <CardTitle>Data Pengguna (Record)</CardTitle>
          <CardDescription>Otomatis diteruskan oleh Vuelament dari Resource jika ada {record}.</CardDescription>
        </CardHeader>
        <CardContent>
          <pre class="bg-muted p-4 rounded-lg text-sm overflow-auto">{{ record || 'Tidak ada record' }}</pre>
        </CardContent>
      </Card>

      <!-- Tampilkan Extra Data (Dari metod getData) -->
      <Card>
        <CardHeader>
          <CardTitle>Data Ekstra (getData())</CardTitle>
          <CardDescription>Diteruskan dari deklarasi metod public static function getData().</CardDescription>
        </CardHeader>
        <CardContent>
          <pre class="bg-muted p-4 rounded-lg text-sm overflow-auto">{{ reportStats || 'Tidak ada extra data' }}</pre>
        </CardContent>
      </Card>
    </div>
  </DashboardLayout>
</template>