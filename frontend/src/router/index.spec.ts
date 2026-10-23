import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import { flushPromises, mount } from '@vue/test-utils'
import App from '@/App.vue'

const auth = vi.hoisted(() => ({
  isAuthenticated: true,
  user: { id: 7, name: 'Test User', username: 'tester', email: 'tester@example.test' },
  logout: vi.fn(async () => undefined),
  initializeAuth: vi.fn(async () => undefined),
  getUser: vi.fn(async () => undefined),
}))

vi.mock('@/stores/auth', () => ({ useAuthStore: () => auth }))

import router from './index'

const pagePaths = [
  '/dashboard',
  '/daily-bookings', '/daily-bookings/create', '/daily-bookings/42', '/daily-bookings/42/edit',
  '/fleet', '/fleet/create', '/fleet/42', '/fleet/42/edit',
  '/customers', '/customers/create', '/customers/42', '/customers/42/edit',
  '/drivers', '/drivers/create', '/drivers/42', '/drivers/42/edit',
  '/operators', '/operators/create', '/operators/42', '/operators/42/edit',
  '/insurance', '/insurance/create', '/insurance/42', '/insurance/42/edit',
  '/maintenance', '/maintenance/create', '/maintenance/42', '/maintenance/42/edit',
  '/monthly-contracts', '/monthly-contracts/create', '/monthly-contracts/42', '/monthly-contracts/42/edit',
  '/payments', '/reports',
]

describe('application routes', () => {
  beforeEach(async () => {
    auth.isAuthenticated = true
    auth.initializeAuth.mockClear()
    auth.getUser.mockClear()
    await router.push('/login')
  })

  it('renders every protected page through the shared layout', async () => {
    await router.push('/dashboard')
    const app = mount(App, { global: { plugins: [router] } })
    await flushPromises()
    for (const [path, expectedTitle] of pagePaths.map(path => [path, router.resolve(path).meta.title] as const)) {
      await router.push(path)
      await flushPromises()
      await nextTick()
      expect(router.currentRoute.value.path, path).toBe(path)
      expect(app.find('.shell-header h1').text(), path).toBe(expectedTitle)
      expect(app.find('nav[aria-label=\"Primary navigation\"]').exists(), path).toBe(true)
    }
    app.unmount()
  })

  it('navigates from the sidebar to daily bookings', async () => {
    await router.push('/dashboard')
    const app = mount(App, { global: { plugins: [router] } })
    await flushPromises()
    await app.get('a[href="/daily-bookings"]').trigger('click')
    await flushPromises()
    expect(router.currentRoute.value.path).toBe('/daily-bookings')
    expect(app.find('.shell-header h1').text()).toBe('Daily Bookings')
    app.unmount()
  })

  it('redirects unauthenticated users to the public login layout without the sidebar', async () => {
    auth.isAuthenticated = false
    await router.push('/customers')
    expect(router.currentRoute.value.path).toBe('/login')
    expect(router.currentRoute.value.meta.public).toBe(true)
    const app = mount(App, { global: { plugins: [router] } })
    await flushPromises()
    expect(app.find('form').exists()).toBe(true)
    expect(app.find('nav[aria-label=\"Primary navigation\"]').exists()).toBe(false)
    app.unmount()
  })
})
