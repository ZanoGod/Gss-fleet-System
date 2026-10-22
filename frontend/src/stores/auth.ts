import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import axios from 'axios'

interface AuthUser { id: number; name: string; username: string }
const api = axios.create({ baseURL: '/api', headers: { Accept: 'application/json' } })

export const useAuthStore = defineStore('auth', () => {
  const token = ref(sessionStorage.getItem('gss_token'))
  const user = ref<AuthUser | null>(null)
  const isAuthenticated = computed(() => Boolean(token.value))
  const authHeader = () => ({ Authorization: `Bearer ${token.value}` })

  async function login(username: string, password: string) {
    const { data } = await api.post<{ token: string; user: AuthUser }>('/login', { username, password })
    token.value = data.token
    user.value = data.user
    sessionStorage.setItem('gss_token', data.token)
  }
  async function loadUser() {
    if (!token.value) return false
    try {
      const { data } = await api.get<AuthUser>('/user', { headers: authHeader() })
      user.value = data
      return true
    } catch {
      token.value = null
      user.value = null
      sessionStorage.removeItem('gss_token')
      return false
    }
  }
  async function logout() {
    try { if (token.value) await api.post('/logout', {}, { headers: authHeader() }) }
    finally {
      token.value = null
      user.value = null
      sessionStorage.removeItem('gss_token')
    }
  }
  return { token, user, isAuthenticated, login, loadUser, logout }
})
