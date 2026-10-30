<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseLoading from '@/components/common/BaseLoading.vue'
import BaseEmptyState from '@/components/common/BaseEmptyState.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import CustomerDetails from '../components/CustomerDetails.vue'
import { customerApi, customerError } from '../api'
import { assignmentTypes, rentalStatuses, currentMonth, shiftMonth, type Customer, type CustomerFilters, type CustomerSummary } from '../types'
const route = useRoute(), router = useRouter()
const textQuery = (key: string) => typeof route.query[key] === 'string' ? route.query[key] as string : ''
const filters = reactive<CustomerFilters>({ search: textQuery('search'), status: textQuery('status'), assignment_type: textQuery('assignment_type'), rental_status: textQuery('rental_status'), expiry: textQuery('expiry') })
const rows = ref<Customer[]>([]), summary = ref<CustomerSummary | null>(null), loading = ref(false), error = ref(''), summaryError = ref('')
const page = ref(1), total = ref(0), selected = ref<number | null>(null), month = ref(currentMonth())
const dialog = ref<HTMLDialogElement | null>(null), results = ref<HTMLElement | null>(null), fullscreen = ref(false)
const success = ref(({ created: 'Customer created successfully.', updated: 'Customer updated successfully.', deleted: 'Customer deleted successfully.' } as Record<string, string>)[textQuery('notice')] || '')
const metrics = computed(() => summary.value ? [
  { label: 'Total Customers', value: summary.value.total }, { label: 'Active Customers', value: summary.value.active }, { label: 'With Contracts', value: summary.value.with_contracts },
] : [])
let request = 0, summaryRequest = 0
onUnmounted(() => { request++; summaryRequest++; document.removeEventListener('fullscreenchange', syncFullscreen) })
async function load(next = 1) {
  const version = ++request
  loading.value = true; error.value = ''; rows.value = []; total.value = 0
  try {
    const data = await customerApi.list({ ...filters }, next)
    if (version !== request) return
    // Deletion can remove the final row from a page; return to the last valid page.
    if (next > data.meta.last_page) { await load(Math.max(1, data.meta.last_page)); return }
    rows.value = data.data; total.value = data.meta.total; page.value = data.meta.current_page
  } catch (cause) { if (version === request) error.value = customerError(cause) }
  finally { if (version === request) loading.value = false }
}
async function loadSummary() {
  const version = ++summaryRequest
  summaryError.value = ''
  try { const value = await customerApi.summary(); if (version === summaryRequest) summary.value = value }
  catch (cause) { if (version === summaryRequest) summaryError.value = customerError(cause) }
}
async function apply() {
  const query = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''))
  await router.replace({ path: '/customers', query })
  await load()
}
async function reset() {
  for (const key of Object.keys(filters) as (keyof CustomerFilters)[]) filters[key] = ''
  await apply()
}
async function view(id: number) {
  selected.value = id
  await nextTick()
  if (dialog.value && !dialog.value.open) dialog.value.showModal()
}
async function deleted() {
  dialog.value?.close(); selected.value = null; success.value = 'Customer deleted successfully.'
  await Promise.all([load(page.value), loadSummary()])
}
function syncFullscreen() { fullscreen.value = document.fullscreenElement === results.value }
async function toggleFullscreen() {
  try {
    if (document.fullscreenElement) await document.exitFullscreen()
    else if (results.value?.requestFullscreen) await results.value.requestFullscreen()
    else error.value = 'Fullscreen is unavailable in this browser.'
  } catch { error.value = 'Unable to enter fullscreen. You can continue using the table normally.' }
}
onMounted(() => { document.addEventListener('fullscreenchange', syncFullscreen); void load(); void loadSummary() })
</script>
<template>
  <main class="fleet-module page-content">
    <header class="page-heading"><div class="fleet-intro"><span class="fleet-intro-icon"><i class="bi bi-people" aria-hidden="true"/></span><div><h2>Customer directory</h2><p>Customer profiles and their monthly contract assignments.</p></div></div><RouterLink to="/customers/create" class="btn-gold">+ Add Customer</RouterLink></header>
    <div v-if="success" class="alert alert-success" role="status">{{ success }}<button class="btn-close float-end" aria-label="Dismiss success message" @click="success = ''"/></div>
    <div v-if="summaryError" class="alert alert-warning" role="alert">Summary: {{ summaryError }} <button class="btn-outline-warm" @click="loadSummary">Retry</button></div>
    <div class="row g-3 mb-4"><div v-for="metric in metrics" :key="metric.label" class="col-md-4"><div class="card-shell p-4"><span class="text-muted">{{ metric.label }}</span><div class="fs-3 fw-bold mt-2">{{ metric.value }}</div></div></div></div>
    <section class="card-shell p-4 mb-4" aria-label="Customer filters"><form class="row g-3" @submit.prevent="apply">
      <div class="col-lg-4"><label for="customer-search" class="form-label">Search</label><input id="customer-search" v-model="filters.search" type="search" maxlength="500" class="form-control" placeholder="Company, contact, phone, contract…"></div>
      <div class="col-sm-6 col-lg-2"><label for="customer-status" class="form-label">Customer Status</label><select id="customer-status" v-model="filters.status" class="form-select"><option value="">All statuses</option><option>Active</option><option>Inactive</option></select></div>
      <div class="col-sm-6 col-lg-3"><label for="customer-assignment" class="form-label">Assignment</label><select id="customer-assignment" v-model="filters.assignment_type" class="form-select"><option value="">All assignments</option><option v-for="type in assignmentTypes" :key="type">{{ type }}</option></select></div>
      <div class="col-sm-6 col-lg-3"><label for="customer-rental" class="form-label">Rental</label><select id="customer-rental" v-model="filters.rental_status" class="form-select"><option value="">All rentals</option><option v-for="status in rentalStatuses" :key="status">{{ status }}</option></select></div>
      <div class="col-sm-6 col-lg-3"><label for="customer-expiry" class="form-label">Contract Expiry</label><select id="customer-expiry" v-model="filters.expiry" class="form-select"><option value="">All contracts</option><option value="expiring">Within 60 days</option><option value="expired">Expired</option></select></div>
      <div class="col-lg-9 d-flex align-items-end gap-2 flex-wrap"><button class="btn-gold" :disabled="loading">Filter</button><button type="button" class="btn-outline-warm" :disabled="loading" @click="reset">Reset</button><div class="d-flex gap-2 align-items-center ms-lg-auto"><span class="small">Contract month</span><button type="button" class="btn-outline-warm" aria-label="Previous month" @click="month = shiftMonth(month, -1)">‹</button><span>{{ month }}</span><button type="button" class="btn-outline-warm" aria-label="Next month" @click="month = shiftMonth(month, 1)">›</button><button type="button" class="btn-outline-warm" :disabled="month === currentMonth()" @click="month = currentMonth()">Today</button></div></div>
    </form></section>
    <section ref="results" class="card-shell customer-results" aria-label="Customer results" :aria-busy="loading">
      <div class="p-4 d-flex align-items-center justify-content-between gap-3 flex-wrap"><div><h2 class="h5">Customer List</h2><p class="small text-muted mb-0">{{ total }} matching customers. View opens contracts for {{ month }}.</p></div><button class="btn-outline-warm" @click="toggleFullscreen">{{ fullscreen ? 'Exit Fullscreen' : 'Fullscreen Table' }}</button></div>
      <div v-if="error" class="alert alert-danger m-3" role="alert">{{ error }} <button class="btn-outline-warm" @click="load(page)">Retry</button></div>
      <BaseLoading v-if="loading" label="Loading customers…"/>
      <div v-else-if="rows.length" class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>#</th><th>Company</th><th>Contact</th><th>Phone</th><th>Contracts</th><th>Latest Expiry</th><th>Status</th><th>Actions</th></tr></thead><tbody><tr v-for="(customer, index) in rows" :key="customer.id"><td>{{ (page - 1) * 10 + index + 1 }}</td><td><button class="company-link" @click="view(customer.id)">{{ customer.company_name || '—' }}</button><div class="small text-muted">{{ customer.guest_name || 'No guest name' }}</div></td><td>{{ customer.contact_person || '—' }}<div class="small text-muted">{{ customer.contact_email }}</div></td><td>{{ customer.contact_phone || '—' }}</td><td>{{ customer.contract_count }} total<div class="small text-muted">{{ customer.active_contract_count }} active</div></td><td>{{ customer.latest_expiry || '—' }}</td><td><BaseStatusBadge :status="customer.customer_status"/></td><td><button class="btn-outline-warm" @click="view(customer.id)">View</button><RouterLink :to="`/customers/${customer.id}/edit`" class="btn-outline-warm ms-2">Edit</RouterLink></td></tr></tbody></table></div>
      <BaseEmptyState v-else-if="!error" title="No customers found" description="Add a customer or adjust the search filters."/>
      <div class="p-3"><BasePagination :page="page" :per-page="10" :total="total" :disabled="loading" @update:page="load"/></div>
      <dialog ref="dialog" class="customer-drawer fleet-module" aria-labelledby="customer-drawer-title" @close="selected = null" @click="event => { if (event.target === dialog) dialog?.close() }"><header class="d-flex justify-content-between align-items-center gap-3 mb-4"><strong id="customer-drawer-title">Customer Details</strong><button autofocus class="btn-outline-warm" aria-label="Close customer details" @click="dialog?.close()">× Close</button></header><CustomerDetails v-if="selected" :key="selected" :id="selected" :month="month" @month-change="month = $event" @deleted="deleted"/></dialog>
    </section>
  </main>
</template>
<style scoped>
.customer-results{overflow:hidden}.customer-results:fullscreen{background:var(--paper-strong,#fffaf3);overflow:auto;padding:1rem}.company-link{background:none;border:0;padding:0;color:var(--cocoa,#4f200d);font-weight:800;text-align:left;text-decoration:underline;text-underline-offset:3px}.customer-drawer{inset:0 0 0 auto;margin:0;width:min(760px,100vw);height:100dvh;max-height:100dvh;max-width:100vw;border:0;padding:1.5rem;background:var(--paper-strong,#fffaf3);color:var(--ink,#4f200d);box-shadow:-12px 0 48px #24160e33;overflow-y:auto}.customer-drawer::backdrop{background:#24160e80}.customer-drawer>header{border-bottom:1px solid var(--line,#e7dac7);padding-bottom:1rem}.table td:first-child{min-width:auto}.table td{overflow-wrap:anywhere}
</style>
