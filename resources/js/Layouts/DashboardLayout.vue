<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Sparkles, LayoutDashboard, Circle, ChevronsUpDown, LogOut, Menu, PanelLeftOpen, PanelLeftClose, Sun, Moon, ChevronDown, icons } from 'lucide-vue-next'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'

const props = defineProps({
  title: { type: String, default: 'Dashboard' },
  description: { type: String, default: null },
  hideHeader: { type: Boolean, default: false },
})

const page = usePage()
const panel = computed(() => page.props.panel || {})
const user = computed(() => page.props.auth?.user || {})
const navigation = computed(() => panel.value.navigation || [])
const brandName = computed(() => panel.value.brandName || 'Vuelament')
const currentPath = computed(() => page.url)

const breadcrumbs = computed(() => page.props.breadcrumbs || [])

const collapsedGroups = ref({})

// Initialize collapsed state from group properties
watch(navigation, (groups) => {
  groups.forEach((g, i) => {
    if (collapsedGroups.value[i] === undefined) {
      collapsedGroups.value[i] = g.collapsed || false
    }
  })
}, { immediate: true })

const toggleGroup = (index) => {
  collapsedGroups.value[index] = !collapsedGroups.value[index]
}

const sidebarOpen = ref(true)
const mobileSidebarOpen = ref(false)

const userInitials = computed(() => {
  const name = user.value.name || 'U'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
})

const isActive = (url) => {
  return currentPath.value.startsWith(url)
}

const logout = () => {
  router.post(`/${panel.value.path || 'admin'}/logout`)
}

// Resolve Lucide icon component dari string name (misal 'users' → Users, 'layout-dashboard' → LayoutDashboard)
const resolveIcon = (name) => {
  if (!name) return Circle
  const pascalCase = name.replace(/(^|-)([a-z])/g, (_, __, c) => c.toUpperCase())
  return icons[pascalCase] || Circle
}

// Dark mode
const isDark = ref(false)

const toggleDark = () => {
  isDark.value = !isDark.value
  document.documentElement.classList.toggle('dark', isDark.value)
  localStorage.setItem('vuelament-theme', isDark.value ? 'dark' : 'light')
}

onMounted(() => {
  const saved = localStorage.getItem('vuelament-theme')
  if (saved === 'dark') {
    isDark.value = true
    document.documentElement.classList.add('dark')
  } else if (saved === 'light') {
    isDark.value = false
    document.documentElement.classList.remove('dark')
  } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    isDark.value = true
    document.documentElement.classList.add('dark')
  }
})
</script>

<template>
    <Head :title="`${title} - ${brandName}`" />

    <div class="min-h-screen bg-background">
        <!-- Mobile Sidebar Overlay -->
        <div
            v-if="mobileSidebarOpen"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="mobileSidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed top-0 left-0 z-50 h-full border-r border-sidebar-border bg-sidebar transition-all duration-300 overflow-y-auto',
                sidebarOpen ? 'w-64' : 'w-16',
                mobileSidebarOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <!-- Brand -->
            <div
                class="flex h-14 items-center gap-3 border-b border-sidebar-border px-4"
            >
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                >
                    <Sparkles class="w-4 h-4" />
                </div>
                <span
                    v-if="sidebarOpen"
                    class="font-semibold text-sidebar-foreground truncate"
                >
                    {{ brandName }}
                </span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1">
                <!-- Dashboard link -->
                <Link
                    :href="`/${panel.path || 'admin'}`"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                        isActive(`/${panel.path || 'admin'}`) &&
                        currentPath === `/${panel.path || 'admin'}`
                            ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium'
                            : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                    ]"
                >
                    <LayoutDashboard class="w-4 h-4 shrink-0" />
                    <span v-if="sidebarOpen">Dashboard</span>
                </Link>

                <Separator class="my-3" />

                <!-- Resource Navigation (Groups) -->
                <template v-for="(group, gi) in navigation" :key="gi">
                    <!-- Group label (Collapsible Button) -->
                    <button
                        v-if="group.label && sidebarOpen"
                        @click="group.collapsible !== false ? toggleGroup(gi) : null"
                        class="flex w-full items-center justify-between px-3 pt-3 pb-1"
                        :class="group.collapsible !== false ? 'cursor-pointer hover:opacity-75 transition-opacity' : 'cursor-default'"
                    >
                        <div class="flex items-center gap-2">
                            <component v-if="group.icon" :is="resolveIcon(group.icon)" class="w-3.5 h-3.5 text-sidebar-foreground/40" />
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/40"
                            >
                                {{ group.label }}
                            </span>
                        </div>
                        <ChevronDown 
                            v-if="group.collapsible !== false" 
                            class="w-3.5 h-3.5 text-sidebar-foreground/40 transition-transform duration-200"
                            :class="{ '-rotate-90': collapsedGroups[gi] }"
                        />
                    </button>

                    <!-- Group items -->
                    <div v-show="!collapsedGroups[gi] || !sidebarOpen" class="space-y-1">
                        <template v-for="item in group.items" :key="item.url">
                            <Link
                                :href="item.url"
                                :class="[
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                                    isActive(item.url)
                                        ? 'bg-sidebar-accent text-sidebar-accent-foreground font-medium'
                                        : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                ]"
                            >
                                <component :is="resolveIcon(item.icon)" class="w-4 h-4 shrink-0" />
                                <span v-if="sidebarOpen" class="truncate">{{
                                    item.label
                                }}</span>
                            </Link>
                        </template>
                    </div>
                </template>
            </nav>

            <!-- User Section (bottom) -->
            <div class="border-t border-sidebar-border p-3">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            :class="[
                                'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors',
                                !sidebarOpen && 'justify-center px-0',
                            ]"
                        >
                            <Avatar class="h-7 w-7 shrink-0">
                                <AvatarFallback class="text-xs">{{
                                    userInitials
                                }}</AvatarFallback>
                            </Avatar>
                            <div
                                v-if="sidebarOpen"
                                class="flex-1 text-left min-w-0"
                            >
                                <p
                                    class="truncate font-medium text-sidebar-foreground"
                                >
                                    {{ user.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-sidebar-foreground/50"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                            <ChevronsUpDown
                                v-if="sidebarOpen"
                                class="w-4 h-4 shrink-0 opacity-50"
                            />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-56" align="end" side="top">
                        <DropdownMenuLabel>
                            <p>{{ user.name }}</p>
                            <p
                                class="text-xs font-normal text-muted-foreground"
                            >
                                {{ user.email }}
                            </p>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem
                                @click="logout"
                                class="text-destructive focus:text-destructive cursor-pointer"
                            >
                                <LogOut class="w-4 h-4 mr-2" />
                                Logout
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </aside>

        <!-- Main Content -->
        <div
            :class="[
                'transition-all duration-300',
                sidebarOpen ? 'lg:pl-64' : 'lg:pl-16',
            ]"
        >
            <!-- Top Bar -->
            <header
                class="sticky top-0 z-30 flex h-14 items-center gap-4 border-b border-border bg-background/95 backdrop-blur px-4 lg:px-6"
            >
                <!-- Mobile menu toggle -->
                <button
                    class="lg:hidden text-muted-foreground hover:text-foreground"
                    @click="mobileSidebarOpen = !mobileSidebarOpen"
                >
                    <Menu class="w-5 h-5" />
                </button>

                <!-- Sidebar collapse toggle (desktop) -->
                <button
                    class="hidden lg:block text-muted-foreground hover:text-foreground"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <PanelLeftClose v-if="sidebarOpen" class="w-4.5 h-4.5" />
                    <PanelLeftOpen v-else class="w-4.5 h-4.5" />
                </button>

                <div class="flex-1" />

                <!-- Dark mode toggle -->
                <button
                    class="p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                    @click="toggleDark"
                >
                    <Moon v-if="!isDark" class="w-4.5 h-4.5" />
                    <Sun v-else class="w-4.5 h-4.5" />
                </button>

                <!-- Page title -->
                <h2 class="text-sm font-medium text-muted-foreground">
                    {{ title }}
                </h2>
            </header>

            <!-- Page Content -->
            <main class="p-4 lg:p-6">
                <!-- Breadcrumbs -->
                <div v-if="breadcrumbs.length > 0" class="mb-4">
                    <Breadcrumb>
                        <BreadcrumbList>
                            <template v-for="(bc, i) in breadcrumbs" :key="i">
                                <BreadcrumbItem>
                                    <template v-if="bc.url && i < breadcrumbs.length - 1">
                                        <BreadcrumbLink :as="Link" :href="bc.url">
                                            {{ bc.label }}
                                        </BreadcrumbLink>
                                    </template>
                                    <template v-else>
                                        <BreadcrumbPage>{{ bc.label }}</BreadcrumbPage>
                                    </template>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator v-if="i < breadcrumbs.length - 1" />
                            </template>
                        </BreadcrumbList>
                    </Breadcrumb>
                </div>

                <!-- Global Page Header -->
                <div v-if="!hideHeader && ($slots.header || title || description)" class="mb-6">
                    <slot name="header">
                        <h1 class="text-2xl font-bold tracking-tight">{{ title }}</h1>
                        <p v-if="description" class="text-sm text-muted-foreground mt-1">{{ description }}</p>
                    </slot>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
