<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import './AppSidebar.css'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const STORAGE_KEY = 'gss-fleet-sidebar-collapsed'

function getSavedCollapsedState(): boolean {
  if (typeof window === 'undefined') return false
  try {
    return window.localStorage.getItem(STORAGE_KEY) === 'true'
  } catch {
    return false
  }
}

const collapsed = ref(getSavedCollapsedState())
const mobileOpen = ref(false)
const signingOut = ref(false)
const logoutError = ref('')
const mobileMenuButton = ref<HTMLButtonElement | null>(null)
const mobileCloseButton = ref<HTMLButtonElement | null>(null)
const sidebarElement = ref<HTMLElement | null>(null)

const navItems = [
  { to: '/dashboard', label: 'Dashboard', icon: 'bi-grid-1x2-fill' },
  { to: '/bookings', label: 'Daily Bookings', icon: 'bi-calendar-check' },
  { to: '/monthly-contracts', label: 'Monthly Contracts', icon: 'bi-calendar3' },
  { to: '/fleet', label: 'Cars / Vehicles', icon: 'bi-car-front-fill' },
  { to: '/customers', label: 'Customers', icon: 'bi-people' },
  { to: '/drivers', label: 'Drivers', icon: 'bi-person-vcard' },
  { to: '/operators', label: 'Operators', icon: 'bi-headset' },
  { to: '/insurance', label: 'Insurance', icon: 'bi-shield-check' },
  { to: '/maintenance', label: 'Maintenance', icon: 'bi-tools' },
  { to: '/payments', label: 'Payments', icon: 'bi-credit-card' },
  { to: '/reports', label: 'Reports', icon: 'bi-bar-chart-fill' },
]

function toggleDesktopSidebar(): void {
  collapsed.value = !collapsed.value
  try {
    window.localStorage.setItem(STORAGE_KEY, String(collapsed.value))
  } catch {
    // The sidebar still works when storage is unavailable.
  }
}

function openMobileMenu(): void {
  mobileOpen.value = true
}

function closeMobileMenu(restoreFocus = false): void {
  mobileOpen.value = false
  if (restoreFocus) {
    void nextTick(() => mobileMenuButton.value?.focus())
  }
}

function handleDocumentKeydown(event: KeyboardEvent): void {
  if (event.key === 'Escape' && mobileOpen.value) {
    closeMobileMenu(true)
  }
}

function handleSidebarKeydown(event: KeyboardEvent): void {
  if (!mobileOpen.value || event.key !== 'Tab') return
  const focusable = [...(sidebarElement.value?.querySelectorAll<HTMLElement>(
    'a[href], button:not([disabled])',
  ) ?? [])].filter((element) => element.getClientRects().length > 0)

  if (focusable.length === 0) return
  const first = focusable[0]
  const last = focusable[focusable.length - 1]
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last?.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first?.focus()
  }
}

function handleResize(): void {
  if (window.innerWidth >= 768 && mobileOpen.value) closeMobileMenu()
}

watch(mobileOpen, async (open) => {
  document.body.classList.toggle('gss-mobile-menu-locked', open)
  if (open) {
    await nextTick()
    mobileCloseButton.value?.focus()
  }
})

watch(() => route.fullPath, () => closeMobileMenu())

onMounted(() => {
  window.addEventListener('keydown', handleDocumentKeydown)
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleDocumentKeydown)
  window.removeEventListener('resize', handleResize)
  document.body.classList.remove('gss-mobile-menu-locked')
})

async function signOut(): Promise<void> {
  if (signingOut.value) return
  signingOut.value = true
  try {
    logoutError.value = ''
    await auth.logout()
    closeMobileMenu()
    await router.replace('/login')
  } catch {
    logoutError.value = 'Logout could not be confirmed by the server. Please try again.'
  } finally {
    signingOut.value = false
  }
}
</script>

<template>
  <div class="gss-app-layout" :class="{ 'sidebar-collapsed': collapsed, 'mobile-sidebar-open': mobileOpen }">
    <div
      v-if="mobileOpen"
      class="sidebar-backdrop"
      aria-hidden="true"
      @click="closeMobileMenu(true)"
    ></div>

    <aside
      id="gss-sidebar"
      ref="sidebarElement"
      class="sidebar"
      aria-label="Main sidebar"
      @keydown="handleSidebarKeydown"
    >
      <div class="sidebar-head">
        <!-- Desktop: hover the GSS mark to reveal the expand/collapse arrow. -->
        <button
          type="button"
          class="brand-mark sidebar-brand-trigger"
          :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          :aria-expanded="!collapsed"
          aria-controls="gss-primary-navigation"
          :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          @click="toggleDesktopSidebar"
        >
          <span class="brand-mark-text">GSS</span>
          <i
            class="bi sidebar-brand-arrow"
            :class="collapsed ? 'bi-chevron-right' : 'bi-chevron-left'"
            aria-hidden="true"
          ></i>
        </button>

        <!-- Mobile has a static brand and an explicit Close button. -->
        <div class="brand-mark sidebar-mobile-brand" aria-hidden="true">GSS</div>

        <div class="sidebar-brand-copy">
          <strong title="Fleet Management">Fleet Management</strong>
          <small>v2.0</small>
        </div>

        <button
          v-if="!collapsed"
          class="sidebar-collapse-btn"
          type="button"
          title="Collapse sidebar"
          aria-label="Collapse sidebar"
          aria-controls="gss-primary-navigation"
          :aria-expanded="true"
          @click="toggleDesktopSidebar"
        >
          <i class="bi bi-chevron-left" aria-hidden="true"></i>
        </button>

        <button
          ref="mobileCloseButton"
          type="button"
          class="sidebar-mobile-close"
          aria-label="Close navigation menu"
          @click="closeMobileMenu(true)"
        >
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>

      <nav id="gss-primary-navigation" class="sidebar-nav" aria-label="Primary navigation">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="nav-item"
          :title="collapsed ? item.label : undefined"
          :aria-label="collapsed ? item.label : undefined"
          @click="closeMobileMenu()"
        >
          <span class="nav-icon" aria-hidden="true">
            <i class="bi" :class="item.icon"></i>
          </span>
          <span class="nav-label">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <div class="sidebar-footer">
        <button
          class="nav-item logout-item"
          type="button"
          :disabled="signingOut"
          :aria-label="collapsed ? 'Logout' : undefined"
          :title="collapsed ? 'Logout' : undefined"
          @click="signOut"
        >
          <span class="nav-icon" aria-hidden="true"><i class="bi bi-box-arrow-right"></i></span>
          <span class="nav-label">{{ signingOut ? 'Signing out…' : 'Logout' }}</span>
        </button>
      </div>
    </aside>

    <div class="app-main">
      <header class="shell-header">
        <div>
          <button
            ref="mobileMenuButton"
            type="button"
            class="btn-outline-warm mobile-menu mb-3"
            aria-controls="gss-sidebar"
            :aria-expanded="mobileOpen"
            @click="openMobileMenu"
          >
            <i class="bi bi-list" aria-hidden="true"></i>
            <span>Menu</span>
          </button>
          <div class="eyebrow">Web-Based Fleet Management System</div>
          <h1>{{ $route.meta.title || 'Dashboard' }}</h1>
          <p>{{ $route.meta.description || 'Manage fleet operations from one place.' }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <span class="btn-outline-warm user-chip">
            <i class="bi bi-person-circle me-2" aria-hidden="true"></i>
            {{ auth.user?.username || auth.user?.name || 'Administrator' }}
          </span>
        </div>
      </header>
      <main class="shell-content"><div v-if="logoutError" class="alert alert-danger" role="alert">{{ logoutError }}</div><slot /></main>
    </div>
  </div>
</template>
