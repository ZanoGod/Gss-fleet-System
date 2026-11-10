import type { StatusMetric } from '@/components/common/statusMetrics'
import type { DailyBookingSummary } from './api'

export function dailyBookingMetrics(summary: DailyBookingSummary | null): StatusMetric[] {
  return [
    {
      key: 'total',
      label: 'Total Bookings',
      icon: 'bi-calendar-check',
      tone: 'info',
      value: summary?.total,
    },
    {
      key: 'pending',
      label: 'Pending',
      icon: 'bi-hourglass-split',
      tone: 'warning',
      value: summary?.pending,
    },
    {
      key: 'confirmed',
      label: 'Confirmed',
      icon: 'bi-check-circle',
      tone: 'success',
      value: summary?.confirmed,
    },
    {
      key: 'cancelled',
      label: 'Cancelled',
      icon: 'bi-x-circle',
      tone: 'danger',
      value: summary?.cancelled,
    },
  ]
}
