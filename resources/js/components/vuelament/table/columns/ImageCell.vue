<script setup>
import { computed } from 'vue'

const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
})

const imageUrl = computed(() => {
  const path = props.row[props.col.name]
  if (!path) return null
  if (path.startsWith('http') || path.startsWith('/')) return path
  return `/storage/${path}`
})
</script>

<template>
  <div class="flex items-center">
    <img
      v-if="imageUrl"
      :src="imageUrl"
      :style="{
        width: col.size || '40px',
        height: col.size || '40px',
        objectFit: 'cover',
      }"
      :class="{
        'rounded-full': col.isCircle,
        'rounded-md': !col.isCircle,
        'shadow-sm border border-border': col.isThumbnail,
      }"
      alt=""
    />
    <span v-else class="text-muted-foreground">—</span>
  </div>
</template>
