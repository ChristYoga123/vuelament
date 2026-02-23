<script setup>
import { watch, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'vue-sonner'

const page = usePage()

const showToast = () => {
  const f = page.props.flash || {}
  if (f.success) toast.success('Sukses', { description: f.success })
  if (f.error) toast.error('Gagal', { description: f.error })
  if (f.warning) toast.warning('Peringatan', { description: f.warning })
  if (f.info) toast.info('Informasi', { description: f.info })
}

const hasFlash = () => {
  const f = page.props.flash || {}
  return !!(f.success || f.error || f.warning || f.info)
}

// Ce flash saat initial page load
onMounted(() => {
  if (hasFlash()) {
    setTimeout(() => showToast(), 200)
  }
})

// Watch setiap kali Inertia selesai request (navigasi, form submit, dsb)
// router.on('finish') dipanggil SETIAP kali Inertia request selesai diproses.
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
