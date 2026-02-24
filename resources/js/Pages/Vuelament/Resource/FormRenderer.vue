<script setup>
import { ref, computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Eye, EyeOff, Upload, X, FileIcon, ImageIcon, GripVertical } from 'lucide-vue-next'

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
  }
})

const emit = defineEmits(['update:formData'])

const revealedInputs = ref({})
const toggleReveal = (name) => {
  revealedInputs.value[name] = !revealedInputs.value[name]
}

// File input state
const filePreviews = ref({})
const fileProgress = ref({})
const dragOver = ref({})

// Reorder state
const reorderDragIndex = ref(null)
const reorderDragOverIndex = ref(null)
const reorderFieldName = ref(null)

const getInputType = (comp) => {
  if (comp.revealable && revealedInputs.value[comp.name]) {
    return 'text'
  }
  if (comp.inputType) return comp.inputType
  if (comp.type === 'date-picker') return 'date'
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
</script>

<template>
  <template v-for="comp in components" :key="comp.name || comp.type + Math.random()">
    
    <!-- Nested Layout Components -->
    <div v-if="comp.type === 'Grid'" :class="getGridClass(comp.columns)">
      <FormRenderer 
        :components="comp.components" 
        :formData="formData" 
        :errors="errors"
        @update:formData="$emit('update:formData', $event)" 
      />
    </div>

    <!-- Section -->
    <div v-else-if="comp.type === 'Section'" class="mb-4 rounded-xl border bg-card text-card-foreground shadow">
      <div v-if="comp.heading" class="flex flex-col space-y-1.5 p-6">
        <h3 class="font-semibold leading-none tracking-tight">{{ comp.heading }}</h3>
      </div>
      <div class="p-6 pt-0">
        <FormRenderer 
          :components="comp.components" 
          :formData="formData" 
          :errors="errors"
          @update:formData="$emit('update:formData', $event)" 
        />
      </div>
    </div>

    <!-- Text Input -->
    <div v-else-if="comp.type === 'TextInput' || comp.type === 'text-input'" class="space-y-2 relative">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="comp.required" class="text-destructive">*</span>
      </Label>
      <div class="relative">
        <Input
          :id="comp.name"
          v-model="formData[comp.name]"
          :type="getInputType(comp)"
          :placeholder="comp.placeholder || ''"
          :required="comp.required"
          :disabled="comp.disabled"
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
    <div v-else-if="comp.type === 'Textarea' || comp.type === 'textarea'" class="space-y-2">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="comp.required" class="text-destructive">*</span>
      </Label>
      <textarea
        :id="comp.name"
        v-model="formData[comp.name]"
        :placeholder="comp.placeholder || ''"
        :required="comp.required"
        :disabled="comp.disabled"
        :rows="comp.rows || 4"
        class="flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        :class="errors[comp.name] ? 'border-destructive focus-visible:ring-destructive' : 'border-input focus-visible:ring-ring'"
      />
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Select -->
    <div v-else-if="comp.type === 'Select' || comp.type === 'select'" class="space-y-2">
      <Label :for="comp.name">
        {{ comp.label }}
        <span v-if="comp.required" class="text-destructive">*</span>
      </Label>
      <select
        :id="comp.name"
        v-model="formData[comp.name]"
        :required="comp.required"
        :disabled="comp.disabled"
        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        :class="errors[comp.name] ? 'border-destructive focus-visible:ring-destructive' : 'border-input focus-visible:ring-ring'"
      >
        <option value="">{{ comp.placeholder || `Pilih ${comp.label}` }}</option>
        <option v-for="opt in comp.options" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
      <p v-if="comp.hint" class="text-xs text-muted-foreground">{{ comp.hint }}</p>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Toggle -->
    <div v-else-if="comp.type === 'Toggle' || comp.type === 'toggle'" class="flex items-center gap-3 py-2">
      <input
        :id="comp.name"
        v-model="formData[comp.name]"
        type="checkbox"
        :disabled="comp.disabled"
        class="size-4 rounded border-input accent-primary cursor-pointer"
      />
      <Label :for="comp.name">{{ comp.label }}</Label>
      <p v-if="comp.hint" class="text-xs text-muted-foreground ml-2">{{ comp.hint }}</p>
    </div>

    <!-- File Input -->
    <div v-else-if="comp.type === 'FileInput' || comp.type === 'file-input'" class="space-y-2">
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
              {{ comp.placeholder || 'Klik atau seret file ke sini' }}
            </p>
            <p class="text-xs text-muted-foreground mt-0.5">
              <template v-if="comp.image">JPG, PNG, GIF, WebP, SVG</template>
              <template v-else>Semua tipe file</template>
              <template v-if="comp.maxSize"> · Max {{ comp.maxSize >= 1024 ? ((comp.maxSize / 1024).toFixed(0) + ' MB') : (comp.maxSize + ' KB') }}</template>
            </p>
          </div>
        </div>
      </div>

      <!-- Progress bar -->
      <div v-if="fileProgress[comp.name] != null" class="space-y-1">
        <div class="flex justify-between text-xs text-muted-foreground">
          <span>Memuat file...</span>
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
              class="shrink-0 p-1 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors opacity-0 group-hover:opacity-100"
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
</template>

<script>
// Expose recursive component
export default {
  name: 'FormRenderer'
}
</script>
