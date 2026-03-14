<script setup>
import { computed } from 'vue'

const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
})

const imageUrl = computed(() => {
  const path = props.row[props.col.name]
  if (!path) return null
  if (path.startsWith('http') || path.startsWith('data:')) return path
  // Ensure we have a leading slash for root-relative resolving
  const cleanPath = path.startsWith('/') ? path : `/${path}`
  if (cleanPath.startsWith('/storage/') || cleanPath.startsWith('/public/')) return cleanPath
  return `/storage${cleanPath}`
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
