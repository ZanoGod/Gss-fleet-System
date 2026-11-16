import type { StatusMetric } from '@/components/common/statusMetrics'
import type { LicenseDashboard } from './types'

export function carLicenseMetrics(summary: LicenseDashboard | null): StatusMetric[] {
  return [
    { key: 'current', label: 'Current licenses', value: summary?.total, icon: 'bi-card-checklist', tone: 'info' },
    { key: 'active', label: 'Active', value: summary?.active, icon: 'bi-shield-check', tone: 'success' },
    { key: 'soon', label: 'Expiring within 30 days', value: summary?.expiring_soon, icon: 'bi-hourglass-split', tone: 'warning' },
    { key: 'expired', label: 'Expired', value: summary?.expired, icon: 'bi-exclamation-octagon', tone: 'danger' },
  ]
}
