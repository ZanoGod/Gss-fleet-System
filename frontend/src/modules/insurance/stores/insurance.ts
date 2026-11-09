import { defineStore } from 'pinia'
import { ref } from 'vue'
import { insuranceApi } from '../api'
import type { InsuranceDashboard, InsurancePolicy, InsuranceProvider } from '../types'

export const useInsuranceStore = defineStore('insurance', () => {
  const dashboard = ref<InsuranceDashboard | null>(null)
  const policies = ref<InsurancePolicy[]>([])
  const providers = ref<InsuranceProvider[]>([])
  const loading = ref(false)
  const error = ref('')
  let request = 0
  async function load(params: Record<string, string | number | undefined> = {}) {
    const version = ++request
    loading.value = true
    error.value = ''
    try {
      const [stats, rows, providerRows] = await Promise.all([
        insuranceApi.dashboard(),
        insuranceApi.policies(params),
        insuranceApi.providers(),
      ])
      if (version !== request) return
      dashboard.value = stats
      policies.value = rows
      providers.value = providerRows
    } catch (e) {
      if (version !== request) return
      error.value = e instanceof Error ? e.message : 'Unable to load insurance records.'
      throw e
    } finally {
      if (version === request) loading.value = false
    }
  }
  return { dashboard, policies, providers, loading, error, load }
})
