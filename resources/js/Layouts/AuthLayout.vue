<script setup>
import { onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  title: { type: String, default: 'Vuelament' },
})

onMounted(() => {
  const saved = localStorage.getItem('vuelament-theme')
  if (saved === 'dark') {
    document.documentElement.classList.add('dark')
  } else if (saved === 'light') {
    document.documentElement.classList.remove('dark')
  } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.classList.add('dark')
  }
})
</script>

<template>
  <Head :title="title" />
  <div class="min-h-screen flex items-center justify-center bg-background px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Logo / Brand -->
      <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-primary-foreground">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-foreground">Vuelament</h1>
        <p class="mt-1 text-sm text-muted-foreground">Admin Panel Framework</p>
      </div>

      <slot />
    </div>
  </div>
</template>
