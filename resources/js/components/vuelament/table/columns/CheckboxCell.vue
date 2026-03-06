<script setup>
/**
 * CheckboxCell — native checkbox with Vuelament-specific behavior:
 * - Reads value via isTruthy()
 * - Supports disabled state (while toggling / waiting for server response)
 * - Emits 'toggle' event to parent for server update
 */
import { isTruthy } from '../utils'

const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle'])

const handleChange = (event) => {
  emit('toggle', props.row, props.col.name, event.target.checked)
}
</script>

<template>
  <div class="flex items-center">
    <input
      type="checkbox"
      :checked="isTruthy(row[col.name])"
      :disabled="disabled"
      @change="handleChange"
      class="size-4 rounded border-input accent-primary cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
    />
  </div>
</template>
