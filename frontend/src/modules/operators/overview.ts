import type { StatusMetric } from '@/components/common/statusMetrics'

export interface OverviewSummary {
  total: number
  active: number
  inactive: number
}

export function operatorsMetrics(summary: OverviewSummary | null): StatusMetric[] {
  return [
    {
      key: 'total',
      label: 'Total Operators',
      icon: 'bi-headset',
      tone: 'info',
      value: summary?.total,
    },
    {
      key: 'active',
      label: 'Active Operators',
      icon: 'bi-person-check',
      tone: 'success',
      value: summary?.active,
    },
    {
      key: 'inactive',
      label: 'Inactive Operators',
      icon: 'bi-person-dash',
      tone: 'warning',
      value: summary?.inactive,
    },
  ]
}
