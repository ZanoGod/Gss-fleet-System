<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import RecordAction from '@/components/common/RecordAction.vue'
import { usePendingAction } from '@/components/common/usePendingAction'
import { toast } from '@/services/toast'
import { carLicenseApi } from '../api'
import { carLicenseMetrics } from '../overview'
import type { CarLicense, LicenseDashboard, LicenseVehicleOption } from '../types'

const rows = ref<CarLicense[]>([]),
  summary = ref<LicenseDashboard | null>(null),
  vehicles = ref<LicenseVehicleOption[]>([])
const offices = ref<Record<string, string[]>>({}),
  initial = ref(true),
  loading = ref(false),
  page = ref(1),
  total = ref(0),
  error = ref('')
const filters = reactive({ search: '', status: '', fleet: '', office_region: '' })
const { pending, run } = usePendingAction()
let requestId = 0
async function load(nextPage = 1) {
  const version = ++requestId
  loading.value = true
  error.value = ''
  try {
    const params = { ...filters, page: nextPage, per_page: 10 }
    const [list, stats, options] = await Promise.all([
      carLicenseApi.list(params),
      carLicenseApi.summary(),
      carLicenseApi.options(),
    ])
    if (version !== requestId) return
    rows.value = list.data.data
    total.value = list.data.meta.total
    page.value = list.data.meta.current_page
    summary.value = stats
    vehicles.value = options.vehicles
    offices.value = options.offices
  } catch (cause) {
    if (version === requestId)
      error.value = cause instanceof Error ? cause.message : 'Unable to load car licenses.'
  } finally {
    if (version === requestId) {
      initial.value = false
      loading.value = false
    }
  }
}
async function apply() {
  await run('filter', () => load(1))
}
async function reset() {
  Object.assign(filters, { search: '', status: '', fleet: '', office_region: '' })
  await load(1)
}
function date(value: string) {
  return new Date(`${value}T00:00:00`).toLocaleDateString()
}
onMounted(() => void load())
</script>

<template>
  <section
    class="fleet-module page-content resource-workspace car-license-page"
    aria-label="Car license management"
  >
    <div class="d-flex flex-wrap justify-content-end">
      <PageHeader
        title="Car License Management"
        description="Track RTAD registration terms, renewals, and vehicle documents."
        icon="bi-card-checklist"
      >
        <RouterLink to="/car-licenses/create" class="btn btn-primary">+ Add Car License</RouterLink>
      </PageHeader>
    </div>
    <StatusSummary
      :items="carLicenseMetrics(summary)"
      :loading="initial"
      label="Car license status summary"
    />
    <section
      v-if="summary?.alerts.length || summary?.expired_alerts.length"
      class="license-alerts"
      aria-label="License expiry alerts"
    >
      <h2><i class="bi bi-bell" aria-hidden="true"></i> Expiry alerts</h2>
      <RouterLink
        v-for="alert in [...(summary?.alerts || []), ...(summary?.expired_alerts || [])]"
        :key="alert.id"
        :to="`/car-licenses/${alert.id}`"
        :class="['license-alert', alert.status === 'Expired' ? 'is-expired' : 'is-soon']"
      >
        <span>{{ alert.vehicle?.plate_no }} · {{ alert.license_number }}</span>
        <strong>{{
          alert.status === 'Expired'
            ? `Expired ${Math.abs(alert.days_until_expiry)} days ago`
            : `${alert.days_until_expiry} days left`
        }}</strong>
      </RouterLink>
    </section>
    <section class="card-shell p-4 mb-4" aria-label="Search and filters">
      <form class="row g-3" @submit.prevent="apply">
        <div class="col-md-4">
          <label for="license-search" class="form-label">Search</label
          ><input
            id="license-search"
            v-model="filters.search"
            class="form-control"
            type="search"
            placeholder="Plate, license number, branch"
          />
        </div>
        <div class="col-md-2">
          <label for="license-status" class="form-label">Status</label
          ><select id="license-status" v-model="filters.status" class="form-select">
            <option value="">All statuses</option>
            <option>Active</option>
            <option>Expiring Soon</option>
            <option>Expired</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="license-fleet" class="form-label">Fleet</label
          ><select id="license-fleet" v-model="filters.fleet" class="form-select">
            <option value="">All vehicles</option>
            <option value="daily">Daily fleet</option>
            <option value="monthly">Monthly fleet</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="license-region" class="form-label">RTAD Region</label
          ><select id="license-region" v-model="filters.office_region" class="form-select">
            <option value="">All regions</option>
            <option v-for="region in Object.keys(offices)" :key="region">{{ region }}</option>
          </select>
        </div>
        <div class="col-md-2 module-filter-actions align-items-end">
          <button class="btn module-filter-apply" :disabled="initial || pending('filter')">
            <BaseLoading v-if="pending('filter')" label="Filtering…" />
            <template v-else><i class="bi bi-funnel" aria-hidden="true"></i> Filter</template>
          </button>
          <span class="module-filter-divider" aria-hidden="true"></span>
          <button
            type="button"
            class="btn module-filter-reset"
            :disabled="initial || pending('reset')"
            @click="run('reset', reset)"
          >
            <BaseLoading v-if="pending('reset')" label="Resetting…" />
            <template v-else
              ><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</template
            >
          </button>
        </div>
      </form>
    </section>
    <section class="card-shell" aria-label="Car license records">
      <div class="p-4 d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0">
          License records <span class="text-muted">({{ total }})</span>
        </h2>
        <BaseLoading v-if="loading && !initial" label="Updating licenses…" />
      </div>
      <p v-if="error" class="alert alert-warning mx-3">
        {{ error }}
        <button class="btn btn-sm btn-outline-primary" @click="load(page)">Retry</button>
      </p>
      <PageSkeleton v-if="initial" label="Loading car licenses" :columns="8" />
      <div v-else-if="rows.length" class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <caption class="visually-hidden">
            Car license records
          </caption>
          <thead>
            <tr>
              <th>Vehicle</th>
              <th>License No.</th>
              <th>Period</th>
              <th>Issue Date</th>
              <th>Expiry Date</th>
              <th>Countdown</th>
              <th>RTAD Office</th>
              <th>Status</th>
              <th class="record-actions-cell">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="license in rows" :key="license.id">
              <td>
                <strong>{{ license.vehicle?.plate_no || '—' }}</strong
                ><small class="d-block text-muted"
                  >{{ license.vehicle?.fleet }} fleet · {{ license.vehicle?.model_name }}</small
                >
              </td>
              <td>{{ license.license_number }}</td>
              <td>{{ license.license_period }} year{{ license.license_period > 1 ? 's' : '' }}</td>
              <td>{{ date(license.issue_date) }}</td>
              <td>{{ date(license.expiry_date) }}</td>
              <td :class="license.days_until_expiry <= 30 ? 'text-danger fw-semibold' : ''">
                {{
                  license.days_until_expiry < 0
                    ? `${Math.abs(license.days_until_expiry)} days overdue`
                    : `${license.days_until_expiry} days`
                }}
              </td>
              <td>{{ license.office_region }} · {{ license.office_branch }}</td>
              <td><BaseStatusBadge :status="license.status" /></td>
              <td class="record-actions-cell">
                <div class="record-actions">
                  <RecordAction
                    action="view"
                    label="View"
                    :to="`/car-licenses/${license.id}`"
                  /><RecordAction
                    v-if="license.is_current"
                    action="edit"
                    label="Edit"
                    :to="`/car-licenses/${license.id}/edit`"
                  /><RouterLink
                    v-if="license.is_current"
                    class="btn btn-sm license-renew-action"
                    :to="`/car-licenses/${license.id}/renew`"
                    ><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Renew</RouterLink
                  >
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else-if="!error" class="p-4 text-muted">
        No car license records found. Add a license or adjust your filters.
      </p>
      <div class="p-3">
        <BasePagination
          :page="page"
          :per-page="10"
          :total="total"
          :pending-page="(target) => pending(`page:${target}`)"
          @update:page="run(`page:${$event}`, () => load($event))"
        />
      </div>
    </section>
  </section>
</template>

<style scoped>
.license-alerts {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0 0 1.25rem;
  padding: 1rem 1.1rem;
  border: 1px solid #edc76b;
  border-radius: 14px;
  background: color-mix(in srgb, #fff7df 78%, var(--gss-surface-card));
}
.license-alerts h2 {
  width: 100%;
  margin: 0 0 0.25rem;
  font-size: 0.95rem;
  color: var(--gss-text-strong);
}
.license-alert {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  min-width: min(100%, 260px);
  padding: 0.55rem 0.75rem;
  border-radius: 9px;
  color: inherit;
  text-decoration: none;
  background: #fffdf6;
  font-size: 0.8rem;
}
.license-alert.is-soon strong {
  color: #93650a;
}
.license-alert.is-expired {
  background: #fff0ef;
}
.license-alert.is-expired strong {
  color: #a52834;
}
.license-renew-action {
  color: #8a5a00;
  border: 1px solid #e4bb5a;
  background: #fff6dc;
}
@media (max-width: 640px) {
  .license-alert {
    width: 100%;
  }
  .table {
    min-width: 1050px;
  }
}
</style>
