<script setup lang="ts">
import BaseLoading from '@/components/common/BaseLoading.vue'
import { computed } from 'vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import '@/components/common/moduleGlass.css'
import { navigateSegments } from '@/components/common/navigateSegments'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import RecordAction from '@/components/common/RecordAction.vue'
import { displayValue as text, useModuleList } from '@/modules/shared/moduleRequests'
import { driverConfig as config } from '../config'
import { useDriverScope } from '../useDriverScope'
import { driversMetrics, type OverviewSummary } from '../overview'
import { currentMonth, shiftMonth as shiftMonthValue } from '@/modules/customers/types'

const { scope, query, assignmentPath, statuses, prepare } = useDriverScope()
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
} = useModuleList(config, { query, prepare, readSummary: (data) => data as OverviewSummary })
const monthValue = computed(() => filters.month || currentMonth())
const monthLabel = computed(() => {
  const [year, month] = monthValue.value.split('-').map(Number)
  return new Intl.DateTimeFormat(undefined, {
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
  }).format(new Date(Date.UTC(year!, month! - 1, 1)))
})
function shiftMonth(offset: number) {
  filters.month = shiftMonthValue(monthValue.value, offset)
}
function setCurrentMonth() {
  filters.month = currentMonth()
}
function reset() {
  for (const filter of config.filters) filters[filter.key] = String(filter.default ?? '')
  return apply()
}
</script>

<template>
  <section
    class="fleet-module page-content resource-workspace glass-module"
    aria-label="Module management"
  >
    <header class="module-heading driver-module-heading">
      <div>
        <h1>Drivers</h1>
        <p>{{ config.description }}</p>
      </div>
      <div class="driver-heading-actions">
        <nav
          class="glass-segments driver-segments"
          aria-label="Driver resource"
          :data-active-index="scope === 'monthly' ? 1 : 0"
          @keydown="navigateSegments"
        >
          <RouterLink
            v-for="type in ['daily', 'monthly']"
            :key="type"
            :to="{ path: config.path, query: { resource_type: type } }"
            :aria-current="scope === type ? 'page' : undefined"
          >{{ type === 'daily' ? 'Daily booking drivers' : 'Monthly contract drivers' }}</RouterLink>
        </nav>
        <RouterLink :to="link(`${config.path}/create`)" class="btn btn-primary">
          <i class="bi bi-person-plus me-1" aria-hidden="true" /> Add driver
        </RouterLink>
      </div>
    </header>
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
      :items="driversMetrics(summary)"
      :loading="initial"
      label="drivers status summary"
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
                @click="shiftMonth(-1)"
              >
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
              </button>
              <span class="vehicle-month-label" aria-live="polite">{{ monthLabel }}</span>
              <button
                type="button"
                class="btn btn-outline-secondary vehicle-month-arrow"
                aria-label="Next assignment month"
                @click="shiftMonth(1)"
              >
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
              </button>
              <button
                type="button"
                class="btn btn-outline-secondary vehicle-month-today"
                :disabled="monthValue === currentMonth()"
                @click="setCurrentMonth"
              >
                Today
              </button>
            </div>
          </template>
          <template v-else>
            <label :for="`filter-${filter.key}`" class="form-label">{{ filter.label }}</label>
            <select
              v-if="filter.options"
              :id="`filter-${filter.key}`"
              v-model="filters[filter.key]"
              class="form-select"
            >
              <option value="">All</option>
              <option
                v-for="option in filter.key === 'status' ? statuses : filter.options"
                :key="option"
              >{{ option }}</option>
            </select>
            <input
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
          <button
            class="btn module-filter-apply"
            :disabled="initial || pending('filter')"
            :aria-busy="pending('filter')"
          >
            <BaseLoading v-if="pending('filter')" label="Filtering…" />
            <template v-else><i class="bi bi-funnel" aria-hidden="true"></i> Filter</template>
          </button>
          <span class="module-filter-divider" aria-hidden="true"></span>
          <button
            type="button"
            class="btn module-filter-reset"
            :disabled="initial || pending('reset')"
            :aria-busy="pending('reset')"
            @click="run('reset', reset)"
          >
            <BaseLoading v-if="pending('reset')" label="Resetting…" />
            <template v-else><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</template>
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
        :columns="config.columns.length + 2"
      />
      <div v-else-if="rows.length" class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <caption class="visually-hidden">
            {{
              config.title
            }}
            search results
          </caption>
          <thead>
            <tr>
              <th v-for="column in config.columns" :key="column.key" scope="col">
                {{ column.label }}
              </th>
              <th scope="col">Assignments in selected month</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td v-for="column in config.columns" :key="column.key">
                <BaseStatusBadge
                  v-if="column.key === config.statusKey"
                  :status="text(row[column.key])"
                /><span v-else>{{ text(row[column.key]) }}</span>
              </td>
              <td>
                <div v-for="assignment in row.assignments" :key="assignment.id" class="mb-2">
                  <RouterLink :to="`${assignmentPath}/${assignment.id}`">{{
                    assignment.name
                  }}</RouterLink>
                  <div class="small">
                    {{ assignment.start_date }} – {{ assignment.end_date }} ·
                    {{ assignment.status }}
                  </div>
                  <div v-if="assignment.vehicle || assignment.driver" class="small text-muted">
                    {{ assignment.vehicle
                    }}{{ assignment.secondary_vehicle ? ` / ${assignment.secondary_vehicle}` : ''
                    }}{{ assignment.driver ? ` · Driver: ${assignment.driver}` : '' }}
                  </div>
                </div>
                <span v-if="!row.assignments?.length" class="text-muted">No assignments</span>
              </td>
              <td>
                <div class="record-actions">
                  <RecordAction action="view" icon-only :aria-label="`View driver ${text(row.full_name)}`" :to="link(`${config.path}/${row.id}`)" />
                  <RecordAction action="edit" icon-only :aria-label="`Edit driver ${text(row.full_name)}`" :to="link(`${config.path}/${row.id}/edit`)" />
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
          :pending-page="(target) => pending(`page:${target}`)"
          @update:page="run(`page:${$event}`, () => load($event))"
        />
      </div>
    </section>
  </section>
</template>
