import type { StatusMetric } from '@/components/common/statusMetrics'

export interface MaintenanceDashboard {
  due_this_week: number
  overdue: number
  in_workshop: number
  completed_this_month: number
  completed_previous_month: number
  spend_this_month: { currency: string; total: string }[]
  month_label: string
  previous_month_label: string
}

export function maintenanceMetrics(summary: MaintenanceDashboard | null): StatusMetric[] {
  const spend = summary?.spend_this_month ?? []
  const change = summary ? summary.completed_this_month - summary.completed_previous_month : 0
  return [
    {
      key: 'due',
      label: 'Due this week',
      value: summary?.due_this_week,
      icon: 'bi-calendar-event',
      tone: 'warning',
    },
    {
      key: 'workshop',
      label: 'In workshop',
      value: summary?.in_workshop,
      icon: 'bi-wrench-adjustable',
      tone: 'danger',
    },
    {
      key: 'completed',
      label: 'Completed this month',
      value: summary?.completed_this_month,
      icon: 'bi-calendar2-check',
      tone: 'success',
    },
    {
      key: 'spend',
      label: 'Maintenance spend',
      icon: 'bi-cash',
      tone: 'info',
      // Never add amounts in different currencies together.
      value: !summary
        ? null
        : spend.length
          ? spend
              .map(
                (item) =>
                  `${item.currency} ${Number(item.total).toLocaleString(undefined, { maximumFractionDigits: 2 })}`,
              )
              .join(' · ')
          : 0,
    },
  ]
}
