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
  function clearLegacy() {
    try { sessionStorage.removeItem('gss_token'); localStorage.removeItem('gss_token') } catch { /* Storage may be blocked. */ }
  }
  function clearAuth() { revision++; user.value = null; clearLegacy() }
  async function getUser() {
    if (!pending) {
      const currentRevision = revision
      pending = service.getUser().then(value => {
        if (currentRevision === revision) user.value = value
      }).finally(() => { pending = null })
    }
    await pending
  }
  async function initializeAuth() {
    if (initialized.value) return
    isInitializing.value = true
    clearLegacy()
    try { await getUser(); initialized.value = true }
    finally { isInitializing.value = false }
  }
  async function login(identifier: string, password: string) {
    isLoading.value = true
    revision++
    try { user.value = await service.login(identifier, password); initialized.value = true; clearLegacy() }
    finally { isLoading.value = false }
  }
  async function logout() {
    isLoading.value = true
    try { await service.logout(); clearAuth() }
    finally { isLoading.value = false }
  }
  return { user, isAuthenticated, isLoading, isInitializing, login, logout, getUser, initializeAuth, clearAuth }
})
