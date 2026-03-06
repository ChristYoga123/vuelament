<script setup>
const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
})
</script>

<template>
  <div class="flex items-center">
    <img
      v-if="row[col.name]"
      :src="row[col.name]"
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
