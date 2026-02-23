<script setup>
import { ref, computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Eye, EyeOff } from 'lucide-vue-next'

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
        rows="4"
        class="flex w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        :class="errors[comp.name] ? 'border-destructive focus-visible:ring-destructive' : 'border-input focus-visible:ring-ring'"
      />
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
        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        :class="errors[comp.name] ? 'border-destructive focus-visible:ring-destructive' : 'border-input focus-visible:ring-ring'"
      >
        <option value="">Pilih {{ comp.label }}</option>
        <option v-for="(optLabel, optVal) in comp.options" :key="optVal" :value="optVal">
          {{ optLabel }}
        </option>
      </select>
      <p v-if="errors[comp.name]" class="text-sm text-destructive">{{ errors[comp.name] }}</p>
    </div>

    <!-- Toggle -->
    <div v-else-if="comp.type === 'Toggle' || comp.type === 'toggle'" class="flex items-center gap-3 py-2">
      <input
        :id="comp.name"
        v-model="formData[comp.name]"
        type="checkbox"
        class="rounded border-border"
      />
      <Label :for="comp.name">{{ comp.label }}</Label>
    </div>

  </template>
</template>

<script>
// Expose recursive component
export default {
  name: 'FormRenderer'
}
</script>
