<script setup>
import { ref, computed, toRef, onMounted } from 'vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { Checkbox } from '@/components/ui/checkbox'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Eye, EyeOff, Upload, X, FileIcon, ImageIcon, GripVertical, Loader2 } from 'lucide-vue-next'
import RichEditor from '@/components/vuelament/form/RichEditor.vue'
import VueSelect from '@/components/vuelament/form/VueSelect.vue'
import DatePicker from '@/components/vuelament/form/DatePicker.vue'
import { useFormReactivity } from '@/components/vuelament/form/composables/useFormReactivity'

const props = defineProps({
  components: {
    type: Array,
    required: true,
  },
  formData: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  isNested: {
    type: Boolean,
    default: false,
  }
})

// ── Client-side reactivity (no server requests!) ────
const { isVisible, isDisabled, isRequired, loadingFields } = useFormReactivity(toRef(props, 'formData'))

const TransparentWrapper = (props, { slots }) => slots.default ? slots.default() : null

const applyAutoLayout = computed(() => {
  if (props.isNested) return false
  return !props.components.some(c => ['Grid', 'Section'].includes(c.type))
})

const getAutoColSpan = (comp) => {
  if (comp.columnSpan === 'full' || ['Textarea', 'textarea', 'RichEditor', 'FileInput', 'file-input'].includes(comp.type)) {
    return 'md:col-span-2'
  }
  return 'col-span-1'
}

const emit = defineEmits(['update:formData'])

const revealedInputs = ref({})
const toggleReveal = (name) => {
  revealedInputs.value[name] = !revealedInputs.value[name]
}

const isTruthy = (val) => {
  return val === true || val === 1 || val === '1' || val === 'true'
}

// File input state
const filePreviews = ref({})
const fileProgress = ref({})
const dragOver = ref({})

// Reorder state
const reorderDragIndex = ref(null)
const reorderDragOverIndex = ref(null)
const reorderFieldName = ref(null)

const toggleCheckbox = (name, val, isChecked) => {
  let current = props.formData[name] || []
  if (!Array.isArray(current)) current = []
  if (isChecked) {
    if (!current.includes(val)) current.push(val)
  } else {
    current = current.filter(v => v !== val)
  }
  props.formData[name] = current
}

const getInputType = (comp) => {
  if (comp.revealable && revealedInputs.value[comp.name]) {
    return 'text'
  }
  if (comp.inputType) return comp.inputType
  return 'text'
}

const getGridClass = (cols) => {
  if (cols === 1) return 'grid grid-cols-1 gap-4'
  if (cols === 2) return 'grid grid-cols-1 md:grid-cols-2 gap-4'
  if (cols === 3) return 'grid grid-cols-1 md:grid-cols-3 gap-4'
  if (cols === 4) return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4'
  return 'grid grid-cols-1 gap-4'
}

// File handling
const handleFileSelect = (event, comp) => {
  const files = event.target.files
  if (!files?.length) return
  processFiles(files, comp)
}

const handleFileDrop = (event, comp) => {
  event.preventDefault()
  dragOver.value[comp.name] = false
  const files = event.dataTransfer.files
  if (!files?.length) return
  processFiles(files, comp)
}

const processFiles = (files, comp) => {
  const fileList = Array.from(files)

  if (comp.multiple) {
    const existing = props.formData[comp.name] || []
    props.formData[comp.name] = [...existing, ...fileList]
  } else {
    props.formData[comp.name] = fileList[0]
  }

  // Generate previews
  fileList.forEach(file => {
    if (file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (e) => {
        if (comp.multiple) {
          if (!filePreviews.value[comp.name]) filePreviews.value[comp.name] = {}
          filePreviews.value[comp.name][file.name] = e.target.result
        } else {
          filePreviews.value[comp.name] = e.target.result
        }
      }
      reader.onprogress = (e) => {
        if (e.lengthComputable) {
          fileProgress.value[comp.name] = Math.round((e.loaded / e.total) * 100)
        }
      }
      reader.onloadend = () => {
        setTimeout(() => { fileProgress.value[comp.name] = null }, 500)
      }
      reader.readAsDataURL(file)
    } else {
      // Simulate progress for non-image files
      fileProgress.value[comp.name] = 0
      let progress = 0
      const interval = setInterval(() => {
        progress += 20
        fileProgress.value[comp.name] = progress
        if (progress >= 100) {
          clearInterval(interval)
          setTimeout(() => { fileProgress.value[comp.name] = null }, 500)
        }
      }, 100)
    }
  })
}

const removeFile = (comp, index = null) => {
  if (comp.multiple && index !== null) {
    const files = [...(props.formData[comp.name] || [])]
    files.splice(index, 1)
    props.formData[comp.name] = files
    if (filePreviews.value[comp.name]) {
      delete filePreviews.value[comp.name]
    }
  } else {
    props.formData[comp.name] = null
    filePreviews.value[comp.name] = null
  }
}

const getFileList = (comp) => {
  const val = props.formData[comp.name]
  if (!val) return []
  if (comp.multiple) return Array.isArray(val) ? val : [val]
  return [val]
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// ── Reorder handlers ────────────────────────────────
const onReorderDragStart = (comp, index, event) => {
  reorderFieldName.value = comp.name
  reorderDragIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', index)
}

const onReorderDragOver = (comp, index, event) => {
  if (reorderFieldName.value !== comp.name) return
  event.preventDefault()
  event.dataTransfer.dropEffect = 'move'
  reorderDragOverIndex.value = index
}

const onReorderDrop = (comp, index, event) => {
  event.preventDefault()
  if (reorderFieldName.value !== comp.name) return
  const from = reorderDragIndex.value
  const to = index
  if (from === null || from === to) {
    resetReorderState()
    return
  }
  const files = [...(props.formData[comp.name] || [])]
  const [moved] = files.splice(from, 1)
  files.splice(to, 0, moved)
  props.formData[comp.name] = files
  resetReorderState()
}

const onReorderDragEnd = () => {
  resetReorderState()
}

const resetReorderState = () => {
  reorderDragIndex.value = null
  reorderDragOverIndex.value = null
  reorderFieldName.value = null
}

// ── Repeater helpers ────────────────────────────────
const buildEmptyItem = (comp) => {
  const emptyItem = {}
  ;(comp.components || []).forEach(child => {
    emptyItem[child.name] = child.type === 'Toggle' ? 0 : ''
  })
  return emptyItem
}

const repeaterAdd = (comp) => {
  if (!props.formData[comp.name]) {
    props.formData[comp.name] = []
  }
  props.formData[comp.name].push(buildEmptyItem(comp))
}

// Initialize repeater default items
const initRepeaterDefaults = (components) => {
  const walk = (comps) => {
    for (const comp of (comps || [])) {
      if (comp.type === 'Repeater' && !props.formData[comp.name]?.length) {
        const count = comp.defaultItems ?? 1
        const items = []
        for (let i = 0; i < count; i++) items.push(buildEmptyItem(comp))
        props.formData[comp.name] = items
      }
      // Recurse into Grid/Section
      if (comp.components) walk(comp.components)
    }
  }
  walk(components)
}

// Run on mount
onMounted(() => {
  initRepeaterDefaults(props.components)
})

const repeaterRemove = (fieldName, index) => {
  if (props.formData[fieldName]) {
    props.formData[fieldName].splice(index, 1)
  }
}

const repeaterMoveUp = (fieldName, index) => {
  if (index <= 0) return
  const items = props.formData[fieldName]
  const temp = items[index]
  items[index] = items[index - 1]
  items[index - 1] = temp
}

const repeaterMoveDown = (fieldName, index) => {
  const items = props.formData[fieldName]
  if (index >= items.length - 1) return
  const temp = items[index]
  items[index] = items[index + 1]
  items[index + 1] = temp
}

// Repeater drag reorder
const repeaterDragIndex = ref(null)
const repeaterDragOverIndex = ref(null)
const repeaterDragField = ref(null)

const onRepeaterDragStart = (fieldName, index, event) => {
  repeaterDragField.value = fieldName
  repeaterDragIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', index)
}

const onRepeaterDragOver = (fieldName, index, event) => {
  if (repeaterDragField.value !== fieldName) return
  event.preventDefault()
  event.dataTransfer.dropEffect = 'move'
  repeaterDragOverIndex.value = index
}

const onRepeaterDrop = (fieldName, index, event) => {
  event.preventDefault()
  if (repeaterDragField.value !== fieldName) return
  const from = repeaterDragIndex.value
  const to = index
  if (from === null || from === to) {
    resetRepeaterDragState()
    return
  }
  const items = [...(props.formData[fieldName] || [])]
  const [moved] = items.splice(from, 1)
  items.splice(to, 0, moved)
  props.formData[fieldName] = items
  resetRepeaterDragState()
}

const onRepeaterDragEnd = () => {
  resetRepeaterDragState()
}

const resetRepeaterDragState = () => {
  repeaterDragIndex.value = null
  repeaterDragOverIndex.value = null
  repeaterDragField.value = null
}
</script>

<template>
  <component :is="applyAutoLayout ? 'div' : TransparentWrapper" :class="[applyAutoLayout ? 'grid grid-cols-1 md:grid-cols-2 gap-4 w-full' : '']">
    <template v-for="comp in components" :key="comp.name || comp.type + Math.random()">
    
    <!-- Nested Layout Components -->
    <div v-if="comp.type === 'Grid' && isVisible(comp)" :class="[getGridClass(comp.columns), applyAutoLayout ? getAutoColSpan(comp) : '']">
      <FormRenderer 
        :components="comp.components" 
        :formData="formData" 
        :errors="errors"
        :isNested="true"
        @update:formData="$emit('update:formData', $event)" 
      />
    </div>

    <!-- Section -->
    <div v-else-if="comp.type === 'Section' && isVisible(comp)" class="mb-4 rounded-xl border bg-card text-card-foreground shadow" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <div v-if="comp.heading" class="flex flex-col space-y-1.5 p-6">
        <h3 class="font-semibold leading-none tracking-tight">{{ comp.heading }}</h3>
      </div>
      <div class="p-6 pt-0">
        <FormRenderer 
          :components="comp.components" 
          :formData="formData" 
          :errors="errors"
          :isNested="true"
          @update:formData="$emit('update:formData', $event)" 
        />
      </div>
    </div>

    <!-- Text Input -->
    <div v-else-if="(comp.type === 'TextInput' || comp.type === 'text-input') && isVisible(comp)" class="space-y-2 relative" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="isRequired(comp)" class="text-destructive">*</span>
      </Label>
      <div class="relative">
        <Input
          :id="comp.name"
          v-model="formData[comp.name]"
          :type="getInputType(comp)"
          :placeholder="comp.placeholder || ''"
          :required="isRequired(comp)"
          :disabled="isDisabled(comp)"
          :readonly="comp.readonly"
          :class="[
            comp.revealable ? 'pr-10' : '',
            errors[comp.name] ? 'border-destructive focus-visible:ring-destructive' : ''
          ]"
        />
        <button
          v-if="comp.revealable"
          type="button"
          tabindex="-1"
          class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground focus:outline-none"
          @click="toggleReveal(comp.name)"
        >
          <EyeOff v-if="revealedInputs[comp.name]" class="h-4 w-4" />
          <Eye v-else class="h-4 w-4" />
        </button>
      </div>
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Textarea -->
    <div v-else-if="(comp.type === 'Textarea' || comp.type === 'textarea') && isVisible(comp)" class="space-y-2" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="isRequired(comp)" class="text-destructive">*</span>
      </Label>
      <textarea
        :id="comp.name"
        v-model="formData[comp.name]"
        :placeholder="comp.placeholder || ''"
        :required="isRequired(comp)"
        :disabled="isDisabled(comp)"
        :rows="comp.rows || 4"
        class="flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        :class="errors[comp.name] ? 'border-destructive focus-visible:ring-destructive' : 'border-input focus-visible:ring-ring'"
      />
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Rich Editor -->
    <div v-else-if="comp.type === 'RichEditor' && isVisible(comp)" class="space-y-2" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="isRequired(comp)" class="text-destructive">*</span>
      </Label>
      <RichEditor
        :id="comp.name"
        v-model="formData[comp.name]"
        :placeholder="comp.placeholder || ''"
        :minHeight="comp.minHeight || 200"
        :readOnly="isDisabled(comp)"
        :class="errors[comp.name] ? 'border-destructive focus-within:ring-destructive relative border rounded-md ring-offset-background' : ''"
      />
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Date/Time Picker -->
    <div v-else-if="(comp.type === 'DatePicker' || comp.type === 'date-picker' || comp.type === 'TimePicker' || comp.type === 'DateRangePicker') && isVisible(comp)" class="space-y-2" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="isRequired(comp)" class="text-destructive">*</span>
      </Label>
      <DatePicker
        v-model="formData[comp.name]"
        :placeholder="comp.placeholder || ''"
        :disabled="isDisabled(comp)"
        :timePicker="comp.type === 'TimePicker'"
        :range="comp.type === 'DateRangePicker'"
      />
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Select -->
    <div v-else-if="(comp.type === 'Select' || comp.type === 'select') && isVisible(comp)" class="space-y-2" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="isRequired(comp)" class="text-destructive">*</span>
      </Label>
      <VueSelect
        :id="comp.name"
        :modelValue="formData[comp.name]"
        @update:modelValue="val => formData[comp.name] = val"
        :options="comp.options"
        :optionsFrom="comp.optionsFrom"
        :multiple="comp.multiple || false"
        :searchable="comp.searchable || false"
        :disabled="isDisabled(comp)"
        :placeholder="comp.placeholder || `Select ${comp.label}`"
        :createOptionSchema="comp.createOptionSchema"
        :createOptionEndpoint="comp.createOptionEndpoint"
        :createOptionLabel="comp.createOptionLabel || 'Create New'"
      />
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Toggle -->
    <div v-else-if="(comp.type === 'Toggle' || comp.type === 'toggle') && isVisible(comp)" class="space-y-2 py-2" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <div class="flex items-center gap-3">
        <Switch
          :id="comp.name"
          :checked="isTruthy(formData[comp.name])"
          :model-value="isTruthy(formData[comp.name])"
          @update:checked="val => formData[comp.name] = val ? 1 : 0"
          @update:model-value="val => formData[comp.name] = val ? 1 : 0"
          :disabled="isDisabled(comp)"
        />
        <Label :for="comp.name">
          {{ comp.label }}
          <span v-if="isRequired(comp)" class="text-destructive">*</span>
        </Label>
      </div>
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Radio -->
    <div v-else-if="(comp.type === 'Radio' || comp.type === 'radio') && isVisible(comp)" class="space-y-3" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label>
        {{ comp.label }}
        <span v-if="isRequired(comp)" class="text-destructive">*</span>
      </Label>
      <RadioGroup v-model="formData[comp.name]" :disabled="isDisabled(comp)" :class="comp.layout === 'horizontal' ? 'flex flex-row flex-wrap gap-4' : 'flex flex-col gap-2'">
        <div class="flex items-center space-x-2" v-for="opt in comp.options" :key="opt.value">
          <RadioGroupItem :id="`${comp.name}-${opt.value}`" :value="opt.value" />
          <Label :for="`${comp.name}-${opt.value}`" class="font-normal cursor-pointer">{{ opt.label }}</Label>
        </div>
      </RadioGroup>
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Checkbox -->
    <div v-else-if="(comp.type === 'Checkbox' || comp.type === 'checkbox') && isVisible(comp)" class="space-y-3" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label v-if="comp.multiple">
        {{ comp.label }}
        <span v-if="isRequired(comp)" class="text-destructive">*</span>
      </Label>
      <div :class="comp.layout === 'horizontal' ? 'flex flex-row flex-wrap gap-4' : 'flex flex-col gap-2'">
        <template v-if="comp.multiple">
          <div class="flex items-center space-x-2" v-for="opt in comp.options" :key="opt.value">
            <Checkbox 
              :id="`${comp.name}-${opt.value}`" 
              :checked="(formData[comp.name] || []).includes(opt.value)"
              @update:checked="val => toggleCheckbox(comp.name, opt.value, val)"
              :disabled="isDisabled(comp)"
            />
            <Label :for="`${comp.name}-${opt.value}`" class="font-normal cursor-pointer">{{ opt.label }}</Label>
          </div>
        </template>
        <template v-else>
          <div class="flex items-center space-x-2">
            <Checkbox 
              :id="comp.name" 
              :checked="isTruthy(formData[comp.name])"
              @update:checked="val => formData[comp.name] = val ? 1 : 0"
              :disabled="isDisabled(comp)"
            />
            <Label :for="comp.name" class="font-normal cursor-pointer">
              {{ comp.label }}
              <span v-if="isRequired(comp)" class="text-destructive">*</span>
            </Label>
          </div>
        </template>
      </div>
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Repeater -->
    <div v-else-if="comp.type === 'Repeater' && isVisible(comp)" class="space-y-3" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label>
        {{ comp.label }}
        <span v-if="isRequired(comp)" class="text-destructive">*</span>
      </Label>

      <div class="space-y-3">
        <div
          v-for="(item, index) in (formData[comp.name] || [])"
          :key="index"
          :draggable="comp.reorderable"
          @dragstart="comp.reorderable ? onRepeaterDragStart(comp.name, index, $event) : null"
          @dragover="comp.reorderable ? onRepeaterDragOver(comp.name, index, $event) : null"
          @drop="comp.reorderable ? onRepeaterDrop(comp.name, index, $event) : null"
          @dragend="comp.reorderable ? onRepeaterDragEnd() : null"
          :class="[
            'relative rounded-lg border bg-card p-4 transition-all duration-200',
            comp.reorderable ? 'cursor-grab active:cursor-grabbing' : '',
            repeaterDragIndex === index && repeaterDragField === comp.name
              ? 'opacity-40 scale-[0.98] bg-muted/50 border-dashed'
              : '',
            repeaterDragOverIndex === index && repeaterDragField === comp.name && repeaterDragIndex !== index
              ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
              : '',
          ]"
        >
          <!-- Header with index and actions -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <div
                v-if="comp.reorderable"
                class="shrink-0 flex items-center justify-center text-muted-foreground/50 hover:text-muted-foreground transition-colors"
              >
                <GripVertical class="h-4 w-4" />
              </div>
              <span class="text-xs font-medium text-muted-foreground">#{{ index + 1 }}</span>
            </div>
            <div class="flex items-center gap-1">
              <!-- Move up -->
              <button
                v-if="comp.reorderable && index > 0"
                type="button"
                @click="repeaterMoveUp(comp.name, index)"
                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
              </button>
              <!-- Move down -->
              <button
                v-if="comp.reorderable && index < (formData[comp.name] || []).length - 1"
                type="button"
                @click="repeaterMoveDown(comp.name, index)"
                class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
              </button>
              <!-- Delete -->
              <button
                v-if="comp.deletable"
                type="button"
                @click="repeaterRemove(comp.name, index)"
                class="p-1 rounded text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
              </button>
            </div>
          </div>

          <!-- Nested form fields -->
          <div :class="comp.columns ? `grid grid-cols-1 md:grid-cols-${comp.columns} gap-4` : 'space-y-4'">
            <template v-for="child in comp.components" :key="child.name">
              <div class="space-y-2">
                <Label :for="`${comp.name}.${index}.${child.name}`">
                  {{ child.label }}
                  <span v-if="child.required" class="text-destructive">*</span>
                </Label>
                <!-- Text input -->
                <Input
                  v-if="child.type === 'TextInput'"
                  :id="`${comp.name}.${index}.${child.name}`"
                  :type="child.inputType || 'text'"
                  v-model="item[child.name]"
                  :placeholder="child.placeholder"
                  :disabled="isDisabled(child)"
                />
                <!-- Textarea -->
                <textarea
                  v-else-if="child.type === 'Textarea'"
                  :id="`${comp.name}.${index}.${child.name}`"
                  v-model="item[child.name]"
                  :placeholder="child.placeholder"
                  :disabled="isDisabled(child)"
                  :rows="child.rows || 3"
                  class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
                <!-- Select (VueSelect) -->
                <VueSelect
                  v-else-if="child.type === 'Select'"
                  :id="`${comp.name}.${index}.${child.name}`"
                  :modelValue="item[child.name]"
                  @update:modelValue="val => item[child.name] = val"
                  :options="child.options"
                  :optionsFrom="child.optionsFrom"
                  :multiple="child.multiple || false"
                  :searchable="child.searchable || false"
                  :disabled="isDisabled(child)"
                  :placeholder="child.placeholder || `Select ${child.label}`"
                  :createOptionSchema="child.createOptionSchema"
                  :createOptionEndpoint="child.createOptionEndpoint"
                  :createOptionLabel="child.createOptionLabel || 'Create New'"
                />
                <!-- Toggle -->
                <div v-else-if="child.type === 'Toggle'" class="flex items-center gap-3">
                  <Switch
                    :id="`${comp.name}.${index}.${child.name}`"
                    :checked="isTruthy(item[child.name])"
                    @update:checked="val => item[child.name] = val ? 1 : 0"
                    :disabled="isDisabled(child)"
                  />
                </div>
                <!-- Fallback: text input -->
                <Input
                  v-else
                  :id="`${comp.name}.${index}.${child.name}`"
                  v-model="item[child.name]"
                  :placeholder="child.placeholder"
                  :disabled="isDisabled(child)"
                />
                <p v-if="child.hint" class="text-xs text-muted-foreground">{{ child.hint }}</p>
                <p v-if="errors[`${comp.name}.${index}.${child.name}`]" class="text-sm text-destructive">{{ errors[`${comp.name}.${index}.${child.name}`] }}</p>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- Add button -->
      <Button
        type="button"
        variant="outline"
        size="sm"
        @click="repeaterAdd(comp)"
        :disabled="comp.maxItems && (formData[comp.name] || []).length >= comp.maxItems"
        class="gap-1.5"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        {{ comp.addActionLabel || 'Add' }}
      </Button>

      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- File Input -->
    <div v-else-if="(comp.type === 'FileInput' || comp.type === 'file-input') && isVisible(comp)" class="space-y-2" :class="applyAutoLayout ? getAutoColSpan(comp) : ''">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="comp.required" class="text-destructive">*</span>
      </Label>

      <!-- Drop zone -->
      <div
        @dragover.prevent="dragOver[comp.name] = true"
        @dragleave="dragOver[comp.name] = false"
        @drop="handleFileDrop($event, comp)"
        :class="[
          'relative flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-6 transition-colors cursor-pointer',
          dragOver[comp.name]
            ? 'border-primary bg-primary/5'
            : errors[comp.name]
              ? 'border-destructive/50 bg-destructive/5'
              : 'border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/30'
        ]"
        @click="$refs[`file-${comp.name}`]?.[0]?.click()"
      >
        <input
          :ref="`file-${comp.name}`"
          type="file"
          :id="comp.name"
          :multiple="comp.multiple"
          :accept="comp.acceptedFileTypes?.join(',')"
          @change="handleFileSelect($event, comp)"
          class="hidden"
        />

        <div class="flex flex-col items-center gap-1.5 text-center">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted">
            <Upload class="h-5 w-5 text-muted-foreground" />
          </div>
          <div>
            <p class="text-sm font-medium">
              {{ comp.placeholder || 'Click or drag files here' }}
            </p>
            <p class="text-xs text-muted-foreground mt-0.5">
              <template v-if="comp.image">JPG, PNG, GIF, WebP, SVG</template>
              <template v-else>All file types</template>
              <template v-if="comp.maxSize"> · Max {{ comp.maxSize >= 1024 ? ((comp.maxSize / 1024).toFixed(0) + ' MB') : (comp.maxSize + ' KB') }}</template>
            </p>
          </div>
        </div>
      </div>

      <!-- Progress bar -->
      <div v-if="fileProgress[comp.name] != null" class="space-y-1">
        <div class="flex justify-between text-xs text-muted-foreground">
          <span>Loading file...</span>
          <span>{{ fileProgress[comp.name] }}%</span>
        </div>
        <div class="h-1.5 w-full rounded-full bg-muted overflow-hidden">
          <div
            class="h-full rounded-full bg-primary transition-all duration-300 ease-out"
            :style="{ width: fileProgress[comp.name] + '%' }"
          />
        </div>
      </div>

      <!-- Preview / File list -->
      <div v-if="getFileList(comp).length > 0" class="space-y-1">
        <template v-for="(file, fi) in getFileList(comp)" :key="fi">
          <div
            :draggable="comp.multiple && comp.reorderable"
            @dragstart="comp.reorderable ? onReorderDragStart(comp, fi, $event) : null"
            @dragover="comp.reorderable ? onReorderDragOver(comp, fi, $event) : null"
            @drop="comp.reorderable ? onReorderDrop(comp, fi, $event) : null"
            @dragend="comp.reorderable ? onReorderDragEnd() : null"
            :class="[
              'flex items-center gap-3 rounded-lg border p-3 group transition-all duration-200',
              reorderDragIndex === fi && reorderFieldName === comp.name
                ? 'opacity-40 scale-[0.98] bg-muted/50 border-dashed'
                : 'bg-muted/30',
              reorderDragOverIndex === fi && reorderFieldName === comp.name && reorderDragIndex !== fi
                ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                : '',
              comp.reorderable && comp.multiple ? 'cursor-grab active:cursor-grabbing' : ''
            ]"
          >
            <!-- Drag handle -->
            <div
              v-if="comp.multiple && comp.reorderable"
              class="shrink-0 flex items-center justify-center text-muted-foreground/50 hover:text-muted-foreground transition-colors"
            >
              <GripVertical class="h-5 w-5" />
            </div>

            <!-- Image preview -->
            <div v-if="file.type?.startsWith('image/') && (comp.multiple ? filePreviews[comp.name]?.[file.name] : filePreviews[comp.name])" class="relative shrink-0">
              <img
                :src="comp.multiple ? filePreviews[comp.name][file.name] : filePreviews[comp.name]"
                :alt="file.name"
                class="h-14 w-14 rounded-md object-cover border"
              />
            </div>
            <!-- File icon for non-images -->
            <div v-else class="shrink-0">
              <div class="flex h-14 w-14 items-center justify-center rounded-md bg-muted border">
                <ImageIcon v-if="file.type?.startsWith('image/')" class="h-6 w-6 text-muted-foreground" />
                <FileIcon v-else class="h-6 w-6 text-muted-foreground" />
              </div>
            </div>

            <!-- File info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ file.name }}</p>
              <p class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</p>
            </div>

            <!-- Remove button -->
            <button
              type="button"
              @click.stop="removeFile(comp, comp.multiple ? fi : null)"
              class="shrink-0 p-1 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors opacity-100 md:opacity-0 md:group-hover:opacity-100"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </template>
      </div>

      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    </template>
  </component>
</template>

<script>
// Expose recursive component
export default {
  name: 'FormRenderer'
}
</script>
