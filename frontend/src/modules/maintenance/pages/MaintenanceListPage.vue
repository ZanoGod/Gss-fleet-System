<script setup lang="ts">
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import RecordAction from '@/components/common/RecordAction.vue'
import { displayValue as text, useModuleList } from '@/modules/shared/moduleRequests'
import { maintenanceConfig as config } from '../config'
import { maintenanceMetrics, readMaintenanceDashboard } from '../overview'
import { useMaintenanceVehicles } from '../useMaintenanceVehicles'

const { options, loadVehicles } = useMaintenanceVehicles()
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
  reset,
} = useModuleList(config, {
  readSummary: readMaintenanceDashboard,
  afterList: loadVehicles,
})
</script>

<template>
  <section class="fleet-module page-content resource-workspace" aria-label="Module management">
    <div class="d-flex flex-wrap justify-content-end">
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
      :items="maintenanceMetrics(summary)"
      :loading="initial"
      label="Maintenance status summary"
    />
    <section class="card-shell p-4 mb-4" aria-label="Search and filters">
      <form class="row g-3 maintenance-filter-grid" @submit.prevent="run('filter', apply)">
        <div v-for="filter in config.filters" :key="filter.key" class="maintenance-filter-field">
          <label :for="`filter-${filter.key}`" class="form-label">{{ filter.label }}</label
          ><select
            v-if="filter.key === 'vehicle'"
            :id="`filter-${filter.key}`"
            v-model="filters[filter.key]"
            class="form-select"
          >
            <option value="">All vehicles</option>
            <option v-for="option in options" :key="option.value" :value="option.value">
              {{ option.label }}
            </option></select
          ><select
            v-else-if="filter.options"
            :id="`filter-${filter.key}`"
            v-model="filters[filter.key]"
            class="form-select"
          >
            <option value="">All</option>
            <option v-for="option in filter.options" :key="option">
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
        </div>
        <div class="module-filter-actions maintenance-filter-actions">
          <button class="btn module-filter-apply" :disabled="initial || pending('filter')">
            <BaseLoading v-if="pending('filter')" label="Filtering…" /><template v-else
              ><i class="bi bi-funnel" aria-hidden="true"></i> Filter</template
            ></button
          ><span class="module-filter-divider" aria-hidden="true"></span
          ><button
            type="button"
            class="btn module-filter-reset"
            :disabled="initial || pending('reset')"
            @click="run('reset', reset)"
          >
            <BaseLoading v-if="pending('reset')" label="Resetting…" /><template v-else
              ><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</template
            >
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
        :columns="config.columns.length + 1"
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
                <div class="record-actions">
                  <RecordAction action="view" icon-only :aria-label="`View maintenance record ${row.id}`" :to="link(`${config.path}/${row.id}`)" />
                  <RecordAction action="edit" icon-only :aria-label="`Edit maintenance record ${row.id}`" :to="link(`${config.path}/${row.id}/edit`)" />
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
