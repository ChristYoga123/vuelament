<script setup>
import { inject } from 'vue'
import { Button } from '@/components/ui/button'

const {
  resolvedFilters, resolvedData, isPaginated,
  tableConfig, goToPage, changePerPage,
} = inject('tableState')
</script>

<template>
  <div
    v-if="isPaginated && tableConfig?.paginated !== false"
    class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 border-t"
  >
    <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-start">
      <div class="flex items-center gap-2">
        <select
          :value="resolvedFilters?.per_page || tableConfig?.perPage || 10"
          @change="changePerPage($event.target.value)"
          class="h-8 rounded-md border border-input bg-background px-2 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
        >
          <option
            v-for="opt in tableConfig?.perPageOptions || [10, 25, 50, 'all']"
            :key="opt"
            :value="opt === 'all' ? 1000000 : opt"
          >
            {{ opt === 'all' || opt === 1000000 ? 'Semua' : opt }}
          </option>
        </select>
        <span class="text-sm text-muted-foreground">per halaman</span>
      </div>

      <p class="text-sm text-muted-foreground hidden sm:block">
        Menampilkan {{ resolvedData?.from || 0 }}-{{ resolvedData?.to || 0 }}
        dari {{ resolvedData?.total || 0 }}
      </p>
    </div>

    <div
      class="flex items-center gap-1 overflow-x-auto max-w-full pb-1 sm:pb-0"
      v-if="resolvedData?.last_page > 1"
    >
      <Button
        v-for="link in resolvedData.links"
        :key="link.label"
        variant="ghost"
        size="sm"
        class="h-8"
        :disabled="!link.url"
        :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
        @click="goToPage(link.url)"
        v-html="link.label"
      />
    </div>
  </div>
</template>
