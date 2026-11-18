<script setup lang="ts">
import {
  computed,
  nextTick,
  onMounted,
  provide,
  proxyRefs,
  reactive,
  ref,
  watch,
  type ComponentPublicInstance,
} from 'vue'
import { financeContextKey, type FinancePageContext } from '../financeContext'
import { financeApi } from '../api'
import type { FinanceEntry, FinanceInvoice, FinancePayment } from '../types'
import '../components/financePanels.css'
import FinanceOverviewPanel from '../components/FinanceOverviewPanel.vue'
import FinanceEntriesPanel from '../components/FinanceEntriesPanel.vue'
import FinanceInvoicesPanel from '../components/FinanceInvoicesPanel.vue'
import FinanceReportsPanel from '../components/FinanceReportsPanel.vue'
import FinanceRecordModal from '../components/FinanceRecordModal.vue'
import axios from 'axios'
import { getApiErrorMessage } from '@/services/apiErrors'
import { toast } from '@/services/toast'
import { useAuthStore } from '@/stores/auth'
import '@/components/common/moduleGlass.css'
import { navigateSegments } from '@/components/common/navigateSegments'
import StatusSummary from '@/components/common/StatusSummary.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'

type Entry = FinanceEntry
type Invoice = FinanceInvoice
type Payment = FinancePayment
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission?.('finance.manage') ?? true)
const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'income', label: 'Income' },
  { key: 'expenses', label: 'Expenses' },
  { key: 'invoices', label: 'Invoices & Payments' },
  { key: 'reports', label: 'Finance Reports' },
] as const
type Tab = (typeof tabs)[number]['key']
const tab = ref<Tab>('overview')
const activeTabIndex = computed(() => tabs.findIndex((item) => item.key === tab.value))
const loading = ref(true)
const refreshing = ref(false)
const saving = ref(false)
const error = ref('')
const summary = ref<any>(null)
const options = ref<any>({
  customers: [],
  bookings: [],
  contracts: [],
  vehicles: [],
  maintenance: [],
  insurance: [],
  income_categories: [],
  expense_categories: [],
  currencies: ['MMK', 'USD'],
  payment_methods: [],
})
const optionsLoaded = ref(false)
const entries = ref<Entry[]>([])
const invoices = ref<Invoice[]>([])
const payments = ref<Payment[]>([])
const paymentsLoading = ref(false)
const selectedInvoice = ref<Invoice | null>(null)
const total = ref(0)
const page = ref(1)
const report = ref<any>({ by_category: [], by_month: [], by_vehicle: [] })
const filter = reactive({ search: '', status: '', currency: '', date_from: '', date_to: '' })
const modal = ref<'entry' | 'invoice' | 'payment' | ''>('')
const editingEntry = ref<Entry | null>(null)
const editingInvoice = ref<Invoice | null>(null)
const editingPayment = ref<Payment | null>(null)
const errors = ref<Record<string, string[]>>({})
const errorSummary = ref<HTMLElement | null>(null)
const form = reactive<Record<string, any>>({})
const perPage = 10

const params = () => Object.fromEntries(Object.entries(filter).filter(([, value]) => value))
const money = (value: string | number | null | undefined, currency = 'MMK') =>
  `${currency} ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
const customerName = (item: any) =>
  item?.company_name || item?.guest_name || item?.customer_name || '—'
const formatDate = (value?: string) =>
  value ? new Date(`${value}T00:00:00`).toLocaleDateString() : '—'
const metrics = computed(() => {
  const totals = summary.value?.totals || []
  const currency = filter.currency || totals[0]?.currency || 'MMK'
  const row = totals.find((item: any) => item.currency === currency) || {
    income: 0,
    expenses: 0,
    net: 0,
  }
  const receivable =
    summary.value?.receivables?.find((item: any) => item.currency === currency)?.outstanding || 0
  return [
    {
      key: 'income',
      label: 'Income',
      value: money(row.income, currency),
      icon: 'bi-arrow-down-left-circle',
      tone: 'success' as const,
    },
    {
      key: 'expenses',
      label: 'Expenses',
      value: money(row.expenses, currency),
      icon: 'bi-arrow-up-right-circle',
      tone: 'warning' as const,
    },
    {
      key: 'net',
      label: 'Net income',
      value: money(row.net, currency),
      icon: 'bi-wallet2',
      tone: 'info' as const,
    },
    {
      key: 'receivable',
      label: 'Outstanding invoices',
      value: money(receivable, currency),
      icon: 'bi-receipt',
      tone: 'danger' as const,
    },
  ]
})
const visibleEntries = computed(() => entries.value)

async function loadOptions() {
  if (optionsLoaded.value) return
  options.value = await financeApi.options()
  optionsLoaded.value = true
}
async function loadDashboard() {
  summary.value = await financeApi.dashboard(params())
}
async function loadEntries(next = 1) {
  const type = tab.value === 'expenses' ? 'expense' : tab.value === 'income' ? 'income' : ''
  const result = await financeApi.entries({
    ...params(),
    ...(type ? { type } : {}),
    page: next,
    per_page: perPage,
  })
  entries.value = result.data
  total.value = result.meta.total
  page.value = result.meta.current_page
  if (next > result.meta.last_page) await loadEntries(Math.max(1, result.meta.last_page))
}
async function loadInvoices(next = 1) {
  const result = await financeApi.invoices({
    ...params(),
    ...(filter.status ? { status: filter.status } : {}),
    page: next,
    per_page: perPage,
  })
  invoices.value = result.data
  total.value = result.meta.total
  page.value = result.meta.current_page
  if (next > result.meta.last_page) await loadInvoices(Math.max(1, result.meta.last_page))
}
async function goToPage(loader: (next: number) => Promise<void>, next: number) {
  refreshing.value = true
  error.value = ''
  try {
    await loader(next)
  } catch (cause) {
    error.value = getApiErrorMessage(cause)
  } finally {
    refreshing.value = false
  }
}
async function loadReport() {
  report.value = await financeApi.report(params())
}
async function loadAll(initial = false) {
  error.value = ''
  initial ? (loading.value = true) : (refreshing.value = true)
  try {
    await Promise.all([loadOptions(), loadDashboard()])
    if (tab.value === 'income' || tab.value === 'expenses') await loadEntries()
    if (tab.value === 'invoices') await loadInvoices()
    if (tab.value === 'reports') await loadReport()
  } catch (cause) {
    error.value = getApiErrorMessage(cause)
  } finally {
    loading.value = false
    refreshing.value = false
  }
}
watch(tab, () => {
  page.value = 1
  void loadAll(true)
})
onMounted(() => void loadAll(true))

function clearErrors() {
  errors.value = {}
}
function openEntry(type: 'income' | 'expense', row?: Entry) {
  editingEntry.value = row || null
  editingInvoice.value = null
  editingPayment.value = null
  Object.assign(form, {
    type: row?.type || type,
    category: row?.category || (type === 'income' ? 'Daily rental' : 'Maintenance'),
    description: row?.description || '',
    amount: row?.amount || '',
    currency: row?.currency || 'MMK',
    entry_date: row?.entry_date || new Date().toISOString().slice(0, 10),
    status: row?.status || 'posted',
    payment_method: row?.payment_method || '',
    reference: row?.reference || '',
    customer_id: row?.customer_id || '',
    daily_booking_id: row?.daily_booking_id || '',
    monthly_contract_id: row?.monthly_contract_id || '',
    vehicle_fleet: row?.vehicle_fleet || '',
    vehicle_id: row?.vehicle_id || '',
    maintenance_id: row?.maintenance_id || '',
    insurance_policy_id: row?.insurance_policy_id || '',
    notes: row?.notes || '',
    create_invoice: false,
    invoice_due_date: '',
  })
  clearErrors()
  modal.value = 'entry'
}
function openInvoice(row?: Invoice) {
  editingInvoice.value = row || null
  editingEntry.value = null
  editingPayment.value = null
  Object.assign(form, {
    customer_id: row?.customer_id || '',
    daily_booking_id: row?.daily_booking_id || '',
    monthly_contract_id: row?.monthly_contract_id || '',
    description: row?.description || '',
    issue_date: row?.issue_date || new Date().toISOString().slice(0, 10),
    due_date: row?.due_date || new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10),
    subtotal: row?.subtotal || '',
    tax_amount: row?.tax_amount || '0',
    total: row?.total || '',
    currency: row?.currency || 'MMK',
    status: row?.status || 'sent',
    notes: row?.notes || '',
  })
  clearErrors()
  modal.value = 'invoice'
}
function openPayment(invoice: Invoice, row?: Payment) {
  selectedInvoice.value = invoice
  editingPayment.value = row || null
  editingInvoice.value = null
  editingEntry.value = null
  Object.assign(form, {
    payment_date: row?.payment_date || new Date().toISOString().slice(0, 10),
    amount:
      row?.amount ||
      invoice.balance ||
      Number(invoice.total) - Number(invoice.payments_sum_amount || 0),
    payment_method: row?.payment_method || 'Bank transfer',
    reference: row?.reference || '',
    notes: row?.notes || '',
  })
  clearErrors()
  modal.value = 'payment'
}
function closeModal() {
  modal.value = ''
  saving.value = false
}
async function save() {
  if (saving.value) return
  saving.value = true
  clearErrors()
  try {
    let payload = { ...form }
    if (modal.value === 'entry') {
      for (const key of [
        'customer_id',
        'daily_booking_id',
        'monthly_contract_id',
        'vehicle_id',
        'maintenance_id',
        'insurance_policy_id',
      ])
        payload[key] = payload[key] ? Number(payload[key]) : null
      if (!payload.vehicle_fleet) payload.vehicle_fleet = null
      await financeApi.saveEntry(payload, editingEntry.value?.id)
    } else if (modal.value === 'invoice') {
      for (const key of ['customer_id', 'daily_booking_id', 'monthly_contract_id'])
        payload[key] = payload[key] ? Number(payload[key]) : null
      await financeApi.saveInvoice(payload, editingInvoice.value?.id)
    } else if (selectedInvoice.value) {
      await financeApi.savePayment(selectedInvoice.value.id, payload, editingPayment.value?.id)
    }
    toast.success(
      modal.value === 'payment'
        ? 'Payment saved.'
        : modal.value === 'invoice'
          ? 'Invoice saved.'
          : 'Finance record saved.',
    )
    closeModal()
    await loadAll()
    if (selectedInvoice.value && modal.value !== 'payment')
      await loadPayments(selectedInvoice.value)
  } catch (cause) {
    const message = getApiErrorMessage(cause)
    toast.error(message)
    if (axios.isAxiosError(cause))
      errors.value = cause.response?.data?.errors || cause.response?.data?.error?.errors || {}
    await focusFirstError()
  } finally {
    saving.value = false
  }
}
async function removeEntry(row: Entry) {
  if (!window.confirm(`Delete this ${row.type} record?`)) return
  try {
    await financeApi.deleteEntry(row.id)
    toast.success('Finance record deleted.')
    await loadAll()
  } catch (cause) {
    toast.error(getApiErrorMessage(cause))
  }
}
async function removeInvoice(row: Invoice) {
  if (!window.confirm(`Delete invoice ${row.invoice_number}?`)) return
  try {
    await financeApi.deleteInvoice(row.id)
    toast.success('Invoice deleted.')
    await loadAll()
  } catch (cause) {
    toast.error(getApiErrorMessage(cause))
  }
}
async function loadPayments(invoice: Invoice) {
  selectedInvoice.value = invoice
  paymentsLoading.value = true
  try {
    payments.value = await financeApi.payments(invoice.id)
  } catch (cause) {
    toast.error(getApiErrorMessage(cause))
  } finally {
    paymentsLoading.value = false
  }
}
async function removePayment(payment: Payment) {
  if (!selectedInvoice.value || !window.confirm('Delete this payment?')) return
  try {
    await financeApi.deletePayment(selectedInvoice.value.id, payment.id)
    toast.success('Payment deleted.')
    await loadPayments(selectedInvoice.value)
    await loadInvoices(page.value)
  } catch (cause) {
    toast.error(getApiErrorMessage(cause))
  }
}
function exportCsv() {
  window.location.href = financeApi.exportUrl(params())
}
function updateInvoiceTotal() {
  form.total = (Number(form.subtotal || 0) + Number(form.tax_amount || 0)).toFixed(2)
}
function setVehicleFleet() {
  const vehicle = options.value.vehicles.find(
    (item: any) => String(item.id) === String(form.vehicle_id),
  )
  form.vehicle_fleet = vehicle?.fleet || ''
}
function selectMaintenance() {
  const item = options.value.maintenance.find(
    (row: any) => String(row.id) === String(form.maintenance_id),
  )
  if (!item) return
  form.category = 'Maintenance'
  form.amount = item.actual_cost || form.amount
  form.currency = item.currency || form.currency
  if (item.car_id) {
    form.vehicle_fleet = 'daily'
    form.vehicle_id = item.car_id
  }
  if (item.monthly_car_id) {
    form.vehicle_fleet = 'monthly'
    form.vehicle_id = item.monthly_car_id
  }
}
function selectInsurance() {
  const item = options.value.insurance.find(
    (row: any) => String(row.id) === String(form.insurance_policy_id),
  )
  if (!item) return
  form.category = 'Insurance'
  form.amount = item.premium_amount || form.amount
  form.currency = item.currency || form.currency
  if (item.car_id) {
    form.vehicle_fleet = 'daily'
    form.vehicle_id = item.car_id
  }
  if (item.monthly_car_id) {
    form.vehicle_fleet = 'monthly'
    form.vehicle_id = item.monthly_car_id
  }
}
function selectContract() {
  const contract = options.value.contracts.find(
    (row: any) => String(row.id) === String(form.monthly_contract_id),
  )
  if (!contract) return
  form.amount = contract.monthly_rate || form.amount
  form.currency = contract.rental_currency || form.currency
  form.customer_id = contract.customer_id || form.customer_id
}
function fieldError(key: string) {
  return errors.value[key]?.[0]
}
const errorLabels: Record<string, string> = {
  type: 'Record type',
  category: 'Category',
  description: 'Description',
  amount: 'Amount',
  currency: 'Currency',
  entry_date: 'Date',
  status: 'Status',
  payment_method: 'Payment method',
  customer_id: 'Customer',
  daily_booking_id: 'Daily booking',
  monthly_contract_id: 'Monthly contract',
  vehicle_fleet: 'Vehicle fleet',
  vehicle_id: 'Vehicle',
  maintenance_id: 'Maintenance work order',
  insurance_policy_id: 'Insurance policy',
  reference: 'Reference',
  notes: 'Notes',
  invoice_due_date: 'Invoice due date',
  issue_date: 'Issue date',
  due_date: 'Due date',
  subtotal: 'Subtotal',
  tax_amount: 'Tax',
  total: 'Total',
  payment_date: 'Payment date',
}
function errorTarget(key: string) {
  if (key === 'amount')
    return modal.value === 'payment'
      ? 'payment-amount'
      : modal.value === 'invoice'
        ? 'invoice-total'
        : 'entry-amount'
  if (key === 'description')
    return modal.value === 'invoice' ? 'invoice-description-form' : 'entry-description'
  const targets: Record<string, string> = {
    category: 'entry-category',
    currency: modal.value === 'invoice' ? 'invoice-currency-form' : 'entry-currency',
    entry_date: 'entry-date',
    status: 'entry-status',
    payment_method: modal.value === 'payment' ? 'payment-method' : 'entry-method',
    customer_id: modal.value === 'invoice' ? 'invoice-customer-form' : 'entry-customer',
    daily_booking_id: modal.value === 'invoice' ? 'invoice-booking-form' : 'entry-booking',
    monthly_contract_id: modal.value === 'invoice' ? 'invoice-contract-form' : 'entry-contract',
    vehicle_id: 'entry-vehicle',
    maintenance_id: 'entry-maintenance',
    insurance_policy_id: 'entry-insurance',
    issue_date: 'invoice-issue',
    due_date: modal.value === 'invoice' ? 'invoice-due' : 'invoice-due-date',
    subtotal: 'invoice-subtotal',
    tax_amount: 'invoice-tax',
    total: 'invoice-total',
    payment_date: 'payment-date',
    reference: modal.value === 'payment' ? 'payment-reference' : 'entry-reference',
    notes:
      modal.value === 'payment'
        ? 'payment-notes'
        : modal.value === 'invoice'
          ? 'invoice-notes'
          : 'entry-notes',
  }
  return targets[key] || 'finance-record-form'
}
async function focusFirstError() {
  await nextTick()
  errorSummary.value?.focus()
}
provide(
  financeContextKey,
  proxyRefs({
    tab,
    summary,
    filter,
    exportCsv,
    options,
    loadAll,
    loading,
    formatDate,
    money,
    refreshing,
    canManage,
    openEntry,
    total,
    visibleEntries,
    customerName,
    removeEntry,
    page,
    perPage,
    goToPage,
    loadEntries,
    invoices,
    selectedInvoice,
    paymentsLoading,
    payments,
    openInvoice,
    openPayment,
    removePayment,
    loadPayments,
    removeInvoice,
    loadInvoices,
    report,
    modal,
    editingEntry,
    editingPayment,
    form,
    saving,
    closeModal,
    errors,
    errorTarget,
    focusErrorField,
    errorLabels,
    fieldError,
    editingInvoice,
    updateInvoiceTotal,
    selectContract,
    selectMaintenance,
    selectInsurance,
    setVehicleFleet,
    save,
    focusFirstError,
    setErrorSummary: (element: Element | ComponentPublicInstance | null) => {
      errorSummary.value = element instanceof HTMLElement ? element : null
    },
  }) as FinancePageContext,
)
function focusErrorField(key: string) {
  document.getElementById(errorTarget(key))?.focus()
}
</script>

<template>
  <section class="fleet-module page-content glass-module finance-module">
    <StatusSummary :items="metrics" :loading="loading" label="Finance summary" />
    <div
      class="glass-segments glass-segments--five finance-tabs"
      role="group"
      aria-label="Finance sections"
      :data-active-index="activeTabIndex"
      @keydown="navigateSegments"
    >
      <button
        v-for="item in tabs"
        :key="item.key"
        type="button"
        :aria-pressed="tab === item.key"
        @click="tab = item.key"
      >
        {{ item.label }}
      </button>
    </div>
    <BaseLoading v-if="refreshing" class="d-block mb-3" label="Updating finance data…" />
    <div
      v-if="error"
      class="alert alert-danger d-flex justify-content-between align-items-center"
      role="alert"
    >
      <span>{{ error }}</span>
      <button class="btn btn-outline-danger" :disabled="refreshing" @click="loadAll()">
        <BaseLoading v-if="refreshing" label="Retrying…" /><template v-else>Retry</template>
      </button>
    </div>
    <FinanceOverviewPanel v-if="tab === 'overview'" />
    <FinanceEntriesPanel v-else-if="tab === 'income' || tab === 'expenses'" />
    <FinanceInvoicesPanel v-else-if="tab === 'invoices'" />
    <FinanceReportsPanel v-else />

    <FinanceRecordModal />
  </section>
</template>

<style scoped>
.finance-module :deep(.btn) {
  margin-bottom: 0;
}
.finance-tabs {
  width: min(100%, 920px);
  margin-bottom: 1.25rem;
}
.finance-module .finance-tabs :deep(button) {
  min-height: 44px;
  padding: 0.45rem 0.5rem;
  white-space: nowrap;
}
@media (max-width: 767.98px) {
  .finance-tabs {
    width: 100%;
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: thin;
  }
  .finance-tabs::before {
    display: none;
  }
  .finance-module .finance-tabs :deep(button) {
    flex: 0 0 auto;
    width: auto;
    min-width: max-content;
    padding-inline: 0.75rem;
  }
  .finance-tabs :deep(button[aria-pressed='true']) {
    color: var(--gss-button-default-text);
    border-color: var(--gss-button-primary-border);
    background: var(--gss-button-primary-bg);
    box-shadow:
      inset 0 1px 0 rgb(255 255 255 / 78%),
      0 3px 9px var(--gss-shadow-tint-soft);
  }
}
@media (prefers-reduced-motion: reduce) {
  .finance-tabs::before {
    transition: none;
  }
}
</style>
