<script setup>
import { computed } from 'vue'
import { resolveIcon, resolveColorClass, resolveColorStyle } from '../utils'

const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
})

const color = computed(() => props.row._v_columns?.[props.col.name]?.color)
const iconComponent = computed(() => resolveIcon(props.row[props.col.name]))
</script>

<template>
  <div class="flex items-center justify-center">
    <component
      v-if="row[col.name]"
      :is="iconComponent"
      class="w-5 h-5 text-muted-foreground"
      :class="resolveColorClass(color)"
      :style="resolveColorStyle(color)"
    />
    <span v-else class="text-muted-foreground">—</span>
  </div>
</template>
