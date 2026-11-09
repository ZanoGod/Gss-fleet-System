export interface StatusMetric {
  key: string
  label: string
  value: number | string | null | undefined
  icon: string
  tone: 'info' | 'success' | 'warning' | 'danger'
}
