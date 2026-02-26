<script setup>
import { ref, computed } from 'vue'
import { ArrowLeft, Loader2 } from 'lucide-vue-next'
import { router, usePage, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import FormRenderer from './FormRenderer.vue'

const props = defineProps({
  resource: Object,
  formSchema: Object,
})

const page = usePage()
const panel = computed(() => page.props.panel || {})
const panelPath = computed(() => panel.value.path || 'admin')

// Build form data from schema
const formData = ref({})
const errors = computed(() => page.props.errors || {})

const components = computed(() => props.formSchema?.components || [])

// Check if form has files
const hasFiles = () => {
  return Object.values(formData.value).some(v =>
    v instanceof File || (Array.isArray(v) && v.some(f => f instanceof File))
  )
}

// Submit
const submitting = ref(false)
const submit = () => {
  submitting.value = true
  router.post(`/${panelPath.value}/${props.resource.slug}`, formData.value, {
    forceFormData: hasFiles(),
    onFinish: () => { submitting.value = false },
  })
}

const getInputType = (comp) => {
  if (comp.inputType) return comp.inputType
  if (comp.type === 'date-picker') return 'date'
  return 'text'
}
</script>

<template>
  <DashboardLayout :title="`Create ${resource.label}`">
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="`/${panelPath}/${resource.slug}`">
          <Button variant="ghost" size="icon" class="h-8 w-8">
            <ArrowLeft class="w-4 h-4" />
          </Button>
        </Link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ formSchema?.title || `Create ${resource.label}` }}</h1>
        </div>
      </div>
    </template>

    <Card class="w-full py-4 gap-0">
      <CardContent>
        <form @submit.prevent="submit" class="space-y-4">
          <FormRenderer :components="components" :formData="formData" :errors="errors" />

          <div class="flex items-center gap-2 pt-2">
            <Button type="submit" :disabled="submitting" class="gap-2">
              <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
              {{ submitting ? 'Saving...' : 'Save' }}
            </Button>
            <Link :href="`/${panelPath}/${resource.slug}`">
              <Button variant="outline" type="button">Cancel</Button>
            </Link>
          </div>
        </form>
      </CardContent>
    </Card>
  </DashboardLayout>
</template>
