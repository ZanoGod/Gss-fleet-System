import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { insuranceApi } from '../api'
import { useInsuranceStore } from './insurance'

vi.mock('../api', () => ({ insuranceApi: {
  dashboard: vi.fn(), policies: vi.fn(), providers: vi.fn(),
} }))

describe('insurance store', () => {
  beforeEach(() => { setActivePinia(createPinia()); vi.clearAllMocks() })

  it('loads dashboard, policy, and provider data through the API client', async () => {
    vi.mocked(insuranceApi.dashboard).mockResolvedValue({ total_policies: 1, active_policies: 1, expired_policies: 0, expiring_soon_policies: 0, scheduled_policies: 0, claims: { total: 0, open: 0, approved: 0, paid: 0 }, uninsured_vehicles: { daily: 0, monthly: 0 }, expiring_policies: [], recent_claims: [] })
    vi.mocked(insuranceApi.policies).mockResolvedValue([])
    vi.mocked(insuranceApi.providers).mockResolvedValue([])
    const store = useInsuranceStore()
    await store.load({ status: 'active' })
    expect(store.dashboard?.total_policies).toBe(1)
    expect(store.policies).toEqual([])
    expect(insuranceApi.policies).toHaveBeenCalledWith({ status: 'active' })
    expect(store.loading).toBe(false)
  })

  it('retains an error state when an API request fails', async () => {
    vi.mocked(insuranceApi.dashboard).mockRejectedValue(new Error('Unavailable'))
    vi.mocked(insuranceApi.policies).mockResolvedValue([])
    vi.mocked(insuranceApi.providers).mockResolvedValue([])
    const store = useInsuranceStore()
    await expect(store.load()).rejects.toThrow('Unavailable')
    expect(store.error).toBe('Unavailable')
  })
})
