<script setup>
import { computed } from 'vue'
import { formatCell, resolveColorClass, resolveColorStyle } from '../utils'

const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
})

const color = computed(() => props.row._v_columns?.[props.col.name]?.color)

const badgeClass = computed(() => {
  if (color.value) return resolveColorClass(color.value, 'badge')
  return props.row[props.col.name]
    ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
    : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
})
</script>

<template>
  <span
    :class="[
      'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
      badgeClass,
    ]"
    :style="resolveColorStyle(color, 'badge')"
  >
    <span v-if="col.prefix" class="mr-1 opacity-70">{{ col.prefix }}</span>
    {{ formatCell(row, col) }}
    <span v-if="col.suffix" class="ml-1 opacity-70">{{ col.suffix }}</span>
  </span>
</template>
