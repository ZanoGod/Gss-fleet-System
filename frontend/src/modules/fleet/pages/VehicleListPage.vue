<script setup lang="ts">
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import { displayValue as text, useModuleList } from '@/modules/shared/moduleRequests'
import { fleetConfig as config } from '../config'
import { useVehicleScope } from '../useVehicleScope'
import { fleetMetrics, type OverviewSummary } from '../overview'

const { scope, query, assignmentPath, statuses } = useVehicleScope()
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
} = useModuleList(config, { query, readSummary: (data) => data as OverviewSummary })
</script>

<template>
  <section class="fleet-module page-content resource-workspace" aria-label="Module management">
    <div class="d-flex flex-wrap justify-content-end">
      <nav class="fleet-tabs d-flex gap-2 mb-4" aria-label="Vehicle resource">
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
      <form class="row g-3" @submit.prevent="run('filter', apply)">
        <div v-for="filter in config.filters" :key="filter.key" class="col-sm-6 col-lg-3">
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
        </div>
        <div class="col-12 d-flex flex-wrap align-items-center gap-2">
          <button class="btn btn-primary" :disabled="initial || pending('filter')">
            <BaseLoading v-if="pending('filter')" label="Filtering…" /><template v-else
              >Filter</template
            ></button
          ><button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="initial || pending('reset')"
            @click="run('reset', reset)"
          >
            <BaseLoading v-if="pending('reset')" label="Resetting…" /><template v-else
              >Reset</template
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
                  v-if="column.key === config.statusKey || column.key === 'effective_status'"
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
                <RouterLink :to="link(`${config.path}/${row.id}`)" class="btn-outline-warm"
                  >View</RouterLink
                ><RouterLink
                  :to="link(`${config.path}/${row.id}/edit`)"
                  class="btn-outline-warm ms-2"
                  >Edit</RouterLink
                >
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
