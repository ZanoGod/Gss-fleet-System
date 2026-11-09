<script setup lang="ts">
import { usePendingAction } from '@/components/common/usePendingAction'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import { contractMetrics, contractHeader } from '../overview'
import { onMounted, reactive, ref, nextTick } from 'vue'
import { storeToRefs } from 'pinia'
import { monthlyContractApi } from '../api'
import api from '@/services/api'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import { useMonthlyContractsStore } from '../stores/monthlyContracts'
import type { MonthlyContract } from '../types'
import { toast } from '@/services/toast'
import BasePagination from '@/components/common/BasePagination.vue'
const store = useMonthlyContractsStore(),
  { rows, loading, error, page, lastPage, total, summary, options } = storeToRefs(store),
  filters = reactive({
    search: '',
    status: '',
    date_from: '',
    date_to: '',
    car_id: '',
    driver_id: '',
    customer_id: '',
  }),
  printing = ref(false),
  printRows = ref<MonthlyContract[]>([])
const initial = ref(true)
const { pending, run } = usePendingAction()
async function load(next = 1) {
  try {
    await store.load({ ...filters, page: next, per_page: 15 })
  } catch {
    toast.error(store.error || 'Unable to load contracts.')
  }
}
async function exportExcel() {
  try {
    const res = await api.get('/api/monthly-contracts/export.csv', {
      params: filters,
      responseType: 'blob',
    })
    const href = URL.createObjectURL(res.data)
    const a = document.createElement('a')
    a.href = href
    a.download = 'monthly-contracts.csv'
    a.click()
    URL.revokeObjectURL(href)
    toast.success('Contracts exported successfully.')
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to export contracts.'
    toast.error(error.value)
  }
}
async function printReport() {
  printing.value = true
  printRows.value = []
  try {
    let pageNo = 1,
      last = 1
    do {
      const result = await monthlyContractApi.list({ ...filters, page: pageNo, per_page: 100 })
      printRows.value.push(...result.data)
      last = result.last_page
      pageNo += 1
    } while (pageNo <= last)
    await nextTick()
    window.print()
    toast.success('Contract report prepared for printing.')
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to prepare the PDF report.'
    toast.error(error.value)
  } finally {
    printing.value = false
  }
}
onMounted(async () => {
  try {
    await store.loadOptions()
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to load filters.'
    toast.error(error.value)
  }
  await load()
  initial.value = false
})
</script>
<template>
  <main v-if="printing" class="print-only">
    <h1>Monthly Contracts Report</h1>
    <p>Filtered contracts Â· {{ new Date().toLocaleDateString() }}</p>
    <table class="table table-sm">
      <thead>
        <tr>
          <th>Contract</th>
          <th>Customer</th>
          <th>Vehicle</th>
          <th>Driver</th>
          <th>Operator</th>
          <th>Start</th>
          <th>End</th>
          <th>Status</th>
          <th>Amount</th>
          <th>Contract insurance snapshot</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="c in printRows" :key="c.id">
          <td>{{ c.contract_no }}</td>
          <td>{{ c.customer_name }}</td>
          <td>{{ c.car?.plate_no || 'â€”' }}</td>
          <td>{{ c.driver?.full_name || 'â€”' }}</td>
          <td>{{ c.operator_name || 'â€”' }}</td>
          <td>{{ c.start_date }}</td>
          <td>{{ c.end_date }}</td>
          <td>{{ c.status }}</td>
          <td>{{ c.rental_currency }} {{ c.monthly_rate }}</td>
          <td>{{ c.insurance_price }} Â· {{ c.insurance_company || 'â€”' }}</td>
        </tr>
      </tbody>
    </table>
    <p>Total contracts: {{ printRows.length }}</p>
  </main>
  <main v-else class="fleet-module page-content contract-report">
    <PageHeader v-bind="contractHeader" class="d-flex flex-wrap justify-content-end">
      <div class="d-flex flex-wrap gap-2 report-actions">
        <!-- <button class="btn btn-outline-secondary" @click="printReport">
          <i class="bi bi-file-earmark-pdf me-1" /> Print / Save PDF</button
        ><button class="btn btn-outline-primary" @click="exportExcel">
          <i class="bi bi-file-earmark-spreadsheet me-1" /> Excel CSV</button> -->
        <RouterLink to="/monthly-contracts/create" class="btn btn-primary"
          ><i class="bi bi-plus-lg me-1" /> New contract</RouterLink
        >
      </div>
    </PageHeader>
    <StatusSummary
      :items="contractMetrics(summary)"
      :loading="initial"
      label="contract status summary"
    />
    <section class="card shadow-sm">
      <div class="card-header bg-white report-actions">
        <form class="fleet-filter-grid" @submit.prevent="run('search', () => load())">
          <div class="fleet-filter-field fleet-filter-field--wide">
            <label class="form-label" for="contract-search">Search contracts</label
            ><input
              id="contract-search"
              v-model="filters.search"
              class="form-control"
              placeholder="Contract, customer, vehicleâ€¦"
            />
          </div>
          <div class="fleet-filter-field">
            <label class="form-label" for="contract-status">Status</label
            ><select id="contract-status" v-model="filters.status" class="form-select">
              <option value="">All statuses</option>
              <option v-for="s in ['Draft', 'Active', 'Expired', 'Renewed', 'Cancelled']" :key="s">
                {{ s }}
              </option>
            </select>
          </div>
          <div class="fleet-filter-field">
            <label class="form-label" for="contract-car">Monthly vehicle</label
            ><select id="contract-car" v-model="filters.car_id" class="form-select">
              <option value="">All monthly vehicles</option>
              <option v-for="c in options?.cars || []" :key="c.id" :value="String(c.id)">
                {{ c.plate_no }}
              </option>
            </select>
          </div>
          <div class="fleet-filter-field">
            <label class="form-label" for="contract-driver">Monthly driver</label
            ><select id="contract-driver" v-model="filters.driver_id" class="form-select">
              <option value="">All monthly drivers</option>
              <option v-for="d in options?.drivers || []" :key="d.id" :value="String(d.id)">
                {{ d.full_name }}
              </option>
            </select>
          </div>
          <div class="fleet-filter-field">
            <label class="form-label" for="contract-customer">Customer</label
            ><select id="contract-customer" v-model="filters.customer_id" class="form-select">
              <option value="">All customers</option>
              <option v-for="c in options?.customers || []" :key="c.id" :value="String(c.id)">
                {{ c.company_name || c.guest_name }}
              </option>
            </select>
          </div>
          <div class="fleet-filter-field">
            <label class="form-label" for="contract-from">Start date from</label
            ><input
              id="contract-from"
              v-model="filters.date_from"
              class="form-control"
              type="date"
              aria-label="Contract start from"
            />
          </div>
          <div class="fleet-filter-field">
            <label class="form-label" for="contract-to">End date to</label
            ><input
              id="contract-to"
              v-model="filters.date_to"
              class="form-control"
              type="date"
              aria-label="Contract end to"
            />
          </div>
          <div class="fleet-filter-actions">
            <button
              class="btn btn-outline-primary w-100 fleet-filter-submit"
              :disabled="initial || pending('search')"
            >
              <BaseLoading v-if="pending('search')" label="Searching…" /><template v-else
                >Search</template
              >
            </button>
          </div>
        </form>
      </div>
      <BaseLoading
        v-if="!initial && store.loading"
        class="px-3 py-2 d-block"
        label="Updating contracts…"
      />
      <PageSkeleton v-if="initial" label="Loading contracts" :columns="9" />
      <div v-else-if="rows.length" class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Contract number</th>
              <th>Customer</th>
              <th>Monthly vehicle</th>
              <th>Driver</th>
              <th>Operator</th>
              <th>Period</th>
              <th>Contract amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in rows" :key="c.id">
              <td>
                <RouterLink :to="`/monthly-contracts/${c.id}`" class="fw-semibold">{{
                  c.contract_no
                }}</RouterLink>
              </td>
              <td>{{ c.customer_name }}</td>
              <td>{{ c.car?.plate_no || 'â€”' }}</td>
              <td>{{ c.driver?.full_name || 'â€”' }}</td>
              <td>{{ c.operator_name || 'â€”' }}</td>
              <td>
                {{ c.start_date }}
                <div class="small text-muted">to {{ c.end_date }}</div>
              </td>
              <td>{{ c.rental_currency }} {{ c.monthly_rate }}</td>
              <td><BaseStatusBadge :status="c.status" /></td>
              <td>
                <RouterLink
                  :to="`/monthly-contracts/${c.id}`"
                  class="btn btn-sm btn-outline-secondary"
                  >View</RouterLink
                >
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-4 text-center text-muted">No contracts match your filters.</div>
      <div class="card-footer bg-white">
        <BasePagination
          :page="page"
          :per-page="15"
          :total="total"
          :pending-page="(target) => pending(`page:${target}`)"
          @update:page="run(`page:${$event}`, () => load($event))"
        />
      </div>
    </section>
  </main>
</template>
<style scoped>
.print-only {
  display: none;
}
@media print {
  .report-actions {
    display: none !important;
  }
  .contract-report {
    display: none !important;
  }
  .print-only {
    display: block !important;
    padding: 0 !important;
  }
  .card {
    box-shadow: none !important;
    border: 0 !important;
  }
  .table-responsive {
    overflow: visible !important;
  }
}
</style>
