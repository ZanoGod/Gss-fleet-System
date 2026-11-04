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
      description: 'All drivers in the selected daily or monthly pool',
      value: summary?.total,
    },
    {
      key: 'available',
      label: 'Available',
      icon: 'bi-person-check',
      tone: 'success',
      description: 'Stored as available; date availability is checked on assignment',
      value: summary?.available,
    },
    {
      key: 'assigned',
      label: 'Assigned / On Trip',
      icon: 'bi-calendar-check',
      tone: 'info',
      description: 'Assigned and legacy On Trip stored statuses',
      value: summary?.assigned,
    },
    {
      key: 'leave',
      label: 'On Leave',
      icon: 'bi-calendar-x',
      tone: 'warning',
      description: 'Drivers with Leave stored status',
      value: summary?.leave,
    },
    {
      key: 'inactive',
      label: 'Inactive',
      icon: 'bi-person-dash',
      tone: 'danger',
      description: 'Drivers with Inactive stored status',
      value: summary?.inactive,
    },
  ]
}
