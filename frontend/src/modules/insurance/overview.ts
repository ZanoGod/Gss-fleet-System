import type { StatusMetric } from '@/components/common/statusMetrics'
import type { InsuranceDashboard } from './types'

export const insuranceHeader = {
  title: 'Fleet coverage',
  description: 'Keep policies, claims and insurance providers in one place.',
  icon: 'bi-shield-check',
}

export function insuranceMetrics(summary: InsuranceDashboard | null): StatusMetric[] {
  return [
    {
      key: 'total',
      label: 'Total policies',
      value: summary?.total_policies,
      icon: 'bi-shield-check',
      tone: 'info',
      description: 'All recorded insurance policies',
    },
    {
      key: 'active',
      label: 'Active',
      value: summary?.active_policies,
      icon: 'bi-check-circle',
      tone: 'success',
      description: 'Policies currently providing coverage',
    },
    {
      key: 'expiring',
      label: 'Expiring soon',
      value: summary?.expiring_soon_policies,
      icon: 'bi-alarm',
      tone: 'warning',
      description: 'Coverage within the configured renewal window',
    },
    {
      key: 'claims',
      label: 'Open claims',
      value: summary?.claims.open,
      icon: 'bi-clipboard2-pulse',
      tone: 'danger',
      description: 'Submitted claims and claims under review',
    },
  ]
}
