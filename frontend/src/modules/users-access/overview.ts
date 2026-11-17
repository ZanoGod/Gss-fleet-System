import type { StatusMetric } from '@/components/common/statusMetrics'
import type { UserSummary } from './types'

export function usersMetrics(summary: UserSummary | null): StatusMetric[] {
  return [
    { key: 'total', label: 'Total users', icon: 'bi-people', tone: 'info', value: summary?.total },
    { key: 'active', label: 'Active users', icon: 'bi-person-check', tone: 'success', value: summary?.active },
    { key: 'inactive', label: 'Inactive users', icon: 'bi-person-slash', tone: 'warning', value: summary?.inactive },
    { key: 'roles', label: 'Roles', icon: 'bi-shield-check', tone: 'info', value: summary?.roles },
  ]
}
