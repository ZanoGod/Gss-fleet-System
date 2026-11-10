export interface Assignment {
  id: number
  name: string
  start_date: string
  end_date: string
  status: string
  resource_type?: string
  vehicle?: string | null
  secondary_vehicle?: string | null
  driver?: string | null
  driver_id?: number | null
}

export interface ResourceRow {
  id: number
  [key: string]: unknown
  assignments?: Assignment[]
  maintenances?: { id: number; title: string; scheduled_date: string; status: string }[]
  insurance_policies?: { id: number }[]
}

export interface Field {
  key: string
  label: string
  type?: string
  required?: boolean
  max?: number
  min?: number
  maxNumber?: number
  step?: string
  options?: string[]
  default?: string | number
  placeholder?: string
}

export interface ResourceConfig {
  title: string
  singular: string
  path: string
  endpoint: string
  summaryEndpoint: string
  icon: string
  description: string
  formHelp: string
  statusKey: string
  statuses: string[]
  fields: Field[]
  filters: Field[]
  columns: { key: string; label: string }[]
}
