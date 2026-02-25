<script setup>
import { computed } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
  modelValue: {
    type: [Date, String, Array],
    default: null
  },
  placeholder: {
    type: String,
    default: 'Pilih waktu...'
  },
  timePicker: {
    type: Boolean,
    default: false
  },
  range: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const internalValue = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const format = (date) => {
  if (!date) return ''
  if (props.timePicker) {
    return `${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`
  }
  
  const formatDate = (d) => {
    let day = d.getDate().toString().padStart(2, '0')
    let month = (d.getMonth() + 1).toString().padStart(2, '0')
    let year = d.getFullYear()
    return `${year}-${month}-${day}`
  }

  if (props.range && Array.isArray(date)) {
    const start = date[0] ? formatDate(date[0]) : ''
    const end = date[1] ? formatDate(date[1]) : ''
    return `${start} - ${end}`
  }
  
  return formatDate(date)
}
</script>

<template>
  <div class="calendar-wrapper relative">
    <VueDatePicker
      v-model="internalValue"
      :format="format"
      auto-apply
      :time-picker="timePicker"
      :range="range"
      :enable-time-picker="timePicker"
      :placeholder="placeholder"
      :disabled="disabled"
      input-class-name="dp-custom-input flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
    />
  </div>
</template>

<style>
/* Reset internal components to fit Shadcn/Tailwind style */
.dp__theme_light, .dp__theme_dark {
  --dp-font-family: inherit;
  --dp-border-radius: var(--radius);
  --dp-border-color: var(--border);
  --dp-primary-color: var(--primary);
  --dp-primary-text-color: var(--primary-foreground);
  --dp-background-color: var(--background);
  --dp-text-color: var(--foreground);
  --dp-hover-color: var(--muted);
  --dp-icon-color: var(--muted-foreground);
  --dp-menu-border-color: var(--border);
  --dp-menu-min-width: 250px;
}
.dp-custom-input {
  font-family: inherit;
  border-radius: calc(var(--radius) - 2px) !important;
  border-color: var(--border, #e2e8f0) !important;
}
</style>
