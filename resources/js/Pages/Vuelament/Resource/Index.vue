<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Plus, Search, ChevronDown, ChevronUp, Pencil, Trash2, ArchiveRestore, Columns3, Loader2, SlidersHorizontal, ListFilter, Circle, icons } from 'lucide-vue-next'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Separator } from '@/components/ui/separator'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuCheckboxItem,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'

const props = defineProps({
  resource: Object,
  tableSchema: Object,
  data: Object,
  filters: Object,
})

const page = usePage()
const panel = computed(() => page.props.panel || {})
const panelPath = computed(() => panel.value.path || 'admin')
const flash = computed(() => page.props.flash || {})

// Search
const search = ref(props.filters?.search || '')
const isSearching = ref(false)
let searchTimeout = null

watch(search, (val) => {
  isSearching.value = true
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(`/${panelPath.value}/${props.resource.slug}`, {
      search: val || undefined,
      sort: props.filters?.sort,
      direction: props.filters?.direction,
      per_page: props.filters?.per_page,
    }, { 
      preserveState: true, 
      preserveScroll: true,
      onFinish: () => { isSearching.value = false }
    })
  }, 500)
})

// Sort
const sortBy = (field) => {
  const currentSort = props.filters?.sort
  const currentDir = props.filters?.direction || 'desc'
  const newDir = currentSort === field && currentDir === 'asc' ? 'desc' : 'asc'

  router.get(`/${panelPath.value}/${props.resource.slug}`, {
    search: props.filters?.search,
    sort: field,
    direction: newDir,
    per_page: props.filters?.per_page,
  }, { preserveState: true, preserveScroll: true })
}

// Selection
const selectedIds = ref([])
const allSelected = computed({
  get: () => props.data?.data?.length > 0 && selectedIds.value.length === props.data.data.length,
  set: (val) => {
    selectedIds.value = val ? props.data.data.map(r => r.id) : []
  }
})

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx >= 0) selectedIds.value.splice(idx, 1)
  else selectedIds.value.push(id)
}

// Extract columns from tableSchema
const _allColumns = computed(() => {
  if (!props.tableSchema?.components) return []
  const table = props.tableSchema.components.find(c => c.type === 'table')
  return table?.columns || []
})

// Column Toggling Logic
const hiddenColumnNames = ref([])

onMounted(() => {
  if (hiddenColumnNames.value.length === 0) {
    hiddenColumnNames.value = _allColumns.value
      .filter(col => col.hidden || (col.toggleable && col.hidden))
      .map(col => col.name)
  }
})

const visibleColumns = computed(() => {
  return _allColumns.value.filter(c => !hiddenColumnNames.value.includes(c.name))
})

const toggleColumn = (name) => {
  if (hiddenColumnNames.value.includes(name)) {
    hiddenColumnNames.value = hiddenColumnNames.value.filter(n => n !== name)
  } else {
    hiddenColumnNames.value.push(name)
  }
}

const tableConfig = computed(() => {
  if (!props.tableSchema?.components) return null
  return props.tableSchema.components.find(c => c.type === 'table')
})

const actions = computed(() => tableConfig.value?.actions || [])
const headerActions = computed(() => tableConfig.value?.headerActions || [])
const bulkActions = computed(() => tableConfig.value?.bulkActions || [])
const tableFilters = computed(() => tableConfig.value?.filters || [])
const filtersLayout = computed(() => tableConfig.value?.filtersLayout || 'dropdown')

// Filter values
const filterValues = ref({})
const filtersOpen = ref(false)

onMounted(() => {
  // init filter defaults
  tableFilters.value.forEach(f => {
    filterValues.value[f.name] = props.filters?.[f.name] ?? f.default ?? ''
  })
})

const applyFilters = () => {
  const params = {
    search: search.value || undefined,
    sort: props.filters?.sort,
    direction: props.filters?.direction,
    per_page: props.filters?.per_page,
  }
  tableFilters.value.forEach(f => {
    const val = filterValues.value[f.name]
    if (val !== '' && val !== null && val !== undefined) {
      params[f.name] = val
    }
  })
  router.get(`/${panelPath.value}/${props.resource.slug}`, params, {
    preserveState: true,
    preserveScroll: true,
  })
}

const resetFilters = () => {
  tableFilters.value.forEach(f => {
    filterValues.value[f.name] = f.default ?? ''
  })
  applyFilters()
}

const hasActiveFilters = computed(() => {
  return tableFilters.value.some(f => {
    const val = filterValues.value[f.name]
    return val !== '' && val !== null && val !== undefined && val !== (f.default ?? '')
  })
})

// Resolve Lucide icon component dari string name
const resolveIcon = (name) => {
  if (!name) return Circle
  const pascalCase = name.replace(/(^|-)([a-z])/g, (_, __, c) => c.toUpperCase())
  return icons[pascalCase] || Circle
}

// Actions Confirm Dialog
const confirmDialog = ref({
  isOpen: false,
  title: '',
  description: '',
  onConfirm: () => {},
})

const deleteRecord = (id) => {
  confirmDialog.value = {
    isOpen: true,
    title: 'Hapus Data',
    description: 'Yakin ingin menghapus data ini?',
    onConfirm: () => {
      router.delete(`/${panelPath.value}/${props.resource.slug}/${id}`, {
        preserveScroll: true,
      })
    }
  }
}

const restoreRecord = (id) => {
  confirmDialog.value = {
    isOpen: true,
    title: 'Restore Data',
    description: 'Yakin ingin mengembalikan data ini?',
    onConfirm: () => {
      router.post(`/${panelPath.value}/${props.resource.slug}/${id}/restore`, {}, {
        preserveScroll: true,
      })
    }
  }
}

const forceDeleteRecord = (id) => {
  confirmDialog.value = {
    isOpen: true,
    title: 'Hapus Permanen',
    description: 'Yakin ingin menghapus data ini secara permanen? Data tidak dapat dikembalikan.',
    onConfirm: () => {
      router.delete(`/${panelPath.value}/${props.resource.slug}/${id}/force`, {
        preserveScroll: true,
      })
    }
  }
}

const executeBulkAction = (action) => {
  const count = selectedIds.value.length
  const title = action.confirmationTitle || action.label
  const description = action.confirmationMessage || `Yakin ingin melakukan aksi pada ${count} data yang dipilih?`

  if (action.requiresConfirmation) {
    confirmDialog.value = {
      isOpen: true,
      title,
      description,
      onConfirm: () => performBulkAction(action),
    }
  } else {
    performBulkAction(action)
  }
}

const performBulkAction = (action) => {
  const slug = props.resource.slug
  const base = `/${panelPath.value}/${slug}`

  if (action.type === 'DeleteBulkAction') {
    router.delete(`${base}/bulk-destroy`, {
      data: { ids: selectedIds.value },
      preserveScroll: true,
      onSuccess: () => { selectedIds.value = [] },
    })
  } else if (action.type === 'RestoreBulkAction') {
    router.post(`${base}/bulk-restore`, {
      ids: selectedIds.value,
    }, {
      preserveScroll: true,
      onSuccess: () => { selectedIds.value = [] },
    })
  } else if (action.type === 'ForceDeleteBulkAction') {
    router.delete(`${base}/bulk-force-delete`, {
      data: { ids: selectedIds.value },
      preserveScroll: true,
      onSuccess: () => { selectedIds.value = [] },
    })
  }
}

// Format cell value
const formatCell = (row, col) => {
  let val = row[col.name]
  if (val === null || val === undefined) return '—'
  if (col.dateFormat && val) {
    try {
      const d = new Date(val)
      return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' })
    } catch { return val }
  }
  if (col.badge) {
    return val ? 'Ya' : 'Tidak'
  }
  return String(val)
}

// Pagination
const goToPage = (url) => {
  if (url) router.get(url, {}, { preserveState: true, preserveScroll: true })
}
</script>

<template>
  <DashboardLayout :title="resource.label">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ tableSchema?.title || resource.label }}</h1>
        <p v-if="resource.description" class="text-sm text-muted-foreground mt-1">
          {{ resource.description }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Bulk actions (grouped) -->
        <template v-if="selectedIds.length > 0">
          <template v-for="(group, gi) in bulkActions" :key="gi">
            <!-- ActionGroup → dropdown -->
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
                    @click="executeBulkAction(action)"
                    :class="{
                      'text-destructive focus:text-destructive': action.color === 'danger',
                      'text-orange-600 focus:text-orange-600': action.color === 'warning',
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

            <!-- Single action (non-grouped) -->
            <Button
              v-else
              :variant="group.color === 'danger' ? 'destructive' : 'outline'"
              size="sm"
              @click="executeBulkAction(group)"
              class="gap-2"
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
            :href="`/${panelPath}/${resource.slug}/create`"
          >
            <Button size="sm" class="gap-1.5">
              <Plus class="w-4 h-4" />
              Tambah {{ resource.label }}
            </Button>
          </Link>
        </template>
      </div>
    </div>

    <!-- Search and Columns Toggle -->
    <Card class="py-0 gap-0">
      <div class="flex items-center justify-between gap-3 px-4 py-3 border-b">
        <div class="relative w-full max-w-sm items-center">
          <span class="absolute left-0 inset-y-0 flex items-center justify-center px-3 pointer-events-none">
            <Loader2 v-if="isSearching" class="w-4 h-4 text-muted-foreground animate-spin" />
            <Search v-else class="w-4 h-4 text-muted-foreground" />
          </span>
          <Input
            v-model="search"
            placeholder="Cari..."
            class="pl-9 h-9"
          />
        </div>
        
        <div class="flex items-center gap-2">
          <!-- Filters dropdown (layout=dropdown) -->
          <DropdownMenu v-if="tableFilters.length > 0 && filtersLayout === 'dropdown'">
            <DropdownMenuTrigger as-child>
              <Button variant="outline" size="sm" class="gap-2" :class="{ 'border-primary': hasActiveFilters }">
                <ListFilter class="w-4 h-4" />
                <span class="hidden sm:inline">Filter</span>
                <span v-if="hasActiveFilters" class="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground text-[10px] font-bold">
                  {{ tableFilters.filter(f => { const v = filterValues[f.name]; return v !== '' && v !== null && v !== undefined && v !== (f.default ?? '') }).length }}
                </span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-64 p-3 space-y-3">
              <template v-for="filter in tableFilters" :key="filter.name">
                <div>
                  <label class="text-xs font-medium text-muted-foreground mb-1.5 block">{{ filter.label }}</label>
                  <select
                    v-if="filter.type === 'SelectFilter'"
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
              <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="w-full" @click="resetFilters()">
                Reset Filter
              </Button>
            </DropdownMenuContent>
          </DropdownMenu>

          <!-- Column toggle -->
          <DropdownMenu>
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

      <!-- Filters above content -->
      <div v-if="tableFilters.length > 0 && (filtersLayout === 'aboveContent' || filtersLayout === 'aboveContentCollapsible')" class="border-b">
        <!-- Collapsible toggle -->
        <button
          v-if="filtersLayout === 'aboveContentCollapsible'"
          @click="filtersOpen = !filtersOpen"
          class="flex items-center justify-between w-full px-4 py-2.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted/30 transition-colors"
        >
          <span class="flex items-center gap-2">
            <SlidersHorizontal class="w-4 h-4" />
            Filter
            <span v-if="hasActiveFilters" class="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-primary-foreground text-[10px] font-bold">
              {{ tableFilters.filter(f => { const v = filterValues[f.name]; return v !== '' && v !== null && v !== undefined && v !== (f.default ?? '') }).length }}
            </span>
          </span>
          <ChevronUp class="w-4 h-4 transition-transform" :class="{ 'rotate-180': !filtersOpen }" />
        </button>

        <!-- Filter fields -->
        <div v-if="filtersLayout === 'aboveContent' || filtersOpen" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 px-4 py-3">
          <div v-for="filter in tableFilters" :key="filter.name">
            <label class="text-xs font-medium text-muted-foreground mb-1.5 block">{{ filter.label }}</label>
            <select
              v-if="filter.type === 'SelectFilter'"
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

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b bg-muted/40">
              <th class="w-10 px-4 py-3">
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
                    v-if="filters?.sort === col.name"
                    class="w-3.5 h-3.5"
                    :class="filters?.direction === 'desc' ? 'rotate-180' : ''"
                  />
                </button>
                <span v-else>{{ col.label }}</span>
              </th>
              <th v-if="actions.length" class="px-4 py-3 text-right font-medium text-muted-foreground">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in data?.data"
              :key="row.id"
              class="border-b hover:bg-muted/30 transition-colors"
              :class="{ 'bg-primary/5': selectedIds.includes(row.id) }"
            >
              <td class="px-4 py-3">
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
                <span v-if="col.badge" :class="[
                  'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                  row[col.name] ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
                ]">
                  {{ formatCell(row, col) }}
                </span>
                <span v-else>{{ formatCell(row, col) }}</span>
              </td>
              <td v-if="actions.length" class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <template v-for="action in actions" :key="action.name">
                    <Link
                      v-if="action.type === 'EditAction' || action.type === 'edit'"
                      :href="`/${panelPath}/${resource.slug}/${row.id}/edit`"
                    >
                      <Button variant="ghost" size="icon" class="h-8 w-8">
                        <Pencil class="w-3.5 h-3.5" />
                        <span class="sr-only">Edit</span>
                      </Button>
                    </Link>
                    <Button
                      v-if="action.type === 'DeleteAction' || action.type === 'delete'"
                      variant="ghost"
                      size="icon"
                      class="h-8 w-8 text-destructive hover:text-destructive"
                      @click="deleteRecord(row.id)"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                      <span class="sr-only">Hapus</span>
                    </Button>
                    <Button
                      v-if="action.type === 'RestoreAction' || action.type === 'restore'"
                      variant="ghost"
                      size="icon"
                      class="h-8 w-8 text-green-600 hover:text-green-600"
                      @click="restoreRecord(row.id)"
                    >
                      <ArchiveRestore class="w-3.5 h-3.5" />
                      <span class="sr-only">Restore</span>
                    </Button>
                    <Button
                      v-if="action.type === 'ForceDeleteAction' || action.type === 'force-delete'"
                      variant="ghost"
                      size="icon"
                      class="h-8 w-8 text-red-600 hover:text-red-600"
                      @click="forceDeleteRecord(row.id)"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                      <span class="sr-only">Hapus Permanen</span>
                    </Button>
                  </template>
                </div>
              </td>
            </tr>
            <tr v-if="!data?.data?.length">
              <td :colspan="visibleColumns.length + 2" class="px-4 py-12 text-center text-muted-foreground">
                <div class="flex flex-col items-center justify-center gap-1">
                  <component v-if="tableConfig?.emptyStateIcon" :is="resolveIcon(tableConfig.emptyStateIcon)" class="w-8 h-8 text-muted-foreground opacity-50 mb-2" />
                  <p v-if="tableConfig?.emptyStateHeading" class="text-base font-medium text-foreground">{{ tableConfig.emptyStateHeading }}</p>
                  <p class="text-sm">{{ tableConfig?.emptyStateDescription || 'Tidak ada data ditemukan.' }}</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="data?.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t">
        <p class="text-sm text-muted-foreground">
          Menampilkan {{ data.from }}-{{ data.to }} dari {{ data.total }}
        </p>
        <div class="flex items-center gap-1">
          <Button
            v-for="link in data.links"
            :key="link.label"
            variant="ghost"
            size="sm"
            :disabled="!link.url"
            :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
            @click="goToPage(link.url)"
            v-html="link.label"
          />
        </div>
      </div>
    </Card>

    <!-- Confirm Dialog -->
    <AlertDialog :open="confirmDialog.isOpen" @update:open="confirmDialog.isOpen = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ confirmDialog.title }}</AlertDialogTitle>
          <AlertDialogDescription>
            {{ confirmDialog.description }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="confirmDialog.isOpen = false">Batal</AlertDialogCancel>
          <AlertDialogAction @click="() => { confirmDialog.onConfirm(); confirmDialog.isOpen = false; }" :class="confirmDialog.title.includes('Hapus') ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90' : ''">
            Lanjutkan
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

  </DashboardLayout>
</template>
