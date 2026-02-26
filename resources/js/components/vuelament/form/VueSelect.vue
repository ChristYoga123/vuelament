<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { X, ChevronDown, Search, Plus, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter,
} from '@/components/ui/dialog'

const props = defineProps({
  modelValue: { default: null },
  options: { type: Array, default: () => [] },
  optionsFrom: { type: String, default: null },
  multiple: { type: Boolean, default: false },
  searchable: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  placeholder: { type: String, default: '' },
  createOptionSchema: { type: Array, default: null },
  createOptionEndpoint: { type: String, default: null },
  createOptionLabel: { type: String, default: 'Create New' },
  id: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const searchQuery = ref('')
const containerRef = ref(null)
const searchInputRef = ref(null)
const dropdownRef = ref(null)

// Remote options
const remoteOptions = ref([])
const loadingRemote = ref(false)

const allOptions = computed(() => {
  if (props.optionsFrom && remoteOptions.value.length) return remoteOptions.value
  return props.options || []
})

const filteredOptions = computed(() => {
  if (!searchQuery.value) return allOptions.value
  const q = searchQuery.value.toLowerCase()
  return allOptions.value.filter(o => o.label.toLowerCase().includes(q))
})

// Selected values as array
const selectedValues = computed(() => {
  if (props.multiple) return Array.isArray(props.modelValue) ? props.modelValue : []
  return props.modelValue != null && props.modelValue !== '' ? [props.modelValue] : []
})

const selectedLabels = computed(() => {
  return selectedValues.value.map(v => {
    const opt = allOptions.value.find(o => String(o.value) === String(v))
    return opt ? opt.label : v
  })
})

const displayText = computed(() => {
  if (selectedValues.value.length === 0) return ''
  if (props.multiple) return ''
  return selectedLabels.value[0] || ''
})

// Load remote options
const loadRemoteOptions = async () => {
  if (!props.optionsFrom) return
  loadingRemote.value = true
  try {
    const res = await fetch(props.optionsFrom)
    const data = await res.json()
    remoteOptions.value = Array.isArray(data) ? data : (data.data || [])
  } catch (e) { console.error(e) }
  loadingRemote.value = false
}

if (props.optionsFrom) loadRemoteOptions()

const toggleOpen = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    searchQuery.value = ''
    nextTick(() => searchInputRef.value?.focus())
  }
}

const selectOption = (opt) => {
  if (props.multiple) {
    const arr = [...selectedValues.value]
    const idx = arr.findIndex(v => String(v) === String(opt.value))
    if (idx >= 0) arr.splice(idx, 1)
    else arr.push(opt.value)
    emit('update:modelValue', arr)
  } else {
    emit('update:modelValue', opt.value)
    isOpen.value = false
  }
  searchQuery.value = ''
}

const removeTag = (val) => {
  if (props.disabled) return
  const arr = selectedValues.value.filter(v => String(v) !== String(val))
  emit('update:modelValue', arr)
}

const isSelected = (opt) => selectedValues.value.some(v => String(v) === String(opt.value))

const clearAll = () => {
  if (props.disabled) return
  emit('update:modelValue', props.multiple ? [] : '')
}

// Close on outside click
const onClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

watch(isOpen, (v) => {
  if (v) document.addEventListener('mousedown', onClickOutside)
  else document.removeEventListener('mousedown', onClickOutside)
})

// Create option form
const createDialogOpen = ref(false)
const createFormData = ref({})
const creatingOption = ref(false)

const openCreateDialog = () => {
  createFormData.value = {}
  createDialogOpen.value = true
  isOpen.value = false
}

const submitCreateOption = async () => {
  if (!props.createOptionEndpoint) return
  creatingOption.value = true
  try {
    const res = await fetch(props.createOptionEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify(createFormData.value),
    })
    const data = await res.json()
    if (data.value !== undefined) {
      remoteOptions.value.push({ value: data.value, label: data.label || data.value })
      selectOption({ value: data.value, label: data.label || data.value })
    }
    createDialogOpen.value = false
  } catch (e) { console.error(e) }
  creatingOption.value = false
}
</script>

<template>
  <div ref="containerRef" class="relative w-full">
    <!-- Trigger -->
    <div
      @click="toggleOpen"
      :class="[
        'flex min-h-10 w-full items-center rounded-md border bg-background px-3 py-1.5 text-sm ring-offset-background transition-colors cursor-pointer',
        disabled ? 'opacity-50 cursor-not-allowed' : 'hover:border-ring',
        isOpen ? 'ring-2 ring-ring ring-offset-2 border-ring' : 'border-input',
      ]"
    >
      <div class="flex-1 flex flex-wrap items-center gap-1 min-w-0">
        <!-- Multi tags -->
        <template v-if="multiple && selectedValues.length">
          <span
            v-for="(val, i) in selectedValues"
            :key="val"
            class="inline-flex items-center gap-1 rounded-md bg-primary/10 text-primary px-2 py-0.5 text-xs font-medium max-w-37.5"
          >
            <span class="truncate">{{ selectedLabels[i] }}</span>
            <button
              type="button"
              @click.stop="removeTag(val)"
              class="shrink-0 rounded-sm hover:bg-primary/20 p-0.5"
            >
              <X class="h-3 w-3" />
            </button>
          </span>
        </template>
        <!-- Single display -->
        <span v-else-if="!multiple && displayText" class="truncate">{{ displayText }}</span>
        <!-- Placeholder -->
        <span v-if="selectedValues.length === 0" class="text-muted-foreground">
          {{ placeholder || 'Select...' }}
        </span>
      </div>
      <div class="flex items-center gap-1 shrink-0 ml-2">
        <button
          v-if="selectedValues.length > 0 && !disabled"
          type="button"
          @click.stop="clearAll"
          class="p-0.5 rounded-sm text-muted-foreground hover:text-foreground"
        >
          <X class="h-3.5 w-3.5" />
        </button>
        <ChevronDown
          class="h-4 w-4 text-muted-foreground transition-transform"
          :class="isOpen ? 'rotate-180' : ''"
        />
      </div>
    </div>

    <!-- Dropdown -->
    <div
      v-if="isOpen"
      class="absolute z-9999 mt-1 w-full rounded-md border bg-popover text-popover-foreground shadow-lg animate-in fade-in-0 zoom-in-95 slide-in-from-top-2"
    >
      <!-- Search -->
      <div v-if="searchable" class="flex items-center border-b px-3 py-2">
        <Search class="h-4 w-4 text-muted-foreground mr-2 shrink-0" />
        <input
          ref="searchInputRef"
          v-model="searchQuery"
          type="text"
          placeholder="Search..."
          class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
          @click.stop
        />
      </div>

      <!-- Options list -->
      <div class="max-h-50 overflow-y-auto p-1">
        <div v-if="loadingRemote" class="flex items-center justify-center py-4">
          <Loader2 class="h-4 w-4 animate-spin text-muted-foreground" />
        </div>
        <div
          v-else-if="filteredOptions.length === 0"
          class="py-4 text-center text-sm text-muted-foreground"
        >
          No options found.
        </div>
        <template v-else>
          <button
            v-for="opt in filteredOptions"
            :key="opt.value"
            type="button"
            @click.stop="selectOption(opt)"
            :class="[
              'w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm cursor-pointer transition-colors',
              isSelected(opt) ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-accent',
            ]"
          >
            <div
              v-if="multiple"
              :class="[
                'flex items-center justify-center h-4 w-4 rounded border shrink-0 transition-colors',
                isSelected(opt) ? 'bg-primary border-primary text-primary-foreground' : 'border-input',
              ]"
            >
              <svg v-if="isSelected(opt)" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <polyline points="20 6 9 17 4 12" />
              </svg>
            </div>
            <span class="truncate">{{ opt.label }}</span>
          </button>
        </template>
      </div>

      <!-- Create option button -->
      <div
        v-if="createOptionSchema && createOptionEndpoint"
        class="border-t p-1"
      >
        <button
          type="button"
          @click.stop="openCreateDialog"
          class="w-full flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm cursor-pointer text-primary hover:bg-accent transition-colors"
        >
          <Plus class="h-4 w-4" />
          <span>{{ createOptionLabel }}</span>
        </button>
      </div>
    </div>

    <!-- Create Option Dialog -->
    <Dialog v-if="createOptionSchema" v-model:open="createDialogOpen">
      <DialogContent class="sm:max-w-md" @click.stop>
        <DialogHeader>
          <DialogTitle>{{ createOptionLabel }}</DialogTitle>
        </DialogHeader>
        <form @submit.prevent="submitCreateOption" class="space-y-4 py-2">
          <template v-for="field in createOptionSchema" :key="field.name">
            <div class="space-y-2">
              <Label :for="'create-' + field.name">{{ field.label }}</Label>
              <Input
                :id="'create-' + field.name"
                v-model="createFormData[field.name]"
                :placeholder="field.placeholder || ''"
                :type="field.inputType || 'text'"
              />
            </div>
          </template>
          <DialogFooter>
            <Button type="button" variant="outline" @click="createDialogOpen = false">Cancel</Button>
            <Button type="submit" :disabled="creatingOption" class="gap-2">
              <Loader2 v-if="creatingOption" class="h-4 w-4 animate-spin" />
              Save
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </div>
</template>
