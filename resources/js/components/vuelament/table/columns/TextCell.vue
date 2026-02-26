<script setup>
import { computed } from 'vue'
import { formatCell, resolveColorClass, resolveColorStyle } from '../utils'

const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
})

const color = computed(() => props.row._v_columns?.[props.col.name]?.color)
</script>

<template>
  <span
    :class="resolveColorClass(color)"
    :style="resolveColorStyle(color)"
  >
    <span v-if="col.prefix" class="mr-1 opacity-70">{{ col.prefix }}</span>
    {{ formatCell(row, col) }}
    <span v-if="col.suffix" class="ml-1 opacity-70">{{ col.suffix }}</span>
  </span>
</template>
