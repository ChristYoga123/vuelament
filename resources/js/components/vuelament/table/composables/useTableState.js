import { ref, computed, watch, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

export function useTableState(props) {
  const page = usePage()
  const pageProps = computed(() => page.props)

  const panelPath = computed(() => pageProps.value.panel?.path || 'admin')
  const pageSlug = computed(
    () => pageProps.value.resource?.slug || pageProps.value.page?.slug || '',
  )

  const resolvedFilters = computed(
    () => props.filters || pageProps.value.filters || {},
  )
  const resolvedSchema = computed(
    () => props.schema || pageProps.value.tableSchema,
  )
  const resolvedData = computed(() => props.data || pageProps.value.data)

  // Detect paginated data
  const isPaginated = computed(
    () =>
      resolvedData.value &&
      typeof resolvedData.value === 'object' &&
      'data' in resolvedData.value &&
      'links' in resolvedData.value,
  )
  const rows = computed(() =>
    isPaginated.value ? resolvedData.value.data : resolvedData.value || [],
  )

  // ── Table config ─────────────────────────────────────
  const tableConfig = computed(() => {
    if (!resolvedSchema.value?.components?.length) return null
    return (
      resolvedSchema.value.components.find((c) => c.type === 'table') || null
    )
  })

  const _allColumns = computed(() => tableConfig.value?.columns || [])
  const actions = computed(() => tableConfig.value?.actions || [])
  const headerActions = computed(() => tableConfig.value?.headerActions || [])
  const bulkActions = computed(() => tableConfig.value?.bulkActions || [])
  const tableFilters = computed(() => tableConfig.value?.filters || [])
  const filtersLayout = computed(
    () => tableConfig.value?.filtersLayout || 'dropdown',
  )
  const hasSearchableColumns = computed(() =>
    _allColumns.value.some((c) => c.searchable),
  )
  const isSearchVisible = computed(
    () =>
      tableConfig.value?.searchable !== false && hasSearchableColumns.value,
  )
  const hasToggleableColumns = computed(() =>
    _allColumns.value.some((c) => c.toggleable),
  )

  // ── Column Toggling ──────────────────────────────────
  const hiddenColumnNames = ref([])

  onMounted(() => {
    if (hiddenColumnNames.value.length === 0) {
      hiddenColumnNames.value = _allColumns.value
        .filter((col) => col.hidden || (col.toggleable && col.hidden))
        .map((col) => col.name)
    }
  })

  const visibleColumns = computed(() =>
    _allColumns.value.filter(
      (c) => !hiddenColumnNames.value.includes(c.name),
    ),
  )

  const toggleColumn = (name) => {
    if (hiddenColumnNames.value.includes(name)) {
      hiddenColumnNames.value = hiddenColumnNames.value.filter(
        (n) => n !== name,
      )
    } else {
      hiddenColumnNames.value.push(name)
    }
  }

  // ── Search ───────────────────────────────────────────
  const search = ref(resolvedFilters.value.search || '')
  const isSearching = ref(false)
  let searchTimeout = null

  watch(search, (val) => {
    isSearching.value = true
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      navigateWithParams({ search: val || undefined })
    }, 500)
  })

  // ── Sort ─────────────────────────────────────────────
  const sortBy = (field) => {
    const currentSort = resolvedFilters.value.sort
    const currentDir = resolvedFilters.value.direction || 'desc'
    const newDir =
      currentSort === field && currentDir === 'asc' ? 'desc' : 'asc'
    navigateWithParams({
      sort: field,
      direction: newDir,
      preserveScroll: true,
    })
  }

  // ── Column Toggles (Interactive) ─────────────────────
  const togglingStates = ref(new Set())

  const updateToggleColumn = (row, colName, value) => {
    const stateKey = `${row.id}_${colName}`
    if (togglingStates.value.has(stateKey)) return

    togglingStates.value.add(stateKey)
    row[colName] = value ? 1 : 0

    router.patch(
      `/${panelPath.value}/${pageSlug.value}/${row.id}/update-column`,
      { column: colName, value: value ? 1 : 0 },
      {
        preserveScroll: true,
        preserveState: true,
        only: ['data'],
        onFinish: () => {
          togglingStates.value.delete(stateKey)
        },
        onError: () => {
          row[colName] = !value ? 1 : 0
          togglingStates.value.delete(stateKey)
        },
      },
    )
  }

  // ── Selection ────────────────────────────────────────
  const selectedIds = ref([])
  const allSelected = computed({
    get: () =>
      rows.value?.length > 0 &&
      selectedIds.value.length === rows.value.length,
    set: (val) => {
      selectedIds.value = val ? rows.value.map((r) => r.id) : []
    },
  })

  const toggleSelect = (id) => {
    const idx = selectedIds.value.indexOf(id)
    if (idx >= 0) selectedIds.value.splice(idx, 1)
    else selectedIds.value.push(id)
  }

  // ── Filters ──────────────────────────────────────────
  const filterValues = ref({})
  const filtersOpen = ref(false)

  onMounted(() => {
    tableFilters.value.forEach((f) => {
      filterValues.value[f.name] =
        resolvedFilters.value.filters?.[f.name] ?? f.default ?? ''
    })
  })

  const applyFilters = () => {
    const params = {
      search: search.value || undefined,
      sort: resolvedFilters.value.sort,
      direction: resolvedFilters.value.direction,
      per_page: resolvedFilters.value.per_page,
      filters: {},
    }
    tableFilters.value.forEach((f) => {
      const val = filterValues.value[f.name]
      if (val !== '' && val !== null && val !== undefined) {
        params.filters[f.name] = val
      }
    })
    if (Object.keys(params.filters).length === 0) delete params.filters
    router.get(`/${panelPath.value}/${pageSlug.value}`, params, {
      preserveState: true,
      preserveScroll: true,
    })
  }

  const resetFilters = () => {
    tableFilters.value.forEach((f) => {
      filterValues.value[f.name] = f.default ?? ''
    })
    applyFilters()
  }

  const hasActiveFilters = computed(() =>
    tableFilters.value.some((f) => {
      const val = filterValues.value[f.name]
      return (
        val !== '' &&
        val !== null &&
        val !== undefined &&
        val !== (f.default ?? '')
      )
    }),
  )

  // ── Confirm Dialog ───────────────────────────────────
  const confirmDialog = ref({
    isOpen: false,
    title: '',
    description: '',
    action: null,
    onConfirm: () => {},
  })

  const deleteRecord = (id, action = null) => {
    confirmDialog.value = {
      isOpen: true,
      title: action?.confirmationTitle || 'Delete Data',
      description:
        action?.confirmationMessage || 'Are you sure you want to delete this record?',
      action,
      onConfirm: () => {
        router.delete(`/${panelPath.value}/${pageSlug.value}/${id}`, {
          preserveScroll: true,
        })
      },
    }
  }

  const restoreRecord = (id, action = null) => {
    confirmDialog.value = {
      isOpen: true,
      title: action?.confirmationTitle || 'Restore Data',
      description:
        action?.confirmationMessage ||
        'Are you sure you want to restore this record?',
      action,
      onConfirm: () => {
        router.post(
          `/${panelPath.value}/${pageSlug.value}/${id}/restore`,
          {},
          { preserveScroll: true },
        )
      },
    }
  }

  const forceDeleteRecord = (id, action = null) => {
    confirmDialog.value = {
      isOpen: true,
      title: action?.confirmationTitle || 'Delete Permanently',
      description:
        action?.confirmationMessage ||
        'Are you sure you want to permanently delete this record? This action cannot be undone.',
      action,
      onConfirm: () => {
        router.delete(
          `/${panelPath.value}/${pageSlug.value}/${id}/force`,
          { preserveScroll: true },
        )
      },
    }
  }

  const executeBulkAction = (action) => {
    const count = selectedIds.value.length
    const title = action.confirmationTitle || action.label
    const description =
      action.confirmationMessage ||
      `Are you sure you want to perform this action on ${count} selected records?`

    if (action.requiresConfirmation) {
      confirmDialog.value = {
        isOpen: true,
        title,
        description,
        action,
        onConfirm: () => performBulkAction(action),
      }
    } else {
      performBulkAction(action)
    }
  }

  // ── Custom Actions ───────────────────────────────────
  const actionFormDialog = ref({
    isOpen: false,
    action: null,
    row: null,
    formData: {},
  })

  const isSubmittingCustomAction = ref(false)
  const actionFormErrors = ref({})

  const executeCustomAction = (action, row) => {
    if (
      (action.formSchema && action.formSchema.length > 0) ||
      (action.infolist && action.infolist.length > 0)
    ) {
      actionFormDialog.value = { isOpen: true, action, row, formData: {} }
    } else if (action.requiresConfirmation) {
      confirmDialog.value = {
        isOpen: true,
        title: action.confirmationTitle || action.label,
        description: action.confirmationMessage || 'Apakah Anda yakin?',
        onConfirm: () => performCustomAction(action, row, {}),
      }
    } else {
      performCustomAction(action, row, {})
    }
  }

  const submitActionForm = () => {
    const { action, row, formData } = actionFormDialog.value
    isSubmittingCustomAction.value = true
    actionFormErrors.value = {}
    router.post(
      `/${panelPath.value}/${pageSlug.value}/${row.id}/action`,
      { action: action.name, data: formData },
      {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
          isSubmittingCustomAction.value = false
          const mapped = {}
          for (const key in errors) {
            mapped[key.replace('data.', '')] = errors[key]
          }
          actionFormErrors.value = mapped
        },
        onSuccess: () => {
          isSubmittingCustomAction.value = false
          actionFormDialog.value.isOpen = false
          actionFormErrors.value = {}
        },
      },
    )
  }

  const performCustomAction = (action, row, data) => {
    router.post(
      `/${panelPath.value}/${pageSlug.value}/${row.id}/action`,
      { action: action.name, data },
      { preserveScroll: true },
    )
  }

  const performBulkAction = (action) => {
    const base = `/${panelPath.value}/${pageSlug.value}`

    if (action.type === 'DeleteBulkAction') {
      router.delete(`${base}/bulk-destroy`, {
        data: { ids: selectedIds.value },
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = [] },
      })
    } else if (action.type === 'RestoreBulkAction') {
      router.post(`${base}/bulk-restore`, { ids: selectedIds.value }, {
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

  // ── Navigation helpers ───────────────────────────────
  const navigateWithParams = (overrides) => {
    const params = {
      search: search.value || undefined,
      sort: resolvedFilters.value.sort,
      direction: resolvedFilters.value.direction,
      per_page: resolvedFilters.value.per_page,
      ...overrides,
    }
    Object.keys(params).forEach((k) => {
      if (params[k] === undefined || params[k] === '') delete params[k]
    })
    router.get(`/${panelPath.value}/${pageSlug.value}`, params, {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => { isSearching.value = false },
    })
  }

  const goToPage = (url) => {
    if (url)
      router.get(url, {}, { preserveState: true, preserveScroll: true })
  }

  const changePerPage = (val) => {
    navigateWithParams({
      per_page: val === 'all' ? 1000000 : val,
      page: 1,
    })
  }

  return {
    // Page/route
    panelPath, pageSlug,
    // Data
    resolvedFilters, resolvedData, isPaginated, rows,
    // Table config
    tableConfig, _allColumns, actions, headerActions, bulkActions,
    tableFilters, filtersLayout,
    hasSearchableColumns, isSearchVisible, hasToggleableColumns,
    // Column visibility
    hiddenColumnNames, visibleColumns, toggleColumn,
    // Search
    search, isSearching,
    // Sort
    sortBy,
    // Toggle columns
    togglingStates, updateToggleColumn,
    // Selection
    selectedIds, allSelected, toggleSelect,
    // Filters
    filterValues, filtersOpen, applyFilters, resetFilters, hasActiveFilters,
    // Actions
    deleteRecord, restoreRecord, forceDeleteRecord,
    executeBulkAction, executeCustomAction,
    // Custom action form
    actionFormDialog, isSubmittingCustomAction, actionFormErrors, submitActionForm,
    // Confirm dialog
    confirmDialog,
    // Navigation
    goToPage, changePerPage,
  }
}
