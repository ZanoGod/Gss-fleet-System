export type FinanceTab = 'overview' | 'income' | 'expenses' | 'invoices' | 'reports'
export type FinanceEntryType = 'income' | 'expense'
export type FinanceModalType = 'entry' | 'invoice' | 'payment' | ''

export interface FinanceEntry extends Record<string, any> {
  id: number
  type: FinanceEntryType
  amount: string
  currency: string
}

export interface FinanceInvoice extends Record<string, any> {
  id: number
  invoice_number: string
  total: string
  currency: string
  paid_amount?: string
  payments_sum_amount?: string
  payment_status?: string
  balance?: string
}

export interface FinancePayment extends Record<string, any> {
  id: number
}

export interface FinancePageData<T> {
  data: T[]
  meta: { total: number; current_page: number; last_page: number }
}

export interface FinanceFilters extends Record<string, string> {
  search: string
  status: string
  currency: string
  date_from: string
  date_to: string
}

export interface FinanceOptions {
  customers: any[]
  bookings: any[]
  contracts: any[]
  vehicles: any[]
  maintenance: any[]
  insurance: any[]
  income_categories: string[]
  expense_categories: string[]
  currencies: string[]
  payment_methods: string[]
}

export interface FinanceReport {
  by_category: any[]
  by_month: any[]
  by_vehicle: any[]
}
