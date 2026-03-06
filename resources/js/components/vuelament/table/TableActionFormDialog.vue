<script setup>
import { inject } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import FormRenderer from '@/Pages/Vuelament/Resource/FormRenderer.vue'
import { maxWidthClass } from './utils'

const {
  actionFormDialog,
  isSubmittingCustomAction,
  actionFormErrors,
  submitActionForm,
} = inject('tableState')
</script>

<template>
  <component
    :is="actionFormDialog.action?.requiresConfirmation ? AlertDialog : Dialog"
    :open="actionFormDialog.isOpen"
    @update:open="actionFormDialog.isOpen = $event"
  >
    <component
      :is="actionFormDialog.action?.requiresConfirmation ? AlertDialogContent : DialogContent"
      class="max-h-[90vh] overflow-y-auto"
      :class="
        actionFormDialog.action?.modalWidth
          ? maxWidthClass[actionFormDialog.action.modalWidth] || actionFormDialog.action.modalWidth
          : 'sm:max-w-xl'
      "
      @interact-outside="(e) => { if (actionFormDialog.action?.modalCloseByClickingAway === false) e.preventDefault() }"
    >
      <component
        :is="actionFormDialog.action?.requiresConfirmation ? AlertDialogHeader : DialogHeader"
      >
        <component
          :is="actionFormDialog.action?.requiresConfirmation ? AlertDialogTitle : DialogTitle"
        >
          {{ actionFormDialog.action?.modalHeading || actionFormDialog.action?.label }}
        </component>
        <component
          v-if="actionFormDialog.action?.modalDescription"
          :is="actionFormDialog.action?.requiresConfirmation ? AlertDialogDescription : DialogDescription"
        >
          {{ actionFormDialog.action?.modalDescription }}
        </component>
      </component>

      <!-- Infolist -->
      <div v-if="actionFormDialog.action?.infolist?.length" class="space-y-4 py-4 px-1">
        <div
          v-for="(info, i) in actionFormDialog.action.infolist"
          :key="i"
          class="grid grid-cols-3 gap-2 py-2 border-b last:border-0 text-sm"
        >
          <div class="text-muted-foreground font-medium">{{ info.label }}</div>
          <div class="col-span-2">{{ actionFormDialog.row[info.name] || '-' }}</div>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitActionForm" class="space-y-4 py-4">
        <FormRenderer
          v-if="actionFormDialog.action?.formSchema?.length"
          :components="actionFormDialog.action?.formSchema"
          :formData="actionFormDialog.formData"
          :errors="actionFormErrors"
        />
        <component
          :is="actionFormDialog.action?.requiresConfirmation ? AlertDialogFooter : DialogFooter"
          class="pt-4 border-t"
          v-if="
            actionFormDialog.action?.formSchema?.length ||
            actionFormDialog.action?.modalSubmitAction !== false ||
            actionFormDialog.action?.modalCancelAction !== false
          "
        >
          <Button
            v-if="actionFormDialog.action?.modalCancelAction !== false"
            type="button"
            variant="outline"
            @click="actionFormDialog.isOpen = false"
            :class="actionFormDialog.action?.modalCancelActionColor === 'danger'
              ? 'text-destructive border-destructive hover:bg-destructive/10' : ''"
          >
            {{ actionFormDialog.action?.modalCancelActionLabel || 'Cancel' }}
          </Button>
          <Button
            v-if="actionFormDialog.action?.modalSubmitAction !== false"
            type="submit"
            :disabled="isSubmittingCustomAction"
            class="gap-2"
            :class="
              actionFormDialog.action?.modalSubmitActionColor === 'danger' ||
              actionFormDialog.action?.color === 'danger'
                ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90' : ''
            "
          >
            <Loader2 v-if="isSubmittingCustomAction" class="w-4 h-4 animate-spin" />
            {{ actionFormDialog.action?.modalSubmitActionLabel || 'Lanjutkan' }}
          </Button>
        </component>
      </form>
    </component>
  </component>
</template>
