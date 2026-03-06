<script setup>
/**
 * Table — reusable table component (full-featured)
 *
 * Cukup panggil <Table /> di custom page Vue dan tabel langsung
 * ter-render lengkap dengan search, sort, pagination, filters,
 * column toggle, checkboxes, bulk actions, row actions, confirm dialog.
 *
 * Setara dengan {{ $this->table }} di Filament/Livewire.
 */
import { provide } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Plus, ChevronDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'

// Sub-components
import TableToolbar from './table/TableToolbar.vue'
import TableFiltersAbove from './table/TableFiltersAbove.vue'
import TablePagination from './table/TablePagination.vue'
import TableConfirmDialog from './table/TableConfirmDialog.vue'
import TableActionFormDialog from './table/TableActionFormDialog.vue'
import TableRowActions from './table/TableRowActions.vue'
import ColumnCell from './table/columns/ColumnCell.vue'

// Composable & utils
import { useTableState } from './table/composables/useTableState'
import { resolveIcon } from './table/utils'

const props = defineProps({
  schema: { type: Object, default: null },
  data: { type: [Object, Array], default: null },
  filters: { type: Object, default: null },
})

// Initialize all table state and provide to sub-components
const state = useTableState(props)
provide('tableState', state)

const {
  panelPath, pageSlug,
  resolvedFilters, resolvedData, isPaginated, rows,
  tableConfig, visibleColumns, actions, headerActions, bulkActions,
  selectedIds, allSelected, toggleSelect,
  togglingStates, updateToggleColumn,
  sortBy, executeBulkAction,
} = state
</script>

<template>
  <div v-if="tableConfig">
    <!-- Header Actions + Bulk Actions -->
    <div class="flex items-center justify-between mb-4">
      <div></div>
      <div class="flex items-center gap-2">
        <!-- Bulk actions -->
        <template v-if="selectedIds.length > 0">
          <template v-for="(group, gi) in bulkActions" :key="gi">
            <DropdownMenu v-if="group.type === 'ActionGroup'">
              <DropdownMenuTrigger as-child>
                <Button variant="outline" size="sm" class="gap-2">
                  <component :is="resolveIcon(group.icon)" class="w-4 h-4" />
                  {{ group.label }}
                  <ChevronDown class="w-3.5 h-3.5 opacity-50" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-48">
                <template v-for="(action, ai) in group.actions" :key="ai">
                  <DropdownMenuSeparator v-if="ai > 0" />
                  <DropdownMenuItem
                    v-if="action.url && action.shouldOpenInNewTab"
                    class="p-0 m-0"
                  >
                    <a
                      :href="action.url" target="_blank"
                      class="flex outline-none items-center gap-2 px-2 py-1.5 w-full cursor-pointer text-sm focus:bg-accent focus:text-accent-foreground rounded-sm"
                      :class="{
                        'text-destructive focus:text-destructive': action.color === 'danger',
                        'text-yellow-400 focus:text-yellow-400': action.color === 'warning',
                        'text-green-600 focus:text-green-600': action.color === 'success',
                      }"
                    >
                      <component :is="resolveIcon(action.icon)" class="w-4 h-4" />
                      {{ action.label }}
                    </a>
                  </DropdownMenuItem>
                  <DropdownMenuItem v-else-if="action.url" class="p-0 m-0">
                    <Link
                      :href="action.url"
                      class="flex outline-none items-center gap-2 px-2 py-1.5 w-full cursor-pointer text-sm focus:bg-accent focus:text-accent-foreground rounded-sm"
                      :class="{
                        'text-destructive focus:text-destructive': action.color === 'danger',
                        'text-yellow-400 focus:text-yellow-400': action.color === 'warning',
                        'text-green-600 focus:text-green-600': action.color === 'success',
                      }"
                    >
                      <component :is="resolveIcon(action.icon)" class="w-4 h-4" />
                      {{ action.label }}
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    v-else
                    @click="executeBulkAction(action)"
                    :class="{
                      'text-destructive focus:text-destructive': action.color === 'danger',
                      'text-yellow-400 focus:text-yellow-400': action.color === 'warning',
                      'text-green-600 focus:text-green-600': action.color === 'success',
                    }"
                    class="gap-2 cursor-pointer"
                  >
                    <component :is="resolveIcon(action.icon)" class="w-4 h-4" />
                    {{ action.label }}
                  </DropdownMenuItem>
                </template>
              </DropdownMenuContent>
            </DropdownMenu>

            <Button
              v-else
              :variant="group.color === 'danger' ? 'destructive' : 'outline'"
              size="sm" @click="executeBulkAction(group)" class="gap-2"
            >
              <component :is="resolveIcon(group.icon)" class="w-4 h-4" />
              {{ group.label }}
            </Button>
          </template>
        </template>
        <!-- Header actions -->
        <template v-for="action in headerActions" :key="action.name">
          <Link
            v-if="action.type === 'CreateAction' || action.type === 'create'"
            :href="`/${panelPath}/${pageSlug}/create`"
          >
            <Button size="sm" class="gap-1.5">
              <Plus class="w-4 h-4" />
              {{ action.label }}
            </Button>
          </Link>
        </template>
      </div>
    </div>

    <!-- Table Card -->
    <Card class="py-0 gap-0">
      <!-- Toolbar (search + filters dropdown + column toggle) -->
      <TableToolbar />

      <!-- Filters above content -->
      <TableFiltersAbove />

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b bg-muted/40">
              <th v-if="tableConfig?.selectable !== false" class="w-10 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="allSelected = $event.target.checked"
                  class="size-4 rounded border-input accent-primary cursor-pointer"
                />
              </th>
              <th
                v-for="col in visibleColumns"
                :key="col.name"
                class="px-4 py-3 text-left font-medium text-muted-foreground whitespace-nowrap"
              >
                <button
                  v-if="col.sortable"
                  @click="sortBy(col.name)"
                  class="flex items-center gap-1 hover:text-foreground transition-colors"
                >
                  {{ col.label }}
                  <ChevronDown
                    v-if="resolvedFilters?.sort === col.name"
                    class="w-3.5 h-3.5"
                    :class="resolvedFilters?.direction === 'desc' ? 'rotate-180' : ''"
                  />
                </button>
                <span v-else>{{ col.label }}</span>
              </th>
              <th
                v-if="actions.length"
                class="px-4 py-3 text-right font-medium text-muted-foreground"
              >
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in rows"
              :key="row.id"
              class="border-b hover:bg-muted/30 transition-colors"
              :class="{ 'bg-primary/5': selectedIds.includes(row.id) }"
            >
              <td v-if="tableConfig?.selectable !== false" class="px-4 py-3">
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(row.id)"
                  @change="toggleSelect(row.id)"
                  class="size-4 rounded border-input accent-primary cursor-pointer"
                />
              </td>
              <td
                v-for="col in visibleColumns"
                :key="col.name"
                class="px-4 py-3 whitespace-nowrap"
              >
                <ColumnCell
                  :row="row"
                  :col="col"
                  :is-toggling="togglingStates.has(`${row.id}_${col.name}`)"
                  @toggle="updateToggleColumn"
                />
              </td>
              <td v-if="actions.length" class="px-4 py-3 text-right">
                <TableRowActions :row="row" :actions="actions" />
              </td>
            </tr>
            <tr v-if="!rows?.length">
              <td
                :colspan="visibleColumns.length + (tableConfig?.selectable !== false ? 1 : 0) + (actions.length ? 1 : 0)"
                class="px-4 py-12 text-center text-muted-foreground"
              >
                <div class="flex flex-col items-center justify-center gap-1">
                  <component
                    v-if="tableConfig?.emptyStateIcon"
                    :is="resolveIcon(tableConfig.emptyStateIcon)"
                    class="w-8 h-8 text-muted-foreground opacity-50 mb-2"
                  />
                  <p v-if="tableConfig?.emptyStateHeading" class="text-base font-medium text-foreground">
                    {{ tableConfig.emptyStateHeading }}
                  </p>
                  <p class="text-sm">
                    {{ tableConfig?.emptyStateDescription || 'No data found.' }}
                  </p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <TablePagination />
    </Card>

    <!-- Dialogs -->
    <TableConfirmDialog />
    <TableActionFormDialog />
  </div>
</template>
