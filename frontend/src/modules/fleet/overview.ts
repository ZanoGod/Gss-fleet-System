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
      description: 'Vehicles matching the current filters',
      value: summary?.total,
    },
    {
      key: 'available',
      label: 'Available',
      icon: 'bi-check-circle',
      tone: 'success',
      description: 'Stored as available; date availability is checked on assignment',
      value: summary?.available,
    },
    {
      key: 'assigned',
      label: 'Assigned',
      icon: 'bi-calendar-check',
      tone: 'info',
      description: 'Vehicles with Assigned stored status',
      value: summary?.assigned,
    },
    {
      key: 'maintenance',
      label: 'Maintenance',
      icon: 'bi-wrench-adjustable',
      tone: 'warning',
      description: 'Vehicles with Maintenance stored status',
      value: summary?.maintenance,
    },
    {
      key: 'inactive',
      label: 'Inactive',
      icon: 'bi-pause-circle',
      tone: 'danger',
      description: 'Inactive monthly vehicles',
      value: summary?.inactive,
    },
  ]
  return items.filter((item) => item.key !== 'inactive' || scope === 'monthly')
}
