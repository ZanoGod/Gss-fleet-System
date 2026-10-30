export interface CustomerInput {
  company_name: string
  guest_name: string
  contact_person: string
  contact_phone: string
  contact_email: string
  customer_address: string
  off_day: string
  service_hour: string
  remark: string
}
export interface CustomerContract {
  id: number
  contract_no: string
  guest_name: string | null
  contact_person: string | null
  assignment_type: string
  rental_status: string
  status: string
  start_date: string
  end_date: string
  car_type: string | null
  plate_no: string | null
  driver_name: string | null
  operator_name: string | null
}
export interface Customer {
  id: number
  company_name: string | null
  guest_name: string | null
  contact_person: string | null
  contact_phone: string | null
  contact_email: string | null
  customer_address: string | null
  off_day: string | null
  service_hour: string | null
  remark: string | null
  customer_status: 'Active' | 'Inactive'
  contract_count: number
  active_contract_count: number
  latest_expiry: string | null
  can_delete: boolean
  contracts?: CustomerContract[]
}
export interface CustomerFilters {
  search: string
  status: string
  assignment_type: string
  rental_status: string
  expiry: string
}
export interface CustomerPage {
  data: Customer[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}
export interface CustomerSummary { total: number; active: number; with_contracts: number }
export const assignmentTypes = ['Driver Only', 'Vehicle Only', 'Vehicle and Driver']
export const rentalStatuses = ['Including fuel', 'Excluding fuel', 'Car only', 'Driver only']
export const offDays = ['Public Holidays', 'Sunday & Public Holidays', 'Sat, Sun & Public Holidays', 'Sat or Sun & Public Holidays', 'Once a week']
export function currentMonth(): string {
  const today = new Date()
  return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`
}
export function shiftMonth(month: string, amount: number): string {
  const [year, value] = month.split('-').map(Number)
  const date = new Date(year!, value! - 1 + amount, 1)
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
}
