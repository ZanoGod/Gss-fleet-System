import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from './auth'
import * as service from '@/services/auth'
vi.mock('@/services/auth', () => ({ getUser: vi.fn(), login: vi.fn(), logout: vi.fn() }))
const user = { id: 1, name: 'Test', username: 'test', email: 'test@example.test', is_active: true, role_id: 1 }
beforeEach(() => {
  setActivePinia(createPinia())
  vi.resetAllMocks()
  sessionStorage.clear()
})
describe('cookie authentication', () => {
  it('deduplicates initialization and restores server identity without a token', async () => {
    vi.mocked(service.getUser).mockResolvedValue(user)
    sessionStorage.setItem('gss_token', 'obsolete')
    const auth = useAuthStore()
    await Promise.all([auth.initializeAuth(), auth.initializeAuth()])
    expect(service.getUser).toHaveBeenCalledTimes(1)
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.isInitializing).toBe(false)
    expect(sessionStorage.getItem('gss_token')).toBeNull()
  })
  it('rejects an expired identity', async () => {
    vi.mocked(service.getUser).mockResolvedValue(null)
    const auth = useAuthStore()
    await auth.initializeAuth()
    expect(auth.isAuthenticated).toBe(false)
  })
  it('recognizes the system admin role slug for navigation and route permissions', () => {
    const auth = useAuthStore()
    auth.user = {
      ...user,
      role: { id: 2, name: 'Admin', slug: 'admin', is_system: true },
      permissions: [],
    }
    expect(auth.hasPermission('users.view')).toBe(true)
    auth.user = {
      ...user,
      role: { id: 3, name: 'Admin', slug: 'admin', is_system: false },
      permissions: [],
    }
    expect(auth.hasPermission('users.view')).toBe(false)
  })
  it('does not grant client-side permissions to inactive or roleless accounts', () => {
    const auth = useAuthStore()
    auth.user = { ...user, is_active: true, role_id: null, role: null, permissions: ['dashboard.view'] }
    expect(auth.hasPermission('dashboard.view')).toBe(false)
    auth.user = { ...user, is_active: false, role_id: 1, role: null, permissions: ['dashboard.view'] }
    expect(auth.hasPermission('dashboard.view')).toBe(false)
  })
  it('does not claim logout on a network failure', async () => {
    vi.mocked(service.login).mockResolvedValue(user)
    vi.mocked(service.logout).mockRejectedValue(new Error('offline'))
    const auth = useAuthStore()
    await auth.login('test', 'password')
    await expect(auth.logout()).rejects.toThrow('offline')
    expect(auth.isAuthenticated).toBe(true)
    vi.mocked(service.logout).mockResolvedValue()
    await auth.logout()
    expect(auth.isAuthenticated).toBe(false)
  })
  it('allows a failed initialization to be retried', async () => {
    vi.mocked(service.getUser).mockRejectedValueOnce(new Error('offline')).mockResolvedValue(user)
    const auth = useAuthStore()
    await expect(auth.initializeAuth()).rejects.toThrow('offline')
    await auth.initializeAuth()
    expect(auth.user).toEqual(user)
  })
  it('does not restore identity from a request that finishes after logout', async () => {
    vi.mocked(service.login).mockResolvedValue(user)
    vi.mocked(service.logout).mockResolvedValue()
    let resolveUser!: (value: typeof user) => void
    vi.mocked(service.getUser).mockReturnValue(
      new Promise((resolve) => {
        resolveUser = resolve
      }),
    )
    const auth = useAuthStore()
    await auth.login('test', 'password')
    const pending = auth.getUser()
    await auth.logout()
    resolveUser(user)
    await pending
    expect(auth.isAuthenticated).toBe(false)
  })
})
