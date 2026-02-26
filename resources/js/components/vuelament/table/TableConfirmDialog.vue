<script setup>
import { inject } from 'vue'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { maxWidthClass } from './utils'

const { confirmDialog } = inject('tableState')
</script>

<template>
  <AlertDialog
    :open="confirmDialog.isOpen"
    @update:open="confirmDialog.isOpen = $event"
  >
    <AlertDialogContent
      :class="
        confirmDialog.action?.modalWidth
          ? maxWidthClass[confirmDialog.action.modalWidth] || confirmDialog.action.modalWidth
          : 'sm:max-w-md'
      "
      @interact-outside="(e) => { if (confirmDialog.action?.modalCloseByClickingAway === false) e.preventDefault() }"
    >
      <AlertDialogHeader>
        <AlertDialogTitle>
          {{ confirmDialog.action?.modalHeading || confirmDialog.title }}
        </AlertDialogTitle>
        <AlertDialogDescription>
          {{ confirmDialog.action?.modalDescription || confirmDialog.description }}
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter class="pt-4">
        <AlertDialogCancel
          v-if="confirmDialog.action?.modalCancelAction !== false"
          @click="confirmDialog.isOpen = false"
          :class="confirmDialog.action?.modalCancelActionColor === 'danger'
            ? 'text-destructive border-destructive hover:bg-destructive/10' : ''"
        >
          {{ confirmDialog.action?.modalCancelActionLabel || 'Cancel' }}
        </AlertDialogCancel>
        <AlertDialogAction
          v-if="confirmDialog.action?.modalSubmitAction !== false"
          @click="() => { confirmDialog.onConfirm(); confirmDialog.isOpen = false }"
          :class="
            confirmDialog.action?.modalSubmitActionColor === 'danger' ||
            confirmDialog.title.includes('Delete') ||
            confirmDialog.action?.color === 'danger'
              ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90' : ''
          "
        >
          {{ confirmDialog.action?.modalSubmitActionLabel || 'Continue' }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
