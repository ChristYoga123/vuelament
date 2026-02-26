<script setup>
import { inject } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Pencil, Trash2, ArchiveRestore } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { resolveIcon } from './utils'

const props = defineProps({
  row: { type: Object, required: true },
  actions: { type: Array, required: true },
})

const {
  panelPath, pageSlug,
  deleteRecord, restoreRecord, forceDeleteRecord,
  executeCustomAction,
} = inject('tableState')
</script>

<template>
  <div class="flex items-center justify-end gap-1">
    <template v-for="action in actions" :key="action.name">
      <!-- Edit -->
      <Link
        v-if="!row.deleted_at && (action.type === 'EditAction' || action.type === 'edit')"
        :href="`/${panelPath}/${pageSlug}/${row.id}/edit`"
      >
        <Button
          variant="ghost" :size="action.label ? 'sm' : 'icon'" class="h-8"
          :class="[action.label ? 'px-3' : 'w-8', {
            'text-destructive hover:text-destructive': action.color === 'danger',
            'text-yellow-400 hover:text-yellow-400': action.color === 'warning',
            'text-green-600 hover:text-green-600': action.color === 'success',
          }]"
        >
          <Pencil class="w-3.5 h-3.5" :class="action.label ? 'mr-1.5' : ''" />
          <span v-if="action.label">{{ action.label }}</span>
          <span v-else class="sr-only">Edit</span>
        </Button>
      </Link>

      <!-- Delete -->
      <Button
        v-if="!row.deleted_at && (action.type === 'DeleteAction' || action.type === 'delete')"
        variant="ghost" :size="action.label ? 'sm' : 'icon'" class="h-8"
        :class="[action.label ? 'px-3' : 'w-8',
          !action.color || action.color === 'danger' ? 'text-destructive hover:text-destructive' : {
            'text-yellow-400 hover:text-yellow-400': action.color === 'warning',
            'text-green-600 hover:text-green-600': action.color === 'success',
          }]"
        @click="deleteRecord(row.id, action)"
      >
        <Trash2 class="w-3.5 h-3.5" :class="action.label ? 'mr-1.5' : ''" />
        <span v-if="action.label">{{ action.label }}</span>
        <span v-else class="sr-only">Hapus</span>
      </Button>

      <!-- Restore -->
      <Button
        v-if="row.deleted_at && (action.type === 'RestoreAction' || action.type === 'restore')"
        variant="ghost" :size="action.label ? 'sm' : 'icon'" class="h-8"
        :class="[action.label ? 'px-3' : 'w-8',
          !action.color || action.color === 'success' ? 'text-green-600 hover:text-green-600' : {
            'text-yellow-400 hover:text-yellow-400': action.color === 'warning',
            'text-destructive hover:text-destructive': action.color === 'danger',
          }]"
        @click="restoreRecord(row.id, action)"
      >
        <ArchiveRestore class="w-3.5 h-3.5" :class="action.label ? 'mr-1.5' : ''" />
        <span v-if="action.label">{{ action.label }}</span>
        <span v-else class="sr-only">Restore</span>
      </Button>

      <!-- Force Delete -->
      <Button
        v-if="row.deleted_at && (action.type === 'ForceDeleteAction' || action.type === 'force-delete')"
        variant="ghost" :size="action.label ? 'sm' : 'icon'" class="h-8"
        :class="[action.label ? 'px-3' : 'w-8',
          !action.color || action.color === 'danger' ? 'text-red-600 hover:text-red-600' : {
            'text-yellow-400 hover:text-yellow-400': action.color === 'warning',
            'text-green-600 hover:text-green-600': action.color === 'success',
          }]"
        @click="forceDeleteRecord(row.id, action)"
      >
        <Trash2 class="w-3.5 h-3.5" :class="action.label ? 'mr-1.5' : ''" />
        <span v-if="action.label">{{ action.label }}</span>
        <span v-else class="sr-only">Hapus Permanen</span>
      </Button>

      <!-- Custom Action -->
      <template v-if="action.type === 'Action' && !action.hidden">
        <component
          v-if="row._v_actions?.[action.name]?.url && !row._v_actions?.[action.name]?.shouldOpenInNewTab"
          :is="Link"
          :href="row._v_actions?.[action.name]?.url"
          class="h-8 inline-flex items-center justify-center p-0 rounded-md hover:bg-muted"
          :class="action.label ? 'px-3' : 'w-8'"
        >
          <Button
            variant="ghost" :size="action.label ? 'sm' : 'icon'" class="h-8 w-full"
            :class="{
              'text-destructive hover:text-destructive': action.color === 'danger',
              'text-yellow-400 hover:text-yellow-400': action.color === 'warning',
              'text-green-600 hover:text-green-600': action.color === 'success',
            }"
          >
            <component :is="resolveIcon(action.icon)" class="w-3.5 h-3.5" :class="action.label ? 'mr-1.5' : ''" />
            <span v-if="action.label">{{ action.label }}</span>
          </Button>
        </component>
        <a
          v-else-if="row._v_actions?.[action.name]?.url && row._v_actions?.[action.name]?.shouldOpenInNewTab"
          :href="row._v_actions?.[action.name]?.url"
          target="_blank"
          class="h-8 inline-flex items-center justify-center p-0 rounded-md hover:bg-muted"
          :class="action.label ? 'px-3' : 'w-8'"
        >
          <Button
            variant="ghost" :size="action.label ? 'sm' : 'icon'" class="h-8 w-full"
            :class="{
              'text-destructive hover:text-destructive': action.color === 'danger',
              'text-yellow-400 hover:text-yellow-400': action.color === 'warning',
              'text-green-600 hover:text-green-600': action.color === 'success',
            }"
          >
            <component :is="resolveIcon(action.icon)" class="w-3.5 h-3.5" :class="action.label ? 'mr-1.5' : ''" />
            <span v-if="action.label">{{ action.label }}</span>
          </Button>
        </a>
        <Button
          v-else
          variant="ghost" :size="action.label ? 'sm' : 'icon'" class="h-8"
          :class="[{
            'text-destructive hover:text-destructive': action.color === 'danger',
            'text-yellow-400 hover:text-yellow-400': action.color === 'warning',
            'text-green-600 hover:text-green-600': action.color === 'success',
          }, action.label ? 'px-3' : 'w-8']"
          @click="executeCustomAction(action, row)"
        >
          <component :is="resolveIcon(action.icon)" class="w-3.5 h-3.5" :class="action.label ? 'mr-1.5' : ''" />
          <span v-if="action.label">{{ action.label }}</span>
        </Button>
      </template>
    </template>
  </div>
</template>
