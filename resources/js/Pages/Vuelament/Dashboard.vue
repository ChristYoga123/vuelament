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

const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const panel = computed(() => page.props.panel || {})
const stats = computed(() => page.props.stats || [])

const defaultStats = [
  {
    title: 'Total Users',
    value: '0',
    description: 'Pengguna terdaftar',
    icon: 'users',
  },
]

const displayStats = computed(() => stats.value.length ? stats.value : defaultStats)
</script>

<template>
  <DashboardLayout title="Dashboard">
    <!-- Welcome -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight">
        Halo, {{ user.name || 'Admin' }}! 👋
      </h1>
      <p class="text-muted-foreground mt-1">
        Selamat datang di panel {{ panel.brandName || 'Vuelament' }}.
      </p>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
      <Card v-for="(stat, index) in displayStats" :key="index" class="py-4 gap-2">
        <CardHeader class="flex flex-row items-center justify-between px-4 pb-0">
          <CardTitle class="text-sm font-medium text-muted-foreground">
            {{ stat.title }}
          </CardTitle>
          <div class="h-8 w-8 rounded-lg bg-muted flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
        </CardHeader>
        <CardContent class="px-4 pb-0">
          <div class="text-2xl font-bold">{{ stat.value }}</div>
          <p v-if="stat.description" class="text-xs text-muted-foreground mt-1">
            {{ stat.description }}
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Quick Info -->
    <div class="grid gap-4 md:grid-cols-2">
      <Card class="py-4 gap-2">
        <CardHeader class="px-4 pb-2">
          <CardTitle class="text-lg">Mulai Cepat</CardTitle>
          <CardDescription class="text-xs">Pelajari cara menggunakan panel admin</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3 px-4 pb-0">
          <div class="flex items-start gap-3">
            <div class="mt-0.5 h-6 w-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
              <span class="text-xs font-bold text-primary">1</span>
            </div>
            <div>
              <p class="text-sm font-medium">Create Resource</p>
              <p class="text-xs text-muted-foreground">
                <code class="rounded bg-muted px-1 py-0.5">php artisan vuelament:resource Product</code>
              </p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <div class="mt-0.5 h-6 w-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
              <span class="text-xs font-bold text-primary">2</span>
            </div>
            <div>
              <p class="text-sm font-medium">Daftarkan di Panel</p>
              <p class="text-xs text-muted-foreground">Tambahkan class resource ke VuelamentServiceProvider</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <div class="mt-0.5 h-6 w-6 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
              <span class="text-xs font-bold text-primary">3</span>
            </div>
            <div>
              <p class="text-sm font-medium">Selesai!</p>
              <p class="text-xs text-muted-foreground">CRUD otomatis tersedia di sidebar</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="py-4 gap-2">
        <CardHeader class="px-4 pb-2">
          <CardTitle class="text-lg">Sistem Info</CardTitle>
          <CardDescription class="text-xs">Informasi panel dan framework</CardDescription>
        </CardHeader>
        <CardContent class="px-4 pb-0">
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Framework</span>
              <span class="font-medium">Vuelament v1.0</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">PHP</span>
              <span class="font-medium">Laravel + Inertia</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Frontend</span>
              <span class="font-medium">Vue 3 + shadcn</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Panel ID</span>
              <span class="font-medium">{{ panel.id || 'admin' }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </DashboardLayout>
</template>
