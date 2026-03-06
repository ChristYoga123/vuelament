<script setup>
/**
 * ToggleCell — wraps shadcn Switch with Vuelament-specific behavior:
 * - Reads value via isTruthy()
 * - Supports disabled state (while toggling / waiting for server response)
 * - Emits 'toggle' event to parent for server update
 */
import { Switch } from '@/components/ui/switch'
import { isTruthy } from '../utils'

const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle'])

const handleToggle = (value) => {
  emit('toggle', props.row, props.col.name, value)
}
</script>

<template>
  <div class="flex items-center">
    <Switch
      :checked="isTruthy(row[col.name])"
      :model-value="isTruthy(row[col.name])"
      :disabled="disabled"
      @update:checked="handleToggle"
      @update:model-value="handleToggle"
    />
  </div>
</template>
