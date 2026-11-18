<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import api from '@/services/api'
import { getApiErrorMessage } from '@/services/apiErrors'
import { toast } from '@/services/toast'
import { useAuthStore } from '@/stores/auth'
import '@/components/common/moduleGlass.css'
import { navigateSegments } from '@/components/common/navigateSegments'
import StatusSummary from '@/components/common/StatusSummary.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import BaseModal from '@/components/common/BaseModal.vue'

type Entry = Record<string, any> & { id: number; type: 'income' | 'expense'; amount: string }
type Invoice = Record<string, any> & {
  id: number
  invoice_number: string
  total: string
  currency: string
  paid_amount?: string
  payments_sum_amount?: string
  payment_status?: string
  balance?: string
}
type Payment = Record<string, any> & { id: number }
type PageData = { data: any[]; meta: { total: number; current_page: number; last_page: number } }
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
  options.value = (await api.get('/api/finance/options')).data.data
  optionsLoaded.value = true
}
async function loadDashboard() {
  summary.value = (await api.get('/api/finance/dashboard', { params: params() })).data.data
}
async function loadEntries(next = 1) {
  const type = tab.value === 'expenses' ? 'expense' : tab.value === 'income' ? 'income' : ''
  const result = (
    await api.get<PageData>('/api/finance/entries', {
      params: { ...params(), ...(type ? { type } : {}), page: next, per_page: perPage },
    })
  ).data
  entries.value = result.data
  total.value = result.meta.total
  page.value = result.meta.current_page
  if (next > result.meta.last_page) await loadEntries(Math.max(1, result.meta.last_page))
}
async function loadInvoices(next = 1) {
  const result = (
    await api.get<PageData>('/api/finance/invoices', {
      params: {
        ...params(),
        ...(filter.status ? { status: filter.status } : {}),
        page: next,
        per_page: perPage,
      },
    })
  ).data
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
  report.value = (await api.get('/api/finance/report', { params: params() })).data.data
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
      const url = editingEntry.value
        ? `/api/finance/entries/${editingEntry.value.id}`
        : '/api/finance/entries'
      await api.request({ method: editingEntry.value ? 'put' : 'post', url, data: payload })
    } else if (modal.value === 'invoice') {
      for (const key of ['customer_id', 'daily_booking_id', 'monthly_contract_id'])
        payload[key] = payload[key] ? Number(payload[key]) : null
      const url = editingInvoice.value
        ? `/api/finance/invoices/${editingInvoice.value.id}`
        : '/api/finance/invoices'
      await api.request({ method: editingInvoice.value ? 'put' : 'post', url, data: payload })
    } else if (selectedInvoice.value) {
      const url = `/api/finance/invoices/${selectedInvoice.value.id}/payments${editingPayment.value ? `/${editingPayment.value.id}` : ''}`
      await api.request({ method: editingPayment.value ? 'put' : 'post', url, data: payload })
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
    await api.delete(`/api/finance/entries/${row.id}`)
    toast.success('Finance record deleted.')
    await loadAll()
  } catch (cause) {
    toast.error(getApiErrorMessage(cause))
  }
}
async function removeInvoice(row: Invoice) {
  if (!window.confirm(`Delete invoice ${row.invoice_number}?`)) return
  try {
    await api.delete(`/api/finance/invoices/${row.id}`)
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
    payments.value = (await api.get(`/api/finance/invoices/${invoice.id}/payments`)).data.data
  } catch (cause) {
    toast.error(getApiErrorMessage(cause))
  } finally {
    paymentsLoading.value = false
  }
}
async function removePayment(payment: Payment) {
  if (!selectedInvoice.value || !window.confirm('Delete this payment?')) return
  try {
    await api.delete(`/api/finance/invoices/${selectedInvoice.value.id}/payments/${payment.id}`)
    toast.success('Payment deleted.')
    await loadPayments(selectedInvoice.value)
    await loadInvoices(page.value)
  } catch (cause) {
    toast.error(getApiErrorMessage(cause))
  }
}
function exportCsv() {
  const query = new URLSearchParams(params()).toString()
  window.location.href = `/api/finance/export.csv${query ? `?${query}` : ''}`
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
      <span>{{ error }}</span
      ><button class="btn btn-outline-danger" :disabled="refreshing" @click="loadAll()">
        <BaseLoading v-if="refreshing" label="Retrying…" /><template v-else>Retry</template>
      </button>
    </div>

    <template v-if="tab === 'overview'">
      <section class="card-shell p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <div>
            <h2 class="h5 mb-1">This month</h2>
            <p class="text-muted mb-0">Posted income and expenses, grouped by currency.</p>
          </div>
          <div class="finance-toolbar-actions">
            <button class="btn btn-outline-primary" type="button" @click="exportCsv">
              <i class="bi bi-download me-1" aria-hidden="true" /> Export CSV
            </button>
            <select
              v-model="filter.currency"
              class="form-select finance-currency-filter"
              aria-label="Finance currency"
              @change="loadAll()"
            >
              <option value="">All currencies</option>
              <option v-for="currency in options.currencies" :key="currency">{{ currency }}</option>
            </select>
          </div>
        </div>
        <PageSkeleton v-if="loading" label="Loading monthly finance" :columns="3" />
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Currency</th>
                <th>Income</th>
                <th>Expenses</th>
                <th>Net income</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in summary?.month_totals || []" :key="row.currency">
                <td>{{ row.currency }}</td>
                <td class="finance-number">{{ money(row.income, row.currency) }}</td>
                <td class="finance-number">{{ money(row.expenses, row.currency) }}</td>
                <td class="finance-number">
                  {{ money(Number(row.income) - Number(row.expenses), row.currency) }}
                </td>
              </tr>
              <tr v-if="!summary?.month_totals?.length">
                <td colspan="4" class="text-muted">No posted activity this month.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      <section class="card-shell">
        <div class="p-4"><h2 class="h5 mb-0">Recent activity</h2></div>
        <PageSkeleton v-if="loading" label="Loading recent activity" :columns="5" />
        <div v-else-if="summary?.recent_entries?.length" class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Category</th>
                <th class="text-end">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in summary.recent_entries" :key="row.id">
                <td>{{ formatDate(row.entry_date) }}</td>
                <td><BaseStatusBadge :status="row.type === 'income' ? 'Income' : 'Expense'" /></td>
                <td>{{ row.description }}</td>
                <td>{{ row.category }}</td>
                <td class="text-end finance-number">{{ money(row.amount, row.currency) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="p-4 text-muted mb-0">
          No finance records yet. Add income or an expense to start tracking fleet performance.
        </p>
      </section>
    </template>

    <template v-else-if="tab === 'income' || tab === 'expenses'">
      <section class="card-shell p-4 mb-4">
        <form class="row g-3" @submit.prevent="loadAll()">
          <div class="col-12 col-md-3">
            <label class="form-label" for="finance-search">Search</label
            ><input
              id="finance-search"
              v-model="filter.search"
              class="form-control"
              type="search"
              placeholder="Description, category, reference"
            />
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="entry-status-filter">Status</label
            ><select id="entry-status-filter" v-model="filter.status" class="form-select">
              <option value="">All</option>
              <option value="posted">Posted</option>
              <option value="draft">Draft</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="finance-currency">Currency</label
            ><select id="finance-currency" v-model="filter.currency" class="form-select">
              <option value="">All</option>
              <option v-for="currency in options.currencies" :key="currency">{{ currency }}</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="finance-from">From</label
            ><input id="finance-from" v-model="filter.date_from" class="form-control" type="date" />
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="finance-to">To</label
            ><input id="finance-to" v-model="filter.date_to" class="form-control" type="date" />
          </div>
          <div class="col-12 col-md-1 d-flex align-items-end gap-2">
            <button class="btn btn-primary" :disabled="refreshing">Filter</button>
          </div>
          <div class="col-12">
            <button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="refreshing"
              @click="
                Object.assign(filter, {
                  search: '',
                  currency: '',
                  date_from: '',
                  date_to: '',
                  status: '',
                });
                loadAll()
              "
            >
              Reset filters
            </button>
          </div>
        </form>
      </section>
      <section class="card-shell">
        <div class="finance-section-header p-4">
          <h2 class="h5 mb-0">
            {{ tab === 'income' ? 'Income records' : 'Expense records' }}
            <span class="text-muted">({{ total }})</span>
          </h2>
          <div class="finance-toolbar-actions">
            <button class="btn btn-outline-primary" type="button" @click="exportCsv">
              <i class="bi bi-download me-1" aria-hidden="true" /> Export CSV
            </button>
            <button
              v-if="canManage"
              class="btn btn-primary"
              type="button"
              @click="openEntry(tab === 'income' ? 'income' : 'expense')"
            >
              <i :class="`bi ${tab === 'income' ? 'bi-plus-circle' : 'bi-plus-circle'} me-1`" aria-hidden="true" />
              Add {{ tab === 'income' ? 'income' : 'expense' }}
            </button>
          </div>
        </div>
        <BaseLoading
          v-if="refreshing"
          class="d-block px-4 pb-2"
          label="Updating finance records…"
        /><PageSkeleton v-if="loading" label="Loading finance records" :columns="7" />
        <div v-else-if="visibleEntries.length" class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Customer / source</th>
                <th>Status</th>
                <th class="text-end">Amount</th>
                <th v-if="canManage">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in visibleEntries" :key="row.id">
                <td>{{ formatDate(row.entry_date) }}</td>
                <td>
                  {{ row.description
                  }}<small v-if="row.reference" class="d-block text-muted">{{
                    row.reference
                  }}</small>
                </td>
                <td>{{ row.category }}</td>
                <td>{{ customerName(row.customer) }}</td>
                <td><BaseStatusBadge :status="row.status" /></td>
                <td class="text-end finance-number">{{ money(row.amount, row.currency) }}</td>
                <td v-if="canManage">
                  <div class="d-flex gap-2">
                    <button
                      class="btn btn-sm btn-outline-primary"
                      @click="openEntry(row.type, row)"
                    >
                      Edit</button
                    ><button class="btn btn-sm btn-outline-danger" @click="removeEntry(row)">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else-if="!loading" class="p-4 text-muted mb-0">No records match these filters.</p>
        <div class="p-3">
          <BasePagination
            :page="page"
            :per-page="perPage"
            :total="total"
            :disabled="refreshing"
            @update:page="goToPage(loadEntries, $event)"
          />
        </div>
      </section>
    </template>

    <template v-else-if="tab === 'invoices'">
      <section class="card-shell p-4 mb-4">
        <form class="row g-3" @submit.prevent="loadAll()">
          <div class="col-12 col-md-4">
            <label class="form-label" for="invoice-search">Search invoices</label
            ><input
              id="invoice-search"
              v-model="filter.search"
              class="form-control"
              type="search"
              placeholder="Invoice number or description"
            />
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="invoice-status">Status</label
            ><select id="invoice-status" v-model="filter.status" class="form-select">
              <option value="">All</option>
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="void">Void</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="invoice-currency">Currency</label
            ><select id="invoice-currency" v-model="filter.currency" class="form-select">
              <option value="">All</option>
              <option v-for="currency in options.currencies" :key="currency">{{ currency }}</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="invoice-from">From</label
            ><input id="invoice-from" v-model="filter.date_from" class="form-control" type="date" />
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="invoice-to">To</label
            ><input id="invoice-to" v-model="filter.date_to" class="form-control" type="date" />
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" :disabled="refreshing">Filter</button
            ><button
              type="button"
              class="btn btn-outline-secondary"
              :disabled="refreshing"
              @click="
                Object.assign(filter, {
                  search: '',
                  status: '',
                  currency: '',
                  date_from: '',
                  date_to: '',
                });
                loadAll()
              "
            >
              Reset
            </button>
          </div>
        </form>
      </section>
      <section class="card-shell">
        <div class="finance-section-header p-4">
          <h2 class="h5 mb-0">
            Invoices <span class="text-muted">({{ total }})</span>
          </h2>
          <div class="finance-toolbar-actions">
            <button class="btn btn-outline-primary" type="button" @click="exportCsv">
              <i class="bi bi-download me-1" aria-hidden="true" /> Export CSV
            </button>
            <button v-if="canManage" class="btn btn-primary" type="button" @click="openInvoice()">
              <i class="bi bi-receipt me-1" aria-hidden="true" /> Create invoice
            </button>
          </div>
        </div>
        <PageSkeleton v-if="loading" label="Loading invoices" :columns="7" />
        <div v-else-if="invoices.length" class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Due</th>
                <th>Status</th>
                <th class="text-end">Total</th>
                <th class="text-end">Balance</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="row in invoices" :key="row.id"
                ><tr>
                  <td>
                    <strong>{{ row.invoice_number }}</strong
                    ><small class="d-block text-muted">{{ row.description }}</small>
                  </td>
                  <td>{{ customerName(row.customer) }}</td>
                  <td>{{ formatDate(row.due_date) }}</td>
                  <td><BaseStatusBadge :status="row.payment_status || row.status" /></td>
                  <td class="text-end finance-number">{{ money(row.total, row.currency) }}</td>
                  <td class="text-end finance-number">
                    {{
                      money(
                        row.balance ?? Number(row.total) - Number(row.payments_sum_amount || 0),
                        row.currency,
                      )
                    }}
                  </td>
                  <td>
                    <div class="d-flex flex-wrap gap-2">
                      <button class="btn btn-sm btn-outline-secondary" @click="loadPayments(row)">
                        Payments</button
                      ><button
                        v-if="canManage"
                        class="btn btn-sm btn-outline-success"
                        :disabled="row.payment_status === 'Paid' || row.status === 'void'"
                        @click="openPayment(row)"
                      >
                        Record payment</button
                      ><button
                        v-if="canManage"
                        class="btn btn-sm btn-outline-primary"
                        @click="openInvoice(row)"
                      >
                        Edit</button
                      ><button
                        v-if="canManage"
                        class="btn btn-sm btn-outline-danger"
                        @click="removeInvoice(row)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="selectedInvoice?.id === row.id" class="finance-payment-row">
                  <td :colspan="canManage ? 7 : 6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong>Payments for {{ row.invoice_number }}</strong
                      ><button
                        class="btn btn-sm btn-outline-secondary"
                        @click="
                          selectedInvoice = null;
                          payments = []
                        "
                      >
                        Close
                      </button>
                    </div>
                    <BaseLoading v-if="paymentsLoading" label="Loading payments…" />
                    <div v-else-if="payments.length" class="table-responsive">
                      <table class="table table-sm align-middle mb-0">
                        <thead>
                          <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                            <th v-if="canManage">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="payment in payments" :key="payment.id">
                            <td>{{ formatDate(payment.payment_date) }}</td>
                            <td>{{ payment.payment_method }}</td>
                            <td>{{ payment.reference || '—' }}</td>
                            <td class="text-end finance-number">
                              {{ money(payment.amount, row.currency) }}
                            </td>
                            <td v-if="canManage">
                              <button
                                class="btn btn-sm btn-outline-primary me-2"
                                @click="openPayment(row, payment)"
                              >
                                Edit</button
                              ><button
                                class="btn btn-sm btn-outline-danger"
                                @click="removePayment(payment)"
                              >
                                Delete
                              </button>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <p v-else class="text-muted mb-0">No payments recorded.</p>
                  </td>
                </tr></template
              >
            </tbody>
          </table>
        </div>
        <p v-else-if="!loading" class="p-4 text-muted mb-0">No invoices match these filters.</p>
        <div class="p-3">
          <BasePagination
            :page="page"
            :per-page="perPage"
            :total="total"
            :disabled="refreshing"
            @update:page="goToPage(loadInvoices, $event)"
          />
        </div>
      </section>
    </template>

    <template v-else>
      <section class="card-shell p-4 mb-4">
        <form class="row g-3 align-items-end" @submit.prevent="loadAll()">
          <div class="col-6 col-md-3">
            <label class="form-label" for="report-from">From</label
            ><input id="report-from" v-model="filter.date_from" class="form-control" type="date" />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="report-to">To</label
            ><input id="report-to" v-model="filter.date_to" class="form-control" type="date" />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="report-currency">Currency</label
            ><select id="report-currency" v-model="filter.currency" class="form-select">
              <option value="">All currencies</option>
              <option v-for="currency in options.currencies" :key="currency">{{ currency }}</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <button class="btn btn-primary" :disabled="refreshing">Run report</button
            ><button class="btn btn-outline-secondary ms-2" type="button" @click="exportCsv">
              Export CSV
            </button>
          </div>
        </form>
      </section>
      <div class="row g-4">
        <div class="col-12 col-xl-6">
          <section class="card-shell h-100">
            <div class="p-4"><h2 class="h5 mb-0">Income and expenses by category</h2></div>
            <PageSkeleton v-if="loading" label="Loading category report" :columns="4" />
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Currency</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in report.by_category"
                    :key="`${row.type}-${row.category}-${row.currency}`"
                  >
                    <td>{{ row.type === 'income' ? 'Income' : 'Expense' }}</td>
                    <td>{{ row.category }}</td>
                    <td>{{ row.currency }}</td>
                    <td class="text-end finance-number">{{ money(row.total, row.currency) }}</td>
                  </tr>
                  <tr v-if="!report.by_category.length">
                    <td colspan="4" class="text-muted">No activity in this period.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>
        <div class="col-12 col-xl-6">
          <section class="card-shell h-100">
            <div class="p-4"><h2 class="h5 mb-0">Performance by vehicle</h2></div>
            <PageSkeleton v-if="loading" label="Loading vehicle report" :columns="5" />
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th>Vehicle</th>
                    <th>Fleet</th>
                    <th>Currency</th>
                    <th class="text-end">Income</th>
                    <th class="text-end">Expenses</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in report.by_vehicle"
                    :key="`${row.fleet}-${row.vehicle_id}-${row.currency}`"
                  >
                    <td>{{ row.vehicle || `Vehicle #${row.vehicle_id}` }}</td>
                    <td>{{ row.fleet }}</td>
                    <td>{{ row.currency }}</td>
                    <td class="text-end finance-number">{{ money(row.income, row.currency) }}</td>
                    <td class="text-end finance-number">{{ money(row.expenses, row.currency) }}</td>
                  </tr>
                  <tr v-if="!report.by_vehicle.length">
                    <td colspan="5" class="text-muted">
                      No vehicle-linked activity in this period.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </div>
      <section class="card-shell mt-4">
        <div class="p-4"><h2 class="h5 mb-0">Monthly trend</h2></div>
        <PageSkeleton v-if="loading" label="Loading monthly trend" :columns="4" />
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Month</th>
                <th>Type</th>
                <th>Currency</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in report.by_month" :key="`${row.month}-${row.type}-${row.currency}`">
                <td>{{ row.month }}</td>
                <td>{{ row.type === 'income' ? 'Income' : 'Expense' }}</td>
                <td>{{ row.currency }}</td>
                <td class="text-end finance-number">{{ money(row.total, row.currency) }}</td>
              </tr>
              <tr v-if="!report.by_month.length">
                <td colspan="4" class="text-muted">No monthly activity to show.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>

    <BaseModal
      :open="Boolean(modal)"
      :title="
        modal === 'entry'
          ? `${editingEntry ? 'Edit' : 'Add'} ${form.type === 'income' ? 'income' : 'expense'}`
          : modal === 'invoice'
            ? `${editingInvoice ? 'Edit' : 'Create'} invoice`
            : `${editingPayment ? 'Edit' : 'Record'} payment`
      "
      :busy="saving"
      @close="closeModal"
    >
      <form id="finance-record-form" class="finance-form row g-3" @submit.prevent="save">
        <div
          v-if="Object.keys(errors).length"
          ref="errorSummary"
          class="col-12 alert alert-danger py-2"
          role="alert"
          tabindex="-1"
          aria-labelledby="finance-error-title"
        >
          <strong id="finance-error-title">There is a problem</strong>
          <ul class="mb-0 mt-1">
            <li v-for="(messages, key) in errors" :key="key">
              <a
                class="link-danger"
                :href="`#${errorTarget(String(key))}`"
                @click.prevent="focusErrorField(String(key))"
                >{{ errorLabels[String(key)] || 'Finance record' }}: {{ messages[0] }}</a
              >
            </li>
          </ul>
        </div>
        <template v-if="modal === 'entry'">
          <div class="col-12 col-md-6">
            <label class="form-label" for="entry-description">Description</label
            ><input
              id="entry-description"
              v-model="form.description"
              class="form-control"
              maxlength="180"
              required
            /><small v-if="fieldError('description')" class="text-danger">{{
              fieldError('description')
            }}</small>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="entry-category">Category</label
            ><select id="entry-category" v-model="form.category" class="form-select" required>
              <option
                v-for="category in form.type === 'income'
                  ? options.income_categories
                  : options.expense_categories"
                :key="category"
              >
                {{ category }}
              </option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="entry-amount">Amount</label
            ><input
              id="entry-amount"
              v-model="form.amount"
              class="form-control"
              type="number"
              min="0.01"
              step="0.01"
              required
            /><small v-if="fieldError('amount')" class="text-danger">{{
              fieldError('amount')
            }}</small>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="entry-currency">Currency</label
            ><select id="entry-currency" v-model="form.currency" class="form-select">
              <option v-for="currency in options.currencies" :key="currency">{{ currency }}</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="entry-date">Date</label
            ><input
              id="entry-date"
              v-model="form.entry_date"
              class="form-control"
              type="date"
              required
            />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="entry-status">Status</label
            ><select id="entry-status" v-model="form.status" class="form-select">
              <option value="posted">Posted</option>
              <option value="draft">Draft</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="entry-method">Payment method</label
            ><select id="entry-method" v-model="form.payment_method" class="form-select">
              <option value="">Not recorded</option>
              <option v-for="method in options.payment_methods" :key="method">{{ method }}</option>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="entry-customer">Customer</label
            ><select id="entry-customer" v-model="form.customer_id" class="form-select">
              <option value="">None</option>
              <option v-for="customer in options.customers" :key="customer.id" :value="customer.id">
                {{ customerName(customer) }}
              </option>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="entry-booking">Daily booking</label
            ><select id="entry-booking" v-model="form.daily_booking_id" class="form-select">
              <option value="">None</option>
              <option v-for="item in options.bookings" :key="item.id" :value="item.id">
                #{{ item.id }} · {{ item.guest_company_name }} · {{ item.start_date }}
              </option>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="entry-contract">Monthly contract</label
            ><select
              id="entry-contract"
              v-model="form.monthly_contract_id"
              class="form-select"
              @change="selectContract"
            >
              <option value="">None</option>
              <option v-for="item in options.contracts" :key="item.id" :value="item.id">
                {{ item.contract_no }} · {{ item.customer_name }}
              </option>
            </select>
          </div>
          <div v-if="form.type === 'expense'" class="col-12 col-md-6">
            <label class="form-label" for="entry-maintenance">Maintenance work order</label
            ><select
              id="entry-maintenance"
              v-model="form.maintenance_id"
              class="form-select"
              @change="selectMaintenance"
            >
              <option value="">None</option>
              <option v-for="item in options.maintenance" :key="item.id" :value="item.id">
                {{ item.title }} · {{ item.currency }} {{ item.actual_cost }}
              </option>
            </select>
          </div>
          <div v-if="form.type === 'expense'" class="col-12 col-md-6">
            <label class="form-label" for="entry-insurance">Insurance policy</label
            ><select
              id="entry-insurance"
              v-model="form.insurance_policy_id"
              class="form-select"
              @change="selectInsurance"
            >
              <option value="">None</option>
              <option v-for="item in options.insurance" :key="item.id" :value="item.id">
                {{ item.policy_number }} · {{ item.currency }} {{ item.premium_amount }}
              </option>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="entry-vehicle">Vehicle</label
            ><select
              id="entry-vehicle"
              v-model="form.vehicle_id"
              class="form-select"
              @change="setVehicleFleet"
            >
              <option value="">None</option>
              <option
                v-for="item in options.vehicles"
                :key="`${item.fleet}-${item.id}`"
                :value="item.id"
              >
                {{ item.plate_no }} · {{ item.model_name }} ({{ item.fleet }})
              </option>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="entry-reference">Reference</label
            ><input
              id="entry-reference"
              v-model="form.reference"
              class="form-control"
              maxlength="100"
            />
          </div>
          <div class="col-12">
            <label class="form-label" for="entry-notes">Notes</label
            ><textarea
              id="entry-notes"
              v-model="form.notes"
              class="form-control"
              rows="2"
              maxlength="5000"
            />
          </div>
          <div
            v-if="form.type === 'income' && !editingEntry?.invoice"
            class="col-12 form-check ms-2"
          >
            <input
              id="create-invoice"
              v-model="form.create_invoice"
              class="form-check-input"
              type="checkbox"
              @change="!form.create_invoice && (form.invoice_due_date = '')"
            /><label class="form-check-label" for="create-invoice"
              >Create invoice for this income</label
            ><input
              v-if="form.create_invoice"
              id="invoice-due-date"
              v-model="form.invoice_due_date"
              class="form-control mt-2"
              type="date"
              :min="form.entry_date"
              aria-label="Invoice due date"
            /><small v-if="fieldError('invoice_due_date')" class="d-block text-danger">{{
              fieldError('invoice_due_date')
            }}</small>
          </div>
        </template>
        <template v-else-if="modal === 'invoice'">
          <div class="col-12">
            <label class="form-label" for="invoice-description-form">Description</label
            ><input
              id="invoice-description-form"
              v-model="form.description"
              class="form-control"
              maxlength="180"
              required
            />
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="invoice-customer-form">Customer</label
            ><select id="invoice-customer-form" v-model="form.customer_id" class="form-select">
              <option value="">None</option>
              <option v-for="customer in options.customers" :key="customer.id" :value="customer.id">
                {{ customerName(customer) }}
              </option>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="invoice-booking-form">Daily booking</label
            ><select id="invoice-booking-form" v-model="form.daily_booking_id" class="form-select">
              <option value="">None</option>
              <option v-for="item in options.bookings" :key="item.id" :value="item.id">
                #{{ item.id }} · {{ item.guest_company_name }}
              </option>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label" for="invoice-contract-form">Monthly contract</label
            ><select
              id="invoice-contract-form"
              v-model="form.monthly_contract_id"
              class="form-select"
            >
              <option value="">None</option>
              <option v-for="item in options.contracts" :key="item.id" :value="item.id">
                {{ item.contract_no }} · {{ item.customer_name }}
              </option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="invoice-issue">Issue date</label
            ><input
              id="invoice-issue"
              v-model="form.issue_date"
              class="form-control"
              type="date"
              required
            />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="invoice-due">Due date</label
            ><input
              id="invoice-due"
              v-model="form.due_date"
              class="form-control"
              type="date"
              :min="form.issue_date"
              required
            />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="invoice-subtotal">Subtotal</label
            ><input
              id="invoice-subtotal"
              v-model="form.subtotal"
              class="form-control"
              type="number"
              min="0.01"
              step="0.01"
              required
              @input="updateInvoiceTotal"
            />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="invoice-tax">Tax</label
            ><input
              id="invoice-tax"
              v-model="form.tax_amount"
              class="form-control"
              type="number"
              min="0"
              step="0.01"
              required
              @input="updateInvoiceTotal"
            />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="invoice-total">Total</label
            ><input
              id="invoice-total"
              v-model="form.total"
              class="form-control"
              type="number"
              min="0.01"
              step="0.01"
              required
            />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label" for="invoice-currency-form">Currency</label
            ><select id="invoice-currency-form" v-model="form.currency" class="form-select">
              <option v-for="currency in options.currencies" :key="currency">{{ currency }}</option>
            </select>
          </div>
          <div v-if="editingInvoice" class="col-12">
            <label class="form-label" for="invoice-status-form">Invoice status</label
            ><select id="invoice-status-form" v-model="form.status" class="form-select">
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="void">Void</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label" for="invoice-notes">Notes</label
            ><textarea
              id="invoice-notes"
              v-model="form.notes"
              class="form-control"
              rows="2"
              maxlength="5000"
            />
          </div>
        </template>
        <template v-else>
          <div class="col-12">
            <p class="text-muted mb-0">
              {{ selectedInvoice?.invoice_number }} · balance
              {{
                money(
                  selectedInvoice?.balance ||
                    Number(selectedInvoice?.total) -
                      Number(selectedInvoice?.payments_sum_amount || 0),
                  selectedInvoice?.currency,
                )
              }}
            </p>
          </div>
          <div class="col-6">
            <label class="form-label" for="payment-date">Payment date</label
            ><input
              id="payment-date"
              v-model="form.payment_date"
              class="form-control"
              type="date"
              required
            />
          </div>
          <div class="col-6">
            <label class="form-label" for="payment-amount">Amount</label
            ><input
              id="payment-amount"
              v-model="form.amount"
              class="form-control"
              type="number"
              min="0.01"
              step="0.01"
              required
            /><small v-if="fieldError('amount')" class="text-danger">{{
              fieldError('amount')
            }}</small>
          </div>
          <div class="col-6">
            <label class="form-label" for="payment-method">Method</label
            ><select id="payment-method" v-model="form.payment_method" class="form-select" required>
              <option v-for="method in options.payment_methods" :key="method">{{ method }}</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label" for="payment-reference">Reference</label
            ><input
              id="payment-reference"
              v-model="form.reference"
              class="form-control"
              maxlength="100"
            />
          </div>
          <div class="col-12">
            <label class="form-label" for="payment-notes">Notes</label
            ><textarea
              id="payment-notes"
              v-model="form.notes"
              class="form-control"
              rows="2"
              maxlength="5000"
            />
          </div>
        </template>
      </form>
      <template #actions
        ><button class="btn btn-outline-secondary" :disabled="saving" @click="closeModal">
          Cancel</button
        ><button
          class="btn btn-primary"
          type="submit"
          form="finance-record-form"
          :disabled="saving"
        >
          <BaseLoading v-if="saving" label="Saving…" /><template v-else>Save</template>
        </button></template
      >
    </BaseModal>
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
.finance-section-header,
.finance-toolbar-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
}
.finance-section-header {
  justify-content: space-between;
  border-bottom: 1px solid var(--line);
}
.finance-toolbar-actions {
  justify-content: flex-end;
}
.finance-currency-filter {
  width: auto;
  min-width: 150px;
}
.finance-number {
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.finance-payment-row > td {
  padding: 1rem 1.25rem;
  background: var(--gss-palette-eee, #f8f6f3);
}
.finance-form {
  max-height: min(65vh, 650px);
  overflow-y: auto;
  padding: 0.75rem 0.1rem;
}
.finance-form :deep(.form-control),
.finance-form :deep(.form-select) {
  font-size: 0.875rem;
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
    box-shadow: inset 0 1px 0 rgb(255 255 255 / 78%), 0 3px 9px var(--gss-shadow-tint-soft);
  }
  .finance-section-header {
    align-items: flex-start;
    flex-direction: column;
  }
  .finance-toolbar-actions {
    width: 100%;
    justify-content: flex-start;
  }
  .finance-toolbar-actions > .btn {
    flex: 1 1 auto;
  }
  .finance-form :deep(.form-control),
  .finance-form :deep(.form-select) {
    font-size: 1rem;
  }
}
@media (prefers-reduced-motion: reduce) {
  .finance-tabs::before {
    transition: none;
  }
}
</style>
