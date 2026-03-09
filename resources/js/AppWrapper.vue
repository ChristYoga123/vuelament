<script setup>
import { onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'vue-sonner'

const page = usePage()

const TOAST_TYPE_MAP = {
  success: toast.success,
  error: toast.error,
  danger: toast.error,
  warning: toast.warning,
  info: toast.info,
}

const showFlashToasts = () => {
  const f = page.props.flash || {}
  if (f.success) toast.success('Success', { description: f.success })
  if (f.error) toast.error('Error', { description: f.error })
  if (f.warning) toast.warning('Warning', { description: f.warning })
  if (f.info) toast.info('Info', { description: f.info })
}

const showNotifications = () => {
  const items = page.props.notifications || []
  items.forEach((n) => {
    const fn = TOAST_TYPE_MAP[n.type] || toast.info
    fn(n.title || '', { description: n.body || '' })
  })
}

const hasFlash = () => {
  const f = page.props.flash || {}
  return !!(f.success || f.error || f.warning || f.info)
}

const hasNotifications = () => {
  const items = page.props.notifications || []
  return items.length > 0
}

const showAll = () => {
  if (hasFlash()) showFlashToasts()
  if (hasNotifications()) showNotifications()
}

onMounted(() => {
  setTimeout(() => showAll(), 200)
})

const removeFinishListener = router.on('finish', () => {
  setTimeout(() => showAll(), 50)
})

onUnmounted(() => {
  removeFinishListener()
})
</script>

<template>
  <slot />
  <Toaster position="top-right" richColors :visibleToasts="2" :duration="4000" />
</template>
