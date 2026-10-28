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
  async function load(params: Record<string, string | number | undefined> = {}) {
    loading.value = true; error.value = ''
    try {
      const [stats, rows, providerRows] = await Promise.all([insuranceApi.dashboard(), insuranceApi.policies(params), insuranceApi.providers()])
      dashboard.value = stats; policies.value = rows; providers.value = providerRows
    } catch (e) { error.value = e instanceof Error ? e.message : 'Unable to load insurance records.'; throw e }
    finally { loading.value = false }
  }
  return { dashboard, policies, providers, loading, error, load }
})
