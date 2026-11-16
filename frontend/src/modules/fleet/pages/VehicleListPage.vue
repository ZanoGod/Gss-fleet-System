<script setup lang="ts">
import { computed, ref } from 'vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import RecordAction from '@/components/common/RecordAction.vue'
import { displayValue as text, useModuleList } from '@/modules/shared/moduleRequests'
import { fleetConfig as config } from '../config'
import { useVehicleScope } from '../useVehicleScope'
import { fleetMetrics, type OverviewSummary } from '../overview'
import { currentMonth, shiftMonth } from '@/modules/customers/types'
import api from '@/services/api'
import { getApiErrorMessage } from '@/services/apiErrors'
import { toast } from '@/services/toast'

const { scope, query, statuses } = useVehicleScope()
const {
  rows,
  summary,
  total,
  page,
  loading,
  initial,
  error,
  filters,
  link,
  pending,
  run,
  load,
  apply,
} = useModuleList(config, { query, readSummary: (data) => data as OverviewSummary })

const monthValue = computed(() => filters.month || currentMonth())
const monthLabel = computed(() => {
  const [year, value] = monthValue.value.split('-').map(Number)
  return new Intl.DateTimeFormat(undefined, {
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
  }).format(new Date(Date.UTC(year!, value! - 1, 1)))
})
const deletingId = ref<number | null>(null)

function assignmentDate(value: string) {
  return new Intl.DateTimeFormat(undefined, {
    day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC',
  }).format(new Date(`${value}T00:00:00Z`))
}

async function deleteVehicle(row: Record<string, unknown>) {
  const id = Number(row.id)
  const plate = text(row.plate_no)
  if (!window.confirm(`Delete vehicle ${plate}?`)) return
  deletingId.value = id
  try {
    await api.delete(`${config.endpoint}/${id}`, { params: query() })
    toast.success('Vehicle deleted.')
    await load(page.value)
  } catch (cause) {
    toast.error(getApiErrorMessage(cause))
  } finally {
    deletingId.value = null
  }
}

function changeMonth(amount: number) {
  filters.month = shiftMonth(monthValue.value, amount)
}

function resetVehicleFilters() {
  for (const filter of config.filters) filters[filter.key] = String(filter.default ?? '')
  return apply()
}
</script>

<template>
  <section class="fleet-module page-content resource-workspace glass-module" aria-label="Module management">
    <div class="vehicle-list-toolbar">
      <nav
        class="glass-segments vehicle-segments mb-4"
        aria-label="Vehicle resource"
        :data-active-index="scope === 'monthly' ? 1 : 0"
      >
        <RouterLink
          v-for="type in ['daily', 'monthly']"
          :key="type"
          :to="{ path: config.path, query: { resource_type: type } }"
          class="btn"
          :class="scope === type ? 'btn-primary' : 'btn-outline-secondary'"
          :aria-current="scope === type ? 'page' : undefined"
          >{{
            type === 'daily' ? `Daily booking vehicles` : `Monthly contract vehicles`
          }}</RouterLink
        >
      </nav>
      <PageHeader :title="config.title" :description="config.description" :icon="config.icon">
        <RouterLink :to="link(`${config.path}/create`)" class="btn btn-primary">{{
          `+ Add ${config.singular}`
        }}</RouterLink>
      </PageHeader>
    </div>
    <button
      v-if="error"
      class="btn-outline-warm mb-3"
      :disabled="pending('reload')"
      @click="run('reload', () => load(page))"
    >
      <BaseLoading v-if="pending('reload')" label="Reloading…" /><template v-else
        >Retry loading</template
      >
    </button>
    <StatusSummary
      :items="fleetMetrics(summary, scope)"
      :loading="initial"
      label="Cars status summary"
    />
    <section class="card-shell p-4 mb-4" aria-label="Search and filters">
      <form class="vehicle-filter-grid" @submit.prevent="run('filter', apply)">
        <div
          v-for="filter in config.filters"
          :key="filter.key"
          class="vehicle-filter-field"
          :class="{ 'vehicle-filter-field--month': filter.key === 'month' }"
        >
          <template v-if="filter.key === 'month'">
            <span class="form-label d-block">{{ filter.label }}</span>
            <div class="vehicle-month-controls">
              <button
                type="button"
                class="btn btn-outline-secondary vehicle-month-arrow"
                aria-label="Previous assignment month"
                @click="changeMonth(-1)"
              ><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
              <span class="vehicle-month-label" aria-live="polite">{{ monthLabel }}</span>
              <button
                type="button"
                class="btn btn-outline-secondary vehicle-month-arrow"
                aria-label="Next assignment month"
                @click="changeMonth(1)"
              ><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
              <button
                type="button"
                class="btn btn-outline-secondary vehicle-month-today"
                :disabled="monthValue === currentMonth()"
                @click="filters.month = currentMonth()"
              >Today</button>
            </div>
          </template>
          <template v-else>
          <label :for="`filter-${filter.key}`" class="form-label">{{ filter.label }}</label
          ><select
            v-if="filter.options"
            :id="`filter-${filter.key}`"
            v-model="filters[filter.key]"
            class="form-select"
          >
            <option value="">All</option>
            <option
              v-for="option in filter.key === 'status' ? statuses : filter.options"
              :key="option"
            >
              {{ option }}
            </option></select
          ><input
            v-else
            :id="`filter-${filter.key}`"
            v-model="filters[filter.key]"
            :type="filter.type || 'search'"
            class="form-control"
            :placeholder="filter.placeholder"
            maxlength="500"
          />
          </template>
        </div>
        <div class="module-filter-actions vehicle-filter-actions">
          <button class="btn module-filter-apply" :disabled="initial || pending('filter')" :aria-busy="pending('filter')">
            <BaseLoading v-if="pending('filter')" label="Filtering…" /><template v-else><i class="bi bi-funnel" aria-hidden="true"></i> Filter</template>
          </button>
          <span class="module-filter-divider" aria-hidden="true"></span>
          <button type="button" class="btn module-filter-reset" :disabled="initial || pending('reset')" :aria-busy="pending('reset')" @click="run('reset', resetVehicleFilters)">
            <BaseLoading v-if="pending('reset')" label="Resetting…" /><template v-else><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</template>
          </button>
        </div>
      </form>
    </section>
    <section class="card-shell" aria-label="Results">
      <div class="p-4">
        <h2 class="h5 mb-0">
          {{ config.title }} <span class="text-muted">({{ total }})</span>
        </h2>
      </div>
      <BaseLoading
        v-if="loading && !initial"
        class="d-block px-3 py-2"
        :label="`Updating ${config.title.toLowerCase()}…`"
      />
      <PageSkeleton
        v-if="initial"
        :label="`Loading ${config.title.toLowerCase()}`"
        :columns="7"
      />
      <div v-else-if="rows.length" class="table-responsive">
        <table class="table table-hover align-middle mb-0 classic-fleet-table">
          <caption class="visually-hidden">
            {{
              config.title
            }}
            search results
          </caption>
          <thead>
            <tr>
              <th scope="col">Car Type</th>
              <th scope="col">Plate Number</th>
              <th scope="col">Status</th>
              <th scope="col">Start Date</th>
              <th scope="col">End Date</th>
              <th scope="col">Guest / Company</th>
              <th scope="col" class="classic-fleet-actions-cell">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td>{{ text(row.car_type) }}</td>
              <td>{{ text(row.plate_no) }}</td>
              <td><span class="classic-fleet-status">{{ text(row.availability_status) }}</span></td>
              <td>
                <div v-for="(assignment, index) in row.assignments" :key="assignment.id" class="classic-assignment-value">
                  <span v-if="row.assignments && row.assignments.length > 1" class="classic-assignment-index">{{ index + 1 }}</span>
                  <span>{{ assignmentDate(assignment.start_date) }}</span>
                </div>
                <span v-if="!row.assignments?.length" class="text-muted">—</span>
              </td>
              <td>
                <div v-for="(assignment, index) in row.assignments" :key="assignment.id" class="classic-assignment-value">
                  <span v-if="row.assignments && row.assignments.length > 1" class="classic-assignment-index">{{ index + 1 }}</span>
                  <span>{{ assignmentDate(assignment.end_date) }}</span>
                </div>
                <span v-if="!row.assignments?.length" class="text-muted">—</span>
              </td>
              <td>
                <div v-for="(assignment, index) in row.assignments" :key="assignment.id" class="classic-assignment-value">
                  <span v-if="row.assignments && row.assignments.length > 1" class="classic-assignment-index">{{ index + 1 }}</span>
                  <span class="classic-assignment-company">{{ assignment.name }}</span>
                </div>
                <span v-if="!row.assignments?.length" class="text-muted">—</span>
              </td>
              <td class="classic-fleet-actions-cell">
                <div class="record-actions">
                  <RecordAction action="view" label="View" :to="link(`${config.path}/${row.id}`)" />
                  <RecordAction action="edit" label="Edit" :to="link(`${config.path}/${row.id}/edit`)" />
                  <RecordAction action="delete" label="Delete" :loading="deletingId === row.id" :disabled="deletingId !== null && deletingId !== row.id" @click="deleteVehicle(row)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-else-if="!error" class="p-4 text-muted">
        No {{ config.title.toLowerCase() }} found. Add a record or adjust your filters.
      </p>
      <div class="p-3">
        <BasePagination
          :page="page"
          :per-page="10"
          :total="total"
          :disabled="loading"
          numbered-only
          :pending-page="(target) => pending(`page:${target}`)"
          @update:page="run(`page:${$event}`, () => load($event))"
        />
      </div>
    </section>
  </section>
</template>

<style scoped>
.fleet-module .classic-fleet-table {
  --bs-table-bg: #fffdfa;
  --bs-table-color: var(--gss-text-body);
  --bs-table-border-color: #eadfd4;
  --bs-table-hover-bg: #fff5df;
  min-width: 1100px;
  font-size: 0.88rem;
  line-height: 1.45;
}
.fleet-module .classic-fleet-table thead th {
  padding: 13px 14px;
  border-color: #754027;
  background: #542512;
  color: #fff8ef;
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: 0;
  text-transform: none;
}
.fleet-module .classic-fleet-table tbody td {
  padding: 14px;
  border-color: #eee4da;
  color: var(--gss-text-body);
  font-weight: 500;
  vertical-align: top;
}
.fleet-module .classic-fleet-table tbody td:nth-child(4),
.fleet-module .classic-fleet-table tbody td:nth-child(5) {
  font-variant-numeric: tabular-nums;
}
.fleet-module .classic-fleet-table :is(th, td).classic-fleet-actions-cell {
  width: 1%;
  white-space: nowrap;
  text-align: left;
}
.fleet-module .classic-fleet-table .text-muted {
  color: var(--gss-table-header-text) !important;
}
.classic-assignment-company {
  color: var(--gss-text-body);
  overflow-wrap: anywhere;
}
.fleet-module .vehicle-filter-grid .form-control::placeholder {
  color: var(--gss-text-muted);
  opacity: 1;
}
.fleet-module .classic-fleet-table tbody tr:nth-child(even) {
  --bs-table-bg: #fffefa;
}
.fleet-module .classic-fleet-table tbody tr:hover {
  --bs-table-bg: #fff5df;
}
.classic-fleet-status {
  display: inline-block;
  padding: 0.34rem 0.82rem;
  border-radius: 999px;
  background: #fff0ba;
  color: #87500c;
  font-size: 0.78rem;
  font-weight: 700;
  white-space: nowrap;
}
.classic-assignment-value {
  display: flex;
  align-items: center;
  gap: 0.42rem;
  min-height: 1.72rem;
  line-height: 1.45;
}
.classic-assignment-value > span:not(.classic-assignment-index) {
  white-space: nowrap;
}
.classic-assignment-index {
  display: inline-flex;
  flex: 0 0 1.15rem;
  width: 1.15rem;
  height: 1.15rem;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #ffedcf;
  color: #75451d;
  font-size: 0.75rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
.fleet-module .classic-fleet-table .record-actions {
  justify-content: flex-start;
  flex-wrap: nowrap;
  gap: 0.35rem;
}
.fleet-module .classic-fleet-table .record-action {
  padding: 0.35rem 0.55rem;
  border-radius: 0.55rem;
  font-size: 0.8rem;
}
@media (max-width: 767.98px) {
  .fleet-module .classic-fleet-table { min-width: 1100px; }
}
</style>
