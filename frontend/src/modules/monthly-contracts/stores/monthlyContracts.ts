import { defineStore } from 'pinia'
import { ref } from 'vue'
import { monthlyContractApi } from '../api'
import type { ContractOptions, ContractSummary, MonthlyContract } from '../types'
export const useMonthlyContractsStore = defineStore('monthlyContracts', () => {
  const rows = ref<MonthlyContract[]>([]),
    options = ref<ContractOptions | null>(null),
    summary = ref<ContractSummary | null>(null),
    page = ref(1),
    lastPage = ref(1),
    total = ref(0),
    loading = ref(false),
    error = ref('')
  let request = 0
  async function load(params: Record<string, string | number | undefined> = {}) {
    const version = ++request
    loading.value = true
    error.value = ''
    try {
      const [result, stats] = await Promise.all([
        monthlyContractApi.list(params),
        monthlyContractApi.summary({
          date_from:
            typeof params.date_from === 'string' && params.date_from ? params.date_from : undefined,
          date_to:
            typeof params.date_to === 'string' && params.date_to ? params.date_to : undefined,
        }),
      ])
      if (version !== request) return
      if (result.current_page > result.last_page) {
        await load({ ...params, page: Math.max(1, result.last_page) })
        return
      }
      rows.value = result.data
      page.value = result.current_page
      lastPage.value = result.last_page
      total.value = result.total
      summary.value = stats
    } catch (e) {
      if (version !== request) return
      error.value = e instanceof Error ? e.message : 'Unable to load contracts.'
      throw e
    } finally {
      if (version === request) loading.value = false
    }
  }
  async function loadOptions() {
    options.value = await monthlyContractApi.options()
  }
  return { rows, options, summary, page, lastPage, total, loading, error, load, loadOptions }
})
