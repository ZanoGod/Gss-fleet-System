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
      description: `${summary?.overdue ?? 0} services overdue (included)`,
    },
    {
      key: 'workshop',
      label: 'In workshop',
      value: summary?.in_workshop,
      icon: 'bi-wrench-adjustable',
      tone: 'danger',
      description: 'Maintenance jobs in progress; vehicles unavailable',
    },
    {
      key: 'completed',
      label: 'Completed this month',
      value: summary?.completed_this_month,
      icon: 'bi-calendar2-check',
      tone: 'success',
      description: `${change >= 0 ? '↑' : '↓'} ${Math.abs(change)} vs ${summary?.previous_month_label ?? 'previous month'}`,
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
      description: `${summary?.month_label ?? 'This month'} to date`,
    },
  ]
}
