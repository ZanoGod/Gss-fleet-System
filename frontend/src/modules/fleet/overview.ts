import type { StatusMetric } from '@/components/common/statusMetrics'

export interface OverviewSummary {
  total: number
  available: number
  assigned: number
  maintenance: number
  inactive?: number
}

export function fleetMetrics(summary: OverviewSummary | null, scope: string): StatusMetric[] {
  const items: StatusMetric[] = [
    {
      key: 'total',
      label: 'Total Cars',
      icon: 'bi-car-front',
      tone: 'info',
      value: summary?.total,
    },
    {
      key: 'available',
      label: 'Available',
      icon: 'bi-check-circle',
      tone: 'success',
      value: summary?.available,
    },
    {
      key: 'assigned',
      label: 'Assigned',
      icon: 'bi-calendar-check',
      tone: 'info',
      value: summary?.assigned,
    },
    {
      key: 'maintenance',
      label: 'Maintenance',
      icon: 'bi-wrench-adjustable',
      tone: 'warning',
      value: summary?.maintenance,
    },
    {
      key: 'inactive',
      label: 'Inactive',
      icon: 'bi-pause-circle',
      tone: 'danger',
      value: summary?.inactive,
    },
  ]
  return items.filter((item) => item.key !== 'inactive' || scope === 'monthly')
}
