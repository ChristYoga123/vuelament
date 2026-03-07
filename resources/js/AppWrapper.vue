<script setup>
import { onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'vue-sonner'

const page = usePage()

const showToast = () => {
  const f = page.props.flash || {}
  if (f.success) toast.success('Success', { description: f.success })
  if (f.error) toast.error('Error', { description: f.error })
  if (f.warning) toast.warning('Warning', { description: f.warning })
  if (f.info) toast.info('Info', { description: f.info })
}

const hasFlash = () => {
  const f = page.props.flash || {}
  return !!(f.success || f.error || f.warning || f.info)
}

onMounted(() => {
  if (hasFlash()) {
    setTimeout(() => showToast(), 200)
  }
})

router.on('finish', () => {
  if (hasFlash()) {
    setTimeout(() => showToast(), 50)
  }
})
</script>

<template>
  <slot />
  <Toaster position="top-right" richColors />
</template>
