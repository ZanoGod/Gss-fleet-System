import type { ComponentPublicInstance, InjectionKey } from 'vue'
import type {
  FinanceEntry,
  FinanceFilters,
  FinanceInvoice,
  FinanceOptions,
  FinancePayment,
  FinanceReport,
  FinanceTab,
} from './types'

export interface FinancePageContext {
  tab: FinanceTab
  summary: Record<string, any> | null
  filter: FinanceFilters
  exportCsv: () => void
  options: FinanceOptions
  loadAll: (initial?: boolean) => Promise<void>
  loading: boolean
  refreshing: boolean
  canManage: boolean
  money: (value: string | number | null | undefined, currency?: string) => string
  formatDate: (value?: string) => string
  customerName: (item: any) => string
  openEntry: (type: 'income' | 'expense', row?: FinanceEntry) => void
  visibleEntries: FinanceEntry[]
  total: number
  removeEntry: (row: FinanceEntry) => Promise<void>
  page: number
  perPage: number
  goToPage: (loader: (next: number) => Promise<void>, next: number) => Promise<void>
  loadEntries: (next?: number) => Promise<void>
  invoices: FinanceInvoice[]
  selectedInvoice: FinanceInvoice | null
  paymentsLoading: boolean
  payments: FinancePayment[]
  openInvoice: (row?: FinanceInvoice) => void
  openPayment: (invoice: FinanceInvoice, row?: FinancePayment) => void
  removePayment: (payment: FinancePayment) => Promise<void>
  loadPayments: (invoice: FinanceInvoice) => Promise<void>
  removeInvoice: (row: FinanceInvoice) => Promise<void>
  loadInvoices: (next?: number) => Promise<void>
  report: FinanceReport
  modal: 'entry' | 'invoice' | 'payment' | ''
  editingEntry: FinanceEntry | null
  editingPayment: FinancePayment | null
  editingInvoice: FinanceInvoice | null
  form: Record<string, any>
  saving: boolean
  closeModal: () => void
  errors: Record<string, string[]>
  errorTarget: (key: string) => string
  focusErrorField: (key: string) => void
  errorLabels: Record<string, string>
  fieldError: (key: string) => string | undefined
  updateInvoiceTotal: () => void
  selectContract: () => void
  selectMaintenance: () => void
  selectInsurance: () => void
  setVehicleFleet: () => void
  save: () => Promise<void>
  focusFirstError: () => Promise<void>
  setErrorSummary: (element: Element | ComponentPublicInstance | null) => void
}

export const financeContextKey: InjectionKey<FinancePageContext> = Symbol('finance-context')
