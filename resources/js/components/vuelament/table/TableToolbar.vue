<script setup>
import { inject } from 'vue'
import { Search, Loader2, ListFilter, Columns3 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
} from '@/components/ui/dropdown-menu'

const {
  search, isSearching, isSearchVisible,
  tableFilters, filterValues, filtersLayout,
  hasActiveFilters, hasToggleableColumns,
  _allColumns, hiddenColumnNames,
  applyFilters, resetFilters, toggleColumn,
} = inject('tableState')
</script>

<template>
  <div
    v-if="isSearchVisible || tableFilters.length > 0 || hasToggleableColumns"
    class="flex items-center justify-between gap-3 px-4 py-3 border-b"
  >
    <div class="relative w-full max-w-sm items-center">
      <template v-if="isSearchVisible">
        <span class="absolute left-0 inset-y-0 flex items-center justify-center px-3 pointer-events-none">
          <Loader2 v-if="isSearching" class="w-4 h-4 text-muted-foreground animate-spin" />
          <Search v-else class="w-4 h-4 text-muted-foreground" />
        </span>
        <Input v-model="search" placeholder="Search..." class="pl-9 h-9" />
      </template>
    </div>

    <div class="flex items-center gap-2">
      <!-- Filters dropdown -->
      <DropdownMenu v-if="tableFilters.length > 0 && filtersLayout === 'dropdown'">
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline" size="sm" class="gap-2"
            :class="{ 'border-primary': hasActiveFilters }"
          >
            <ListFilter class="w-4 h-4" />
            <span class="hidden sm:inline">Filter</span>
            <span
              v-if="hasActiveFilters"
              class="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground text-[10px] font-bold"
            >
              {{ tableFilters.filter(f => {
                const v = filterValues[f.name]
                return v !== '' && v !== null && v !== undefined && v !== (f.default ?? '')
              }).length }}
            </span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-64 p-3 space-y-3">
          <template v-for="filter in tableFilters" :key="filter.name">
            <div>
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
            </div>
          </template>
          <Button
            v-if="hasActiveFilters" variant="ghost" size="sm"
            class="w-full" @click="resetFilters()"
          >
            Reset Filter
          </Button>
        </DropdownMenuContent>
      </DropdownMenu>

      <!-- Column toggle -->
      <DropdownMenu v-if="hasToggleableColumns">
        <DropdownMenuTrigger as-child>
          <Button variant="outline" size="sm" class="gap-2">
            <Columns3 class="w-4 h-4" />
            <span class="hidden sm:inline">Kolom</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-48 p-2 space-y-1">
          <template v-for="col in _allColumns.filter(c => c.toggleable)" :key="col.name">
            <label class="flex items-center gap-2 px-2 py-1.5 rounded-sm hover:bg-accent cursor-pointer">
              <input
                type="checkbox"
                :checked="!hiddenColumnNames.includes(col.name)"
                @change="toggleColumn(col.name)"
                class="size-4 rounded border-input accent-primary cursor-pointer"
              />
              <span class="text-sm select-none">{{ col.label }}</span>
            </label>
          </template>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </div>
</template>
