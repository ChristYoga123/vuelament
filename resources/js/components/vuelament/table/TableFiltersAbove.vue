<script setup>
import { inject } from 'vue'
import { ChevronUp, SlidersHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const {
  tableFilters, filterValues, filtersLayout,
  filtersOpen, hasActiveFilters,
  applyFilters, resetFilters,
} = inject('tableState')
</script>

<template>
  <div
    v-if="tableFilters.length > 0 && (filtersLayout === 'aboveContent' || filtersLayout === 'aboveContentCollapsible')"
    class="border-b"
  >
    <button
      v-if="filtersLayout === 'aboveContentCollapsible'"
      @click="filtersOpen = !filtersOpen"
      class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted/30 transition-colors"
    >
      <span class="flex items-center gap-2">
        <SlidersHorizontal class="w-4 h-4" />
        Filter
        <span
          v-if="hasActiveFilters"
          class="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground text-[10px] font-bold"
        >
          {{ tableFilters.filter(f => {
            const v = filterValues[f.name]
            return v !== '' && v !== null && v !== undefined && v !== (f.default ?? '')
          }).length }}
        </span>
      </span>
      <ChevronUp class="w-4 h-4 transition-transform" :class="{ 'rotate-180': !filtersOpen }" />
    </button>

    <div
      v-if="filtersLayout === 'aboveContent' || filtersOpen"
      class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 px-4 py-3"
    >
      <div v-for="filter in tableFilters" :key="filter.name">
        <label class="text-xs font-medium text-muted-foreground mb-1.5 block">
          {{ filter.label }}
        </label>
        <select
          v-if="filter.type === 'SelectFilter' || filter.type === 'TrashFilter'"
          v-model="filterValues[filter.name]"
          @change="applyFilters()"
          class="w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm shadow-xs focus:border-ring focus:ring-ring/50 focus:ring-[3px] outline-none"
        >
          <option value="">{{ filter.placeholder || 'Semua' }}</option>
          <option v-for="opt in filter.options" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
        <Input
          v-else-if="filter.type === 'TextFilter'"
          v-model="filterValues[filter.name]"
          @change="applyFilters()"
          :placeholder="filter.placeholder || filter.label"
          class="h-8"
        />
      </div>
      <div v-if="hasActiveFilters" class="flex items-end">
        <Button variant="ghost" size="sm" @click="resetFilters()" class="text-muted-foreground">
          Reset
        </Button>
      </div>
    </div>
  </div>
</template>
