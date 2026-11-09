import type { StatusMetric } from '@/components/common/statusMetrics'

export interface OverviewSummary {
  total: number
  available: number
  assigned: number
  leave: number
  inactive: number
}

export function driversMetrics(summary: OverviewSummary | null): StatusMetric[] {
  return [
    {
      key: 'total',
      label: 'Total Drivers',
      icon: 'bi-person-vcard',
      tone: 'info',
      value: summary?.total,
    },
    {
      key: 'available',
      label: 'Available',
      icon: 'bi-person-check',
      tone: 'success',
      value: summary?.available,
    },
    {
      key: 'assigned',
      label: 'Assigned / On Trip',
      icon: 'bi-calendar-check',
      tone: 'info',
      value: summary?.assigned,
    },
    {
      key: 'leave',
      label: 'On Leave',
      icon: 'bi-calendar-x',
      tone: 'warning',
      value: summary?.leave,
    },
    {
      key: 'inactive',
      label: 'Inactive',
      icon: 'bi-person-dash',
      tone: 'danger',
      value: summary?.inactive,
    },
  ]
}
