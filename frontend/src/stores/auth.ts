import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as service from '@/services/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<service.User | null>(null)
  const isLoading = ref(false)
  const isInitializing = ref(true)
  const initialized = ref(false)
  let revision = 0
  let pending: Promise<void> | null = null
  const isAuthenticated = computed(() => user.value !== null)
  const hasPermission = (permission: string) => {
    const currentUser = user.value
    if (!currentUser?.is_active || currentUser.role_id == null) return false
    const permissions = currentUser.permissions ?? []
    const role = currentUser.role
    const isAdministrator =
      role?.slug === 'administrator' || (role?.is_system === true && role.slug === 'admin')
    if (isAdministrator || permissions.includes(permission)) return true
    if (permission.endsWith('.view') && permissions.includes(`${permission.slice(0, -5)}.manage`)) return true
    if (permission === 'users.view')
      return ['users.create', 'users.update', 'users.delete', 'access.manage', 'roles.view', 'roles.manage'].some((item) => permissions.includes(item))
    return permission === 'roles.view' && (permissions.includes('roles.manage') || permissions.includes('users.view'))
  }
  function clearLegacy() {
    try {
      sessionStorage.removeItem('gss_token')
      localStorage.removeItem('gss_token')
    } catch {
      /* Storage may be blocked. */
    }
  }
  function clearAuth() {
    revision++
    user.value = null
    clearLegacy()
  }
  async function getUser() {
    if (!pending) {
      const currentRevision = revision
      pending = service
        .getUser()
        .then((value) => {
          if (currentRevision === revision) user.value = value
        })
        .finally(() => {
          pending = null
        })
    }
    await pending
  }
  async function initializeAuth() {
    if (initialized.value) return
    isInitializing.value = true
    clearLegacy()
    try {
      await getUser()
      initialized.value = true
    } finally {
      isInitializing.value = false
    }
  }
  async function login(identifier: string, password: string) {
    isLoading.value = true
    revision++
    try {
      user.value = await service.login(identifier, password)
      initialized.value = true
      clearLegacy()
    } finally {
      isLoading.value = false
    }
  }
  async function logout() {
    isLoading.value = true
    try {
      await service.logout()
      clearAuth()
    } finally {
      isLoading.value = false
    }
  }
  return {
    user,
    hasPermission,
    isAuthenticated,
    isLoading,
    isInitializing,
    login,
    logout,
    getUser,
    initializeAuth,
    clearAuth,
  }
})
