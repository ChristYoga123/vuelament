<script setup>
import { ref, computed } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Table from '@/components/vuelament/Table.vue'
import { Button } from '@/components/ui/button'
import { Loader2 } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog'
import FormRenderer from './FormRenderer.vue'

const props = defineProps({
  resource: Object,
  tableSchema: Object,
  formSchema: Object,
  data: Object,
  filters: Object,
})

const page = usePage()
const panel = computed(() => page.props.panel || {})
const panelPath = computed(() => panel.value.path || 'admin')

// Modal state
const isModalOpen = ref(false)
const modalMode = ref('create') // 'create' or 'edit'
const editId = ref(null)

// Form data
const formData = ref({})
const formErrors = ref({})
const components = computed(() => props.formSchema?.components || [])

// Check if form has files
const hasFiles = () => {
  return Object.values(formData.value).some(v =>
    v instanceof File || (Array.isArray(v) && v.some(f => f instanceof File))
  )
}

const resetForm = () => {
  formData.value = {}
  formErrors.value = {}
}

// Open Create Modal
const openCreateModal = () => {
  resetForm()
  modalMode.value = 'create'
  editId.value = null
  isModalOpen.value = true
}

// Global Edit Action handler mapping
const getRowActionUrl = (action, row) => {
    // Override the "edit" or custom action mapping locally if it's ManageRecords
    if (action.action === 'edit') {
        openEditModal(row)
        return null // return null to prevent default Link behavior from the Table.vue if modified there
    }
    return `/${panelPath.value}/${props.resource.slug}/${row.id}/${action.action}`
}

// Open Edit Modal requires getting data first from the Table row or specific API call
const openEditModal = (row) => {
  resetForm()
  // Assuming 'row' has the initial values, populate it
  // More robust approach is fetching from an endpoint, but for simplicity we map directly
  formData.value = { ...row } 
  modalMode.value = 'edit'
  editId.value = row.id
  isModalOpen.value = true
}

// Submit
const submitting = ref(false)
const submit = () => {
  if (submitting.value) return
  submitting.value = true
  formErrors.value = {}
  const route = modalMode.value === 'create' 
        ? `/${panelPath.value}/${props.resource.slug}`
        : `/${panelPath.value}/${props.resource.slug}/${editId.value}`

  const method = modalMode.value === 'create' ? 'post' : 'put'

  router[method](route, formData.value, {
    forceFormData: hasFiles(),
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
        isModalOpen.value = false
        resetForm()
    },
    onError: (errs) => {
        formErrors.value = { ...errs }
    },
    onFinish: () => { submitting.value = false },
  })
}

// Provide a way for Table to open Edit Modal directly
const provideEditModalFn = openEditModal

</script>

<template>
  <DashboardLayout :title="tableSchema?.title || `Manage ${resource.label}`" :description="resource.description">
    
    <!-- The Table Component. 
         We listen for createAction and editAction emitted by the Table when in Manage mode. -->
    <Table @createAction="openCreateModal" @editAction="openEditModal" />

    <!-- Create / Edit Modal -->
    <Dialog v-model:open="isModalOpen">
      <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ modalMode === 'create' ? `Create ${resource.label}` : `Edit ${resource.label}` }}</DialogTitle>
          <DialogDescription>
            {{ modalMode === 'create' ? 'Fill in the details below.' : 'Update the existing record details.' }}
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submit" class="space-y-4 py-4">
          <FormRenderer :components="components" :formData="formData" :errors="formErrors" />

          <DialogFooter class="pt-4">
            <Button type="button" variant="outline" @click="isModalOpen = false" :disabled="submitting">
              Cancel
            </Button>
            <Button type="submit" :disabled="submitting" class="gap-2">
              <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
              {{ submitting ? 'Saving...' : 'Save' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

  </DashboardLayout>
</template>
