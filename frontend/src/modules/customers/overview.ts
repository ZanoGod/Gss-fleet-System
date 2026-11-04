import type { StatusMetric } from '@/components/common/statusMetrics'
import type { CustomerSummary } from './types'

export const customerHeader = {
  title: 'Customer directory',
  description: 'Customer profiles and their monthly contract assignments.',
  icon: 'bi-people',
}

export function customersMetrics(summary: CustomerSummary | null): StatusMetric[] {
  return [
    {
      key: 'total',
      label: 'Total Customers',
      icon: 'bi-people',
      tone: 'info',
      value: summary?.total,
    },
    {
      key: 'active',
      label: 'Active Customers',
      icon: 'bi-person-check',
      tone: 'success',
      value: summary?.active,
    },
    {
      key: 'with_contracts',
      label: 'With Contracts',
      icon: 'bi-file-earmark-text',
      tone: 'info',
      value: summary?.with_contracts,
    },
  ]
}
