export type LicenseStatus = 'Active' | 'Expiring Soon' | 'Expired'
export interface LicenseVehicle {
  id: number
  fleet: 'daily' | 'monthly'
  plate_no: string
  model_name: string
}
export interface LicenseDocument {
  id: number
  original_name: string
  mime_type: string
  size: number
  download_url: string
}
export interface LicenseHistory {
  id: number
  license_number: string
  issue_date: string
  expiry_date: string
  renewal_date: string | null
  renewal_fee: string
  status: LicenseStatus
}
export interface CarLicense {
  id: number
  car_id: number | null
  monthly_car_id: number | null
  vehicle: LicenseVehicle | null
  vehicle_label: string | null
  license_number: string
  license_period: number
  issue_date: string
  expiry_date: string
  renewal_date: string | null
  renewal_fee: string
  office_region: string
  office_branch: string
  remarks: string | null
  status: LicenseStatus
  days_until_expiry: number
  is_current: boolean
  renewal_parent_id: number | null
  documents?: LicenseDocument[]
  renewal_history?: LicenseHistory[]
}
export interface LicenseVehicleOption extends LicenseVehicle {
  value: string
}
export interface LicenseDashboard {
  total: number
  active: number
  expiring_soon: number
  expired: number
  alerts: CarLicense[]
  expired_alerts: CarLicense[]
}
