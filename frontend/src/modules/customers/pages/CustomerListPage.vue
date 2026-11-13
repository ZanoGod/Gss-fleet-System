<script setup lang="ts">
import ActionButtonLabel from '@/components/common/ActionButtonLabel.vue'
import { usePendingAction } from '@/components/common/usePendingAction'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import { customersMetrics, customerHeader } from '../overview'
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BaseEmptyState from '@/components/common/BaseEmptyState.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import CustomerDetails from '../components/CustomerDetails.vue'
import RecordAction from '@/components/common/RecordAction.vue'
import { customerApi, customerError } from '../api'
import { toast } from '@/services/toast'
import {
  assignmentTypes,
  currentMonth,
  shiftMonth,
  type Customer,
  type CustomerFilters,
  type CustomerSummary,
} from '../types'
const route = useRoute(),
  router = useRouter()
const textQuery = (key: string) =>
  typeof route.query[key] === 'string' ? (route.query[key] as string) : ''
const filters = reactive<CustomerFilters>({
  search: textQuery('search'),
  status: textQuery('status'),
  assignment_type: textQuery('assignment_type'),
  rental_status: textQuery('rental_status'),
  expiry: textQuery('expiry'),
})
const rows = ref<Customer[]>([]),
  summary = ref<CustomerSummary | null>(null),
  loading = ref(false),
  initial = ref(true),
  error = ref(''),
  summaryError = ref(''),
  summaryLoading = ref(true)
const page = ref(1),
  total = ref(0),
  selected = ref<number | null>(null),
  month = ref(currentMonth())
const monthLabel = computed(() => {
  const [year, value] = month.value.split('-').map(Number)
  return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(
    new Date(Date.UTC(year!, value! - 1, 1)),
  )
})
const dialog = ref<HTMLDialogElement | null>(null),
  results = ref<HTMLElement | null>(null),
  fullscreen = ref(false)
const noticeMessages: Record<string, string> = {
  created: 'Customer created successfully.',
  updated: 'Customer updated successfully.',
  deleted: 'Customer deleted successfully.',
}
if (noticeMessages[textQuery('notice')]) toast.success(noticeMessages[textQuery('notice')]!)
let request = 0,
  summaryRequest = 0
onUnmounted(() => {
  request++
  summaryRequest++
  document.removeEventListener('fullscreenchange', syncFullscreen)
})
const { pending, run } = usePendingAction()
async function load(next = 1) {
  const version = ++request
  loading.value = true
  if (!pending('retry')) error.value = ''
  try {
    const data = await customerApi.list({ ...filters }, next)
    if (version !== request) return
    // Deletion can remove the final row from a page; return to the last valid page.
    if (next > data.meta.last_page) {
      await load(Math.max(1, data.meta.last_page))
      return
    }
    error.value = ''
    rows.value = data.data
    total.value = data.meta.total
    page.value = data.meta.current_page
  } catch (cause) {
    if (version === request) {
      error.value = customerError(cause)
      toast.error(error.value)
    }
  } finally {
    if (version === request) {
      loading.value = false
      initial.value = false
    }
  }
}
async function loadSummary() {
  const version = ++summaryRequest
  summaryLoading.value = true
  try {
    const value = await customerApi.summary()
    if (version === summaryRequest) {
      summary.value = value
      summaryError.value = ''
    }
  } catch (cause) {
    if (version === summaryRequest) {
      summaryError.value = customerError(cause)
      toast.warning(`Customer summary: ${summaryError.value}`)
    }
  } finally {
    if (version === summaryRequest) summaryLoading.value = false
  }
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
  dialog.value?.close()
  selected.value = null
  toast.success('Customer deleted successfully.')
  await Promise.all([load(page.value), loadSummary()])
}
function syncFullscreen() {
  fullscreen.value = document.fullscreenElement === results.value
}
async function toggleFullscreen() {
  try {
    if (document.fullscreenElement) await document.exitFullscreen()
    else if (results.value?.requestFullscreen) await results.value.requestFullscreen()
    else toast.info('Fullscreen is unavailable in this browser.')
  } catch {
    toast.error('Unable to enter fullscreen. You can continue using the table normally.')
  }
}
onMounted(() => {
  document.addEventListener('fullscreenchange', syncFullscreen)
  void load()
  void loadSummary()
})
</script>
<template>
  <main class="fleet-module fleet-list-page page-content glass-module">
    <PageHeader v-bind="customerHeader" class="d-flex flex-wrap justify-content-end">
      <RouterLink to="/customers/create" class="btn gss-button gss-button--primary"
        >+ Add Customer</RouterLink
      >
    </PageHeader>
    <StatusSummary
      :items="customersMetrics(summary)"
      :loading="summaryLoading && !summary"
      label="customer status summary"
    />
    <button
      v-if="summaryError"
      class="btn gss-button gss-button--outline gss-button--neutral mb-3"
      :disabled="summaryLoading"
      @click="loadSummary"
      :aria-busy="summaryLoading"
    >
      <ActionButtonLabel :pending="summaryLoading" loading-label="Retrying…"
        >Retry summary</ActionButtonLabel
      >
    </button>
    <section class="card-shell fleet-list-filter mb-4" aria-label="Customer filters">
      <div class="fleet-list-filter-heading">
        <div>
          <h2 class="h5 mb-1">Filter Customers</h2>
          <p class="small text-muted mb-0">Find customers by details or contract status.</p>
        </div>
      </div>
      <form class="customer-filter-grid" @submit.prevent="run('filter', apply)">
        <div class="customer-filter-field customer-filter-search">
          <label for="customer-search" class="form-label">Search</label
          ><input
            id="customer-search"
            v-model="filters.search"
            type="search"
            maxlength="500"
            class="form-control"
            placeholder="Company, contact, phone, contract…"
          />
        </div>
        <div class="customer-filter-field">
          <label for="customer-status" class="form-label">Customer Status</label
          ><select id="customer-status" v-model="filters.status" class="form-select">
            <option value="">All statuses</option>
            <option>Active</option>
            <option>Inactive</option>
          </select>
        </div>
        <div class="customer-filter-field">
          <label for="customer-assignment" class="form-label">Contract assignment</label
          ><select id="customer-assignment" v-model="filters.assignment_type" class="form-select">
            <option value="">All assignments</option>
            <option v-for="type in assignmentTypes" :key="type">{{ type }}</option>
          </select>
        </div>
        <div class="customer-filter-field">
          <label for="customer-expiry" class="form-label">Contract Expiry</label
          ><select id="customer-expiry" v-model="filters.expiry" class="form-select">
            <option value="">All contracts</option>
            <option value="expiring">Within 60 days</option>
            <option value="expired">Expired</option>
          </select>
        </div>
        <div class="customer-filter-field customer-month-field">
          <label class="form-label">Contract month</label>
          <div class="customer-month-controls">
            <button
              type="button"
              class="btn gss-button gss-button--outline gss-button--neutral"
              aria-label="Previous contract month"
              @click="month = shiftMonth(month, -1)"
            ><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
            <span class="customer-month-label" aria-live="polite">{{ monthLabel }}</span>
            <button
              type="button"
              class="btn gss-button gss-button--outline gss-button--neutral"
              aria-label="Next contract month"
              @click="month = shiftMonth(month, 1)"
            ><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            <button
              type="button"
              class="btn gss-button gss-button--outline gss-button--neutral"
              :disabled="month === currentMonth()"
              @click="month = currentMonth()"
            >Today</button>
          </div>
        </div>
        <div class="fleet-list-filter-actions customer-filter-actions module-filter-actions">
          <button
            class="btn module-filter-apply"
            :disabled="initial || pending('filter')"
            :aria-busy="pending('filter')"
          >
            <ActionButtonLabel :pending="pending('filter')" loading-label="Filtering…"
              ><i class="bi bi-funnel" aria-hidden="true"></i> Filter</ActionButtonLabel
            ></button
          ><span class="module-filter-divider" aria-hidden="true"></span
          ><button
            type="button"
            class="btn module-filter-reset"
            :disabled="initial || pending('reset')"
            @click="run('reset', reset)"
            :aria-busy="pending('reset')"
          >
            <ActionButtonLabel :pending="pending('reset')" loading-label="Resetting…"
              ><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</ActionButtonLabel
            >
          </button>
        </div>
      </form>
    </section>
    <section
      ref="results"
      class="card-shell customer-results fleet-list-results"
      aria-label="Customer results"
    >
      <button
        v-if="error"
        class="btn gss-button gss-button--outline gss-button--neutral m-3"
        :disabled="pending('retry')"
        @click="run('retry', () => load(page))"
      >
        <ActionButtonLabel :pending="pending('retry')" loading-label="Retrying…"
          >Retry loading customers</ActionButtonLabel
        >
      </button>
      <div class="p-4 d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
          <h2 class="h5">Customer List</h2>
          <p class="small text-muted mb-0">
            {{ total }} matching customers. View opens contracts for {{ month }}.
          </p>
        </div>
        <button
          class="btn gss-button gss-button--outline gss-button--neutral"
          @click="toggleFullscreen"
        >
          {{ fullscreen ? 'Exit Fullscreen' : 'Fullscreen Table' }}
        </button>
      </div>
      <BaseLoading
        v-if="loading && !initial"
        class="d-block px-3 py-2"
        label="Updating customers…"
      />
      <PageSkeleton v-if="initial" label="Loading customers" :columns="8" />
      <div v-else-if="rows.length" class="table-responsive fleet-list-table-wrap">
        <table class="table table-hover align-middle mb-0 fleet-list-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Company</th>
              <th>Contact</th>
              <th>Phone</th>
              <th>Contracts</th>
              <th>Latest Expiry</th>
              <th>Status</th>
              <th class="fleet-list-actions-heading">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(customer, index) in rows" :key="customer.id">
              <td>{{ (page - 1) * 10 + index + 1 }}</td>
              <td>
                <button class="company-link" @click="view(customer.id)">
                  {{ customer.company_name || '—' }}
                </button>
                <div class="small text-muted">{{ customer.guest_name || 'No guest name' }}</div>
              </td>
              <td>
                {{ customer.contact_person || '—' }}
                <div class="small text-muted">{{ customer.contact_email }}</div>
              </td>
              <td>{{ customer.contact_phone || '—' }}</td>
              <td>
                {{ customer.contract_count }} total
                <div class="small text-muted">{{ customer.active_contract_count }} active</div>
              </td>
              <td>{{ customer.latest_expiry || '—' }}</td>
              <td><BaseStatusBadge :status="customer.customer_status" /></td>
              <td class="fleet-list-actions-cell">
                <div class="fleet-list-row-actions">
                  <RecordAction action="view" icon-only :aria-label="`View customer ${customer.company_name || customer.id}`" @click="view(customer.id)" />
                  <RecordAction action="edit" icon-only :aria-label="`Edit customer ${customer.company_name || customer.id}`" :to="`/customers/${customer.id}/edit`" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <BaseEmptyState
        v-else-if="!error"
        title="No customers found"
        description="Add a customer or adjust the search filters."
      />
      <div class="p-3">
        <BasePagination
          :page="page"
          :per-page="10"
          :total="total"
          :pending-page="(target) => pending(`page:${target}`)"
          @update:page="run(`page:${$event}`, () => load($event))"
        />
      </div>
      <dialog
        ref="dialog"
        class="customer-drawer fleet-module"
        aria-labelledby="customer-drawer-title"
        @close="selected = null"
        @click="
          (event) => {
            if (event.target === dialog) dialog?.close()
          }
        "
      >
        <header class="d-flex justify-content-between align-items-center gap-3 mb-4">
          <strong id="customer-drawer-title">Customer Details</strong
          ><button
            autofocus
            class="btn gss-button gss-button--outline gss-button--neutral"
            aria-label="Close customer details"
            @click="dialog?.close()"
          >
            × Close
          </button>
        </header>
        <CustomerDetails
          v-if="selected"
          :key="selected"
          :id="selected"
          :month="month"
          @month-change="month = $event"
          @deleted="deleted"
        />
      </dialog>
    </section>
  </main>
</template>
<style scoped>
.customer-results {
  overflow: hidden;
}
.customer-results:fullscreen {
  background: var(--paper-strong, var(--gss-surface-card-header));
  overflow: auto;
  padding: 1rem;
}
.company-link {
  background: none;
  border: 0;
  padding: 0;
  color: var(--cocoa, var(--gss-text-strong));
  font-weight: 800;
  text-align: left;
  text-decoration: underline;
  text-underline-offset: 3px;
}
.customer-drawer {
  inset: 0 0 0 auto;
  margin: 0;
  width: min(760px, 100vw);
  height: 100dvh;
  max-height: 100dvh;
  max-width: 100vw;
  border: 0;
  padding: 1.5rem;
  background: var(--paper-strong, var(--gss-surface-card-header));
  color: var(--ink, var(--gss-text-strong));
  box-shadow: -12px 0 48px var(--gss-palette-24160e33);
  overflow-y: auto;
}
.customer-drawer::backdrop {
  background: var(--gss-palette-24160e80);
}
.customer-drawer > header {
  border-bottom: 1px solid var(--line, var(--gss-border-default));
  padding-bottom: 1rem;
}
</style>
