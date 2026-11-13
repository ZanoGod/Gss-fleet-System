import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import VehicleListPage from '../pages/VehicleListPage.vue'
import api from '@/services/api'

vi.mock('@/services/api', () => ({ default: { get: vi.fn() } }))

const wrappers: { unmount: () => void }[] = []
afterEach(() => wrappers.splice(0).forEach((wrapper) => wrapper.unmount()))

beforeEach(() => {
  vi.clearAllMocks()
  vi.mocked(api.get).mockImplementation(async (url) => {
    if (String(url).endsWith('/summary')) {
      return { data: { data: { total: 0, available: 0, assigned: 0, maintenance: 0 } } }
    }
    return { data: { data: [], meta: { last_page: 1, current_page: 1, total: 0 } } }
  })
})

async function openFleet(scope: 'daily' | 'monthly') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [{ path: '/fleet', component: VehicleListPage }],
  })
  await router.push({ path: '/fleet', query: { resource_type: scope, month: '2026-12' } })
  await router.isReady()
  const wrapper = mount(VehicleListPage, { global: { plugins: [router] } })
  wrappers.push(wrapper)
  await flushPromises()
  return { wrapper, router }
}

describe('vehicle filters', () => {
  it.each(['daily', 'monthly'] as const)(
    'applies Search, Stored Status and Assignment Month together and resets in the %s fleet',
    async (scope) => {
      const { wrapper, router } = await openFleet(scope)
      expect(wrapper.text()).toContain('December 2026')
      expect(wrapper.find('input[type="month"]').exists()).toBe(false)

      await wrapper.get('#filter-search').setValue('Toyota')
      await wrapper.get('#filter-status').setValue('Available')
      await wrapper.get('[aria-label="Next assignment month"]').trigger('click')
      expect(wrapper.text()).toContain('January 2027')
      await wrapper.get('form').trigger('submit')
      await flushPromises()

      const listCalls = vi.mocked(api.get).mock.calls.filter(([url]) => String(url) === '/api/cars')
      expect(listCalls[listCalls.length - 1]?.[1]?.params).toMatchObject({
        resource_type: scope,
        search: 'Toyota',
        status: 'Available',
        month: '2027-01',
      })

      await wrapper.get('.module-filter-reset').trigger('click')
      await flushPromises()
      const resetCall = vi.mocked(api.get).mock.calls
        .filter(([url]) => String(url) === '/api/cars')
        .slice(-1)[0]
      const resetParams = resetCall?.[1]?.params as Record<string, unknown> | undefined
      expect(resetParams).toMatchObject({ resource_type: scope, search: '', status: '' })
      expect(String(resetParams?.month)).toMatch(/^\d{4}-\d{2}$/)
      expect(router.currentRoute.value.query.resource_type).toBe(scope)
      expect(wrapper.get('#filter-search').element).toHaveProperty('value', '')
    },
  )
})
