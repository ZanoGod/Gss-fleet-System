import type { StatusMetric } from '@/components/common/statusMetrics'
import type { DailyBooking } from '@/modules/daily-bookings/types'

export interface DashboardSummary {
  total_bookings: number
  available_cars: number
  available_drivers: number
  ongoing_bookings: number
  expiring_contracts: number
  recent_bookings: DailyBooking[]
}

export function dashboardMetrics(summary: DashboardSummary | null): StatusMetric[] {
  return [
    {
      key: 'bookings',
      label: 'Total Bookings',
      value: summary?.total_bookings,
      icon: 'bi-calendar2-week',
      tone: 'info',
    },
    {
      key: 'cars',
      label: 'Available Cars',
      value: summary?.available_cars,
      icon: 'bi-car-front',
      tone: 'success',
    },
    {
      key: 'drivers',
      label: 'Available Drivers',
      value: summary?.available_drivers,
      icon: 'bi-person-check',
      tone: 'success',
    },
    {
      key: 'ongoing',
      label: 'Ongoing',
      value: summary?.ongoing_bookings,
      icon: 'bi-arrow-repeat',
      tone: 'info',
    },
    {
      key: 'contracts',
      label: 'Monthly Contracts',
      value: summary?.expiring_contracts,
      icon: 'bi-calendar-event',
      tone: 'warning',
    },
  ]
}
