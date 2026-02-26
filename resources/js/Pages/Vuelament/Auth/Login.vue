<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

const props = defineProps({
  canRegister: { type: Boolean, default: true },
  errors: { type: Object, default: () => ({}) },
  panel: { type: Object, default: () => ({}) },
})

const panelPath = props.panel?.path || 'admin'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

const submit = () => {
  form.post(`/${panelPath}/login`, {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <AuthLayout title="Login">
    <Card>
      <CardHeader class="text-center">
        <CardTitle class="text-xl">Welcome</CardTitle>
        <CardDescription>Sign in to your account</CardDescription>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Email -->
          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="mail@example.com"
              required
              autofocus
              :class="{ 'border-destructive': form.errors.email }"
            />
            <p v-if="form.errors.email" class="text-sm text-destructive">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password -->
          <div class="space-y-2">
            <Label for="password">Password</Label>
            <div class="relative">
              <Input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                required
                :class="{ 'border-destructive': form.errors.password }"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                @click="showPassword = !showPassword"
              >
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.749 10.749 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
              </button>
            </div>
            <p v-if="form.errors.password" class="text-sm text-destructive">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Checkbox
                id="remember"
                :checked="form.remember"
                @update:checked="form.remember = $event"
              />
              <Label for="remember" class="text-sm font-normal cursor-pointer">
                Ingat saya
              </Label>
            </div>
          </div>

          <!-- Submit -->
          <Button
            type="submit"
            class="w-full"
            :disabled="form.processing"
          >
            <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            {{ form.processing ? 'Signing in...' : 'Sign in' }}
          </Button>
        </form>
      </CardContent>

      <CardFooter v-if="canRegister" class="justify-center">
        <p class="text-sm text-muted-foreground">
          Belum punya akun?
          <Link :href="`/${panelPath}/register`" class="text-primary underline-offset-4 hover:underline font-medium">
            Daftar
          </Link>
        </p>
      </CardFooter>
    </Card>
  </AuthLayout>
</template>
