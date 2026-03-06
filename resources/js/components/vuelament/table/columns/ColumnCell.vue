<script setup>
/**
 * ColumnCell — dispatcher component yang memilih sub-component
 * berdasarkan col.type / col.badge / col.isToggle.
 */
import TextCell from './TextCell.vue'
import BadgeCell from './BadgeCell.vue'
import ToggleCell from './ToggleCell.vue'
import CheckboxCell from './CheckboxCell.vue'
import ImageCell from './ImageCell.vue'
import IconCell from './IconCell.vue'

const props = defineProps({
  row: { type: Object, required: true },
  col: { type: Object, required: true },
  isToggling: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle'])

const handleToggle = (row, colName, value) => {
  emit('toggle', row, colName, value)
}
</script>

<template>
  <BadgeCell v-if="col.badge" :row="row" :col="col" />
  <ToggleCell
    v-else-if="col.isToggle || col.type === 'ToggleColumn'"
    :row="row" :col="col" :disabled="isToggling"
    @toggle="handleToggle"
  />
  <CheckboxCell
    v-else-if="col.type === 'CheckboxColumn'"
    :row="row" :col="col" :disabled="isToggling"
    @toggle="handleToggle"
  />
  <ImageCell v-else-if="col.type === 'ImageColumn'" :row="row" :col="col" />
  <IconCell v-else-if="col.type === 'IconColumn'" :row="row" :col="col" />
  <TextCell v-else :row="row" :col="col" />
</template>
