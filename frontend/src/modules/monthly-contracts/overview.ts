import type { StatusMetric } from '@/components/common/statusMetrics'
import type { ContractSummary } from './types'

export const contractHeader = {
  title: 'Contract overview',
  description: 'Track agreements, rental periods and upcoming renewals.',
  icon: 'bi-calendar3',
}

export function contractMetrics(summary: ContractSummary | null): StatusMetric[] {
  // The API summary follows the date range, independently of table status/search filters.
  const statuses: { key: string; icon: string; tone: StatusMetric['tone'] }[] = [
    { key: 'Active', icon: 'bi-check-circle', tone: 'success' },
    { key: 'Expired', icon: 'bi-calendar-x', tone: 'danger' },
    { key: 'Renewed', icon: 'bi-arrow-repeat', tone: 'success' },
    { key: 'Cancelled', icon: 'bi-x-circle', tone: 'warning' },
  ]
  return [
    {
      key: 'total',
      label: 'Total Contracts',
      value: summary?.total,
      icon: 'bi-file-earmark-text',
      tone: 'info',
      description: 'All contracts in the selected date range',
    },
    ...statuses.map(({ key, icon, tone }) => ({
      key,
      label: key,
      icon,
      tone,
      value: summary ? (summary.statuses[key] ?? 0) : null,
      description: `${key} contracts in the selected date range`,
    })),
  ]
}
