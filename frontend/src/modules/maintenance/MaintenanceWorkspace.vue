<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import api from '@/services/api'
import { getApiErrorMessage } from '@/services/apiErrors'
import BasePagination from '@/components/common/BasePagination.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import type { ResourceRow } from '@/components/common/resourceTypes'
import { maintenanceConfig } from './config'
const config = maintenanceConfig
const route = useRoute(),
  router = useRouter()
const rows = ref<ResourceRow[]>([]),
  record = ref<ResourceRow | null>(null)
interface MaintenanceDashboard {
  due_this_week: number
  overdue: number
  in_workshop: number
  completed_this_month: number
  completed_previous_month: number
  spend_this_month: { currency: string; total: string }[]
  month_label: string
  previous_month_label: string
}
const dashboard = ref<MaintenanceDashboard | null>(null)
const spend = computed(() => dashboard.value?.spend_this_month || [])
const spendValue = computed(() =>
  spend.value.length === 1
    ? Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(
        Number(spend.value[0]?.total),
      )
    : spend.value.length > 1
      ? '—'
      : '0',
)
const completionChange = computed(() =>
  dashboard.value
    ? dashboard.value.completed_this_month - dashboard.value.completed_previous_month
    : 0,
)
const total = ref(0),
  page = ref(1),
  loading = ref(false),
  saving = ref(false),
  error = ref(''),
  notice = ref(
    route.query.notice === 'saved'
      ? `${config.singular} saved successfully.`
      : route.query.notice === 'deleted'
        ? `${config.singular} deleted.`
        : '',
  )
const errors = ref<Record<string, string[]>>({}),
  form = reactive<Record<string, string | number>>({})
const options = ref<{ value: string; label: string }[]>([])
const filters = reactive<Record<string, string>>({})
const mode = computed(() =>
  route.path.endsWith('/create')
    ? 'create'
    : route.path.endsWith('/edit')
      ? 'edit'
      : route.params.id
        ? 'detail'
        : 'list',
)
const scope = computed(() => String(route.query.resource_type || 'daily'))
const scopedResource = computed(() => ['fleet', 'drivers'].includes(config.key))
const driverStatuses = ref<string[]>([])
const params = computed(() => (scopedResource.value ? { resource_type: scope.value } : {}))
const statuses = computed(() =>
  config.key === 'drivers'
    ? driverStatuses.value.length
      ? driverStatuses.value
      : config.statuses
    : config.key === 'fleet' && scope.value === 'monthly'
      ? [...config.statuses, 'Inactive']
      : config.statuses,
)
const fields = computed(() =>
  config.fields.map((field) =>
    field.key === config.statusKey ? { ...field, options: statuses.value } : field,
  ),
)
const link = (path: string) => ({ path, query: params.value })
const text = (value: unknown) => (value == null || value === '' ? '—' : String(value))
const details = computed(() => fields.value.filter((f) => f.key !== 'vehicle_key'))
const assignments = computed(() => record.value?.assignments || [])
const assignmentPath = computed(() =>
  scopedResource.value && scope.value === 'monthly' ? '/monthly-contracts' : '/daily-bookings',
)
let generation = 0
async function load(next = 1) {
  const version = ++generation
  loading.value = true
  error.value = ''
  errors.value = {}
  record.value = null
  rows.value = []
  dashboard.value = null
  try {
    if (config.key === 'drivers') {
      const response = await api.get(`${config.endpoint}/options`, { params: params.value })
      if (version !== generation) return
      driverStatuses.value = response.data.data.statuses
    }
    if (mode.value === 'list') {
      const query = { ...params.value, ...filters, page: next, per_page: 10 }
      const [list, summary] = await Promise.all([
        api.get(config.endpoint, { params: query }),
        api.get(`${config.endpoint}/summary`, { params: query }),
      ])
      if (version !== generation) return
      if (next > list.data.meta.last_page) {
        await load(Math.max(1, list.data.meta.last_page))
        return
      }
      rows.value = list.data.data
      total.value = list.data.meta.total
      page.value = list.data.meta.current_page
      dashboard.value = summary.data.data.dashboard
      if (config.key === 'maintenance') {
        const response = await api.get(`${config.endpoint}/options`)
        if (version !== generation) return
        options.value = response.data.data.vehicles.map((v: ResourceRow) => ({
          value: `${v.resource_type}:${v.id}`,
          label: `${v.plate_no} · ${v.model_name} (${v.resource_type})`,
        }))
      }
    } else {
      let value: ResourceRow | null = null
      if (mode.value !== 'create')
        value = (await api.get(`${config.endpoint}/${route.params.id}`, { params: params.value }))
          .data.data
      if (version !== generation) return
      record.value = value
      for (const key of Object.keys(form)) delete form[key]
      for (const field of fields.value)
        form[field.key] = (value?.[field.key] as string | number) ?? field.default ?? ''
      if (config.key === 'maintenance')
        form.vehicle_key = value
          ? value.car_id
            ? `daily:${value.car_id}`
            : `monthly:${value.monthly_car_id}`
          : String(route.query.vehicle || '')
      if (config.key === 'maintenance' && mode.value !== 'detail') {
        const response = await api.get(`${config.endpoint}/options`)
        if (version !== generation) return
        options.value = response.data.data.vehicles.map((v: ResourceRow) => ({
          value: `${v.resource_type}:${v.id}`,
          label: `${v.plate_no} · ${v.model_name} (${v.resource_type})`,
        }))
      }
    }
  } catch (cause) {
    if (version === generation) error.value = getApiErrorMessage(cause)
  } finally {
    if (version === generation) loading.value = false
  }
}
async function apply() {
  await router.replace({
    path: config.path,
    query: { ...params.value, ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)) },
  })
  await load()
}
async function reset() {
  for (const key of Object.keys(filters)) filters[key] = ''
  await apply()
}
async function shiftMonth(offset: number) {
  const current =
    filters.month ||
    new Intl.DateTimeFormat('sv-SE', {
      timeZone: 'Asia/Rangoon',
      year: 'numeric',
      month: '2-digit',
    }).format(new Date())
  const [year = 0, month = 1] = current.split('-').map(Number)
  const date = new Date(Date.UTC(year, month - 1 + offset, 1))
  filters.month = date.toISOString().slice(0, 7)
  await apply()
}
async function currentMonth() {
  filters.month = new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Rangoon',
    year: 'numeric',
    month: '2-digit',
  }).format(new Date())
  await apply()
}
async function save() {
  if (saving.value) return
  saving.value = true
  error.value = ''
  errors.value = {}
  try {
    const payload: Record<string, string | number | null> = Object.fromEntries(
      Object.entries(form).map(([k, v]) => [k, typeof v === 'string' ? v.trim() || null : v]),
    )
    if (config.key === 'maintenance') {
      const [type, id] = String(form.vehicle_key).split(':')
      payload.car_id = type === 'daily' ? Number(id) : null
      payload.monthly_car_id = type === 'monthly' ? Number(id) : null
      delete payload.vehicle_key
    }
    await api.request({
      method: mode.value === 'edit' ? 'put' : 'post',
      url: `${config.endpoint}${mode.value === 'edit' ? `/${route.params.id}` : ''}`,
      params: params.value,
      data: payload,
    })
    notice.value = `${config.singular} saved successfully.`
    await router.push({ path: config.path, query: { ...params.value, notice: 'saved' } })
  } catch (cause) {
    error.value = getApiErrorMessage(cause)
    if (axios.isAxiosError(cause)) {
      errors.value = cause.response?.data?.errors || cause.response?.data?.error?.errors || {}
      if (config.key === 'maintenance' && (errors.value.car_id || errors.value.monthly_car_id))
        errors.value.vehicle_key = [
          ...(errors.value.car_id || []),
          ...(errors.value.monthly_car_id || []),
        ]
    }
  } finally {
    saving.value = false
  }
}
const confirmDialog = ref<HTMLDialogElement | null>(null)
async function remove() {
  if (saving.value) return
  saving.value = true
  error.value = ''
  try {
    await api.delete(`${config.endpoint}/${route.params.id}`, { params: params.value })
    confirmDialog.value?.close()
    await router.push({ path: config.path, query: { ...params.value, notice: 'deleted' } })
  } catch (cause) {
    confirmDialog.value?.close()
    error.value = getApiErrorMessage(cause)
  } finally {
    saving.value = false
  }
}
watch(
  () => [route.path, route.query.resource_type],
  () => {
    for (const key of Object.keys(filters)) delete filters[key]
    for (const field of config.filters)
      filters[field.key] = String(route.query[field.key] || field.default || '')
    void load()
  },
  { immediate: true },
)
</script>

<template>
  <section class="fleet-module page-content resource-workspace" aria-label="Module management">
    <header class="page-heading">
      <div class="fleet-intro">
        <span class="fleet-intro-icon"><i :class="`bi ${config.icon}`" aria-hidden="true" /></span>
        <div>
          <h2 class="h4 mb-1">
            {{
              mode === 'list'
                ? config.title
                : `${mode === 'create' ? 'Add' : mode === 'edit' ? 'Edit' : ''} ${config.singular}`
            }}
          </h2>
          <p>{{ config.description }}</p>
        </div>
      </div>
      <RouterLink
        :to="link(mode === 'list' ? `${config.path}/create` : config.path)"
        class="btn-gold"
        >{{ mode === 'list' ? `+ Add ${config.singular}` : `All ${config.title}` }}</RouterLink
      >
    </header>
    <div v-if="notice" class="alert alert-success" role="status">
      {{ notice
      }}<button
        class="btn-close float-end"
        aria-label="Dismiss notification"
        @click="notice = ''"
      />
    </div>
    <nav
      v-if="scopedResource"
      class="fleet-tabs d-flex gap-2 mb-4"
      :aria-label="config.key === 'drivers' ? 'Driver resource' : 'Vehicle resource'"
    >
      <RouterLink
        v-for="type in ['daily', 'monthly']"
        :key="type"
        :to="{ path: config.path, query: { resource_type: type } }"
        class="btn"
        :class="scope === type ? 'btn-primary' : 'btn-outline-secondary'"
        :aria-current="scope === type ? 'page' : undefined"
        >{{
          type === 'daily'
            ? `Daily booking ${config.key === 'drivers' ? 'drivers' : 'vehicles'}`
            : `Monthly contract ${config.key === 'drivers' ? 'drivers' : 'vehicles'}`
        }}</RouterLink
      >
    </nav>
    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }}
      <button v-if="!saving" class="btn-outline-warm" @click="load(page)">Reload</button>
    </div>
    <template v-if="mode === 'list'">
      <div class="row g-3 mb-4" aria-label="Maintenance status summary">
        <div class="col-sm-6 col-xl-3">
          <article class="card-shell maintenance-metric">
            <span class="maintenance-metric-icon icon-due"
              ><i class="bi bi-calendar-event" aria-hidden="true"
            /></span>
            <h3>Due this week</h3>
            <strong class="numeric">{{ dashboard?.due_this_week ?? '—' }}</strong>
            <p class="metric-due">{{ dashboard?.overdue ?? 0 }} services overdue</p>
          </article>
        </div>
        <div class="col-sm-6 col-xl-3">
          <article class="card-shell maintenance-metric">
            <span class="maintenance-metric-icon icon-workshop"
              ><i class="bi bi-wrench-adjustable" aria-hidden="true"
            /></span>
            <h3>In workshop</h3>
            <strong class="numeric">{{ dashboard?.in_workshop ?? '—' }}</strong>
            <p class="metric-workshop">Vehicles unavailable</p>
          </article>
        </div>
        <div class="col-sm-6 col-xl-3">
          <article class="card-shell maintenance-metric">
            <span class="maintenance-metric-icon icon-completed"
              ><i class="bi bi-grid" aria-hidden="true"
            /></span>
            <h3>Completed this month</h3>
            <strong class="numeric">{{ dashboard?.completed_this_month ?? '—' }}</strong>
            <p class="metric-completed">
              {{ completionChange >= 0 ? '↑' : '↓' }} {{ Math.abs(completionChange) }} vs
              {{ dashboard?.previous_month_label || 'previous month' }}
            </p>
          </article>
        </div>
        <div class="col-sm-6 col-xl-3">
          <article class="card-shell maintenance-metric">
            <span class="maintenance-metric-icon icon-spend"
              ><i class="bi bi-credit-card" aria-hidden="true"
            /></span>
            <h3>Maintenance spend</h3>
            <strong class="numeric" :title="spend.length === 1 ? spend[0]?.total : undefined">{{
              spendValue
            }}</strong>
            <p class="metric-spend" v-if="spend.length <= 1">
              {{ spend[0]?.currency || 'No spend' }} ·
              {{ dashboard?.month_label || 'This month' }} to date
            </p>
            <p class="metric-spend" v-else>
              {{
                spend
                  .map((item) => `${item.currency} ${Number(item.total).toLocaleString()}`)
                  .join(' · ')
              }}
            </p>
          </article>
        </div>
      </div>
      <section class="card-shell p-4 mb-4" aria-label="Search and filters">
        <form class="row g-3" @submit.prevent="apply">
          <div v-for="filter in config.filters" :key="filter.key" class="col-sm-6 col-lg-3">
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
            <button class="btn-gold" :disabled="loading">Filter</button
            ><button type="button" class="btn-outline-warm" :disabled="loading" @click="reset">
              Reset
            </button>
            <div
              v-if="config.key === 'drivers'"
              class="resource-month-nav d-flex flex-wrap align-items-center gap-2 ms-sm-auto"
              aria-label="Assignment month navigation"
            >
              <button
                type="button"
                class="btn-outline-warm"
                aria-label="Previous assignment month"
                :disabled="loading"
                @click="shiftMonth(-1)"
              >
                ‹ Previous month
              </button>
              <span class="numeric">{{ filters.month || 'Current month' }}</span>
              <button
                type="button"
                class="btn-outline-warm"
                aria-label="Next assignment month"
                :disabled="loading"
                @click="shiftMonth(1)"
              >
                Next month ›
              </button>
              <button
                type="button"
                class="btn-outline-warm"
                :disabled="loading"
                @click="currentMonth"
              >
                Today
              </button>
            </div>
          </div>
        </form>
      </section>
      <section class="card-shell" aria-label="Results" :aria-busy="loading">
        <div class="p-4">
          <h2 class="h5 mb-0">
            {{ config.title }} <span class="text-muted">({{ total }})</span>
          </h2>
        </div>
        <p v-if="loading" class="p-4" role="status">Loading {{ config.title.toLowerCase() }}…</p>
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
                <th v-if="scopedResource" scope="col">Assignments in selected month</th>
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
                <td v-if="scopedResource">
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
            :disabled="loading"
            @update:page="load"
          />
        </div>
      </section>
    </template>
    <p v-else-if="loading" role="status">Loading…</p>
    <section v-else-if="mode === 'create' || record" class="card-shell p-4">
      <template v-if="mode === 'detail' && record">
        <h2 class="h5">Record details</h2>
        <dl class="row">
          <template v-if="config.key === 'drivers' && record.license_no"
            ><dt class="col-sm-4">Existing license number</dt>
            <dd class="col-sm-8">{{ record.license_no }}</dd></template
          >
          <template v-if="config.key === 'fleet'"
            ><dt class="col-sm-4">Availability today</dt>
            <dd class="col-sm-8"><BaseStatusBadge :status="text(record.effective_status)" /></dd
          ></template>
          <template v-for="field in details" :key="field.key"
            ><dt class="col-sm-4">{{ field.label }}</dt>
            <dd class="col-sm-8 preserve-lines">{{ text(record[field.key]) }}</dd></template
          ><template v-if="config.key === 'maintenance'"
            ><dt class="col-sm-4">Vehicle</dt>
            <dd class="col-sm-8">
              <RouterLink
                :to="{
                  path: `/fleet/${record.car_id || record.monthly_car_id}`,
                  query: { resource_type: record.car_id ? 'daily' : 'monthly' },
                }"
                >{{ record.vehicle_label }}</RouterLink
              >
            </dd></template
          >
        </dl>
        <template
          v-if="scopedResource || config.key === 'operators' || config.key === 'maintenance'"
          ><h2 class="h5 mt-4">
            {{
              config.key === 'maintenance'
                ? 'Vehicle assignments on scheduled date'
                : 'Assignment history'
            }}
          </h2>
          <p v-if="!assignments.length" class="text-muted">No assignment history.</p>
          <ul class="list-group list-group-flush">
            <li
              v-for="a in assignments"
              :key="`${a.resource_type}-${a.id}`"
              class="list-group-item"
            >
              <RouterLink
                :to="`${a.resource_type === 'monthly' ? '/monthly-contracts' : assignmentPath}/${a.id}`"
                >{{ a.name }}</RouterLink
              >
              · {{ a.start_date }} – {{ a.end_date }} · {{ a.status }}
              <div v-if="a.vehicle || a.driver" class="small text-muted">
                {{ a.vehicle }}{{ a.secondary_vehicle ? ` / ${a.secondary_vehicle}` : ''
                }}{{ a.driver ? ` · Driver: ${a.driver}` : '' }}
              </div>
            </li>
          </ul></template
        >
        <template v-if="config.key === 'fleet'"
          ><h2 class="h5 mt-4">Maintenance history</h2>
          <p v-if="!record.maintenances?.length" class="text-muted">No maintenance history.</p>
          <ul class="list-group list-group-flush">
            <li v-for="m in record.maintenances" :key="m.id" class="list-group-item">
              <RouterLink :to="`/maintenance/${m.id}`">{{ m.title }}</RouterLink> ·
              {{ m.scheduled_date }} · {{ m.status }}
            </li>
          </ul>
          <RouterLink
            :to="{ path: '/maintenance/create', query: { vehicle: `${scope}:${record.id}` } }"
            class="btn-outline-warm mt-3"
            >Schedule maintenance</RouterLink
          >
          <h2 class="h5 mt-4">Insurance history</h2>
          <p v-if="!record.insurance_policies?.length" class="text-muted">No insurance history.</p>
          <ul class="list-group list-group-flush">
            <li v-for="p in record.insurance_policies" :key="p.id" class="list-group-item">
              <RouterLink :to="`/insurance/${p.id}`">Policy #{{ p.id }}</RouterLink>
            </li>
          </ul></template
        >
        <div class="d-flex gap-2 mt-4">
          <RouterLink :to="link(`${config.path}/${record.id}/edit`)" class="btn-gold"
            >Edit {{ config.singular }}</RouterLink
          ><button class="btn-outline-warm" @click="confirmDialog?.showModal()">Delete</button>
        </div>
      </template>
      <form v-else class="row g-3" @submit.prevent="save">
        <p class="text-muted mb-0">{{ config.formHelp }}</p>
        <fieldset class="row g-3 border-0 m-0 p-0" :disabled="saving">
          <div
            v-for="field in fields"
            :key="field.key"
            :class="field.type === 'textarea' ? 'col-12' : 'col-md-6'"
          >
            <label :for="`field-${field.key}`" class="form-label"
              >{{ field.label }}<span v-if="field.required" aria-hidden="true"> *</span></label
            ><select
              v-if="field.options || field.key === 'vehicle_key'"
              :id="`field-${field.key}`"
              v-model="form[field.key]"
              class="form-select"
              :required="field.required"
              :class="{ 'is-invalid': errors[field.key] }"
              :aria-invalid="!!errors[field.key]"
              :aria-describedby="`error-${field.key}`"
            >
              <option value="">Select {{ field.label.toLowerCase() }}</option>
              <template v-if="field.key === 'vehicle_key'"
                ><option v-for="option in options" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option></template
              ><template v-else
                ><option v-for="option in field.options" :key="option">
                  {{ option }}
                </option></template
              ></select
            ><textarea
              v-else-if="field.type === 'textarea'"
              :id="`field-${field.key}`"
              v-model="form[field.key]"
              class="form-control"
              rows="4"
              :maxlength="field.max || 20000"
              :class="{ 'is-invalid': errors[field.key] }"
              :aria-invalid="!!errors[field.key]"
              :aria-describedby="`error-${field.key}`"
            /><input
              v-else
              :id="`field-${field.key}`"
              v-model="form[field.key]"
              :type="field.type || 'text'"
              :required="field.required"
              :maxlength="field.max"
              :min="field.min"
              :max="field.maxNumber"
              :step="field.step"
              class="form-control"
              :class="{ 'is-invalid': errors[field.key] }"
              :aria-invalid="!!errors[field.key]"
              :aria-describedby="`error-${field.key}`"
            />
            <div :id="`error-${field.key}`" class="invalid-feedback">
              {{ errors[field.key]?.join(' ') }}
            </div>
          </div>
          <div class="col-12">
            <button class="btn-gold">{{ saving ? 'Saving…' : `Save ${config.singular}` }}</button
            ><RouterLink :to="link(config.path)" class="btn-outline-warm ms-2">Cancel</RouterLink>
          </div>
        </fieldset>
      </form>
    </section>
    <dialog
      ref="confirmDialog"
      class="resource-confirm card-shell p-4"
      aria-labelledby="delete-title"
    >
      <h2 id="delete-title" class="h5">Delete {{ config.singular }}?</h2>
      <p>Referenced records and maintenance service history are protected.</p>
      <div class="d-flex gap-2">
        <button
          autofocus
          class="btn-outline-warm"
          :disabled="saving"
          @click="confirmDialog?.close()"
        >
          Keep record</button
        ><button class="btn-gold" :disabled="saving" @click="remove">
          {{ saving ? 'Deleting…' : 'Delete record' }}
        </button>
      </div>
    </dialog>
  </section>
</template>
<style scoped>
.numeric,
.table {
  font-variant-numeric: tabular-nums;
}
.maintenance-metric {
  position: relative;
  min-height: 124px;
  height: 100%;
  padding: 18px;
  border-radius: 14px;
  box-shadow: 0 1px 2px var(--gss-shadow-tint-subtle);
}
.maintenance-metric h3 {
  margin: 0 40px 12px 0;
  color: var(--muted);
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1.4;
}
.maintenance-metric strong {
  display: block;
  color: var(--ink);
  font-size: 1.85rem;
  line-height: 1.1;
}
.maintenance-metric p {
  margin: 10px 0 0;
  font-size: 0.7rem;
  line-height: 1.4;
  overflow-wrap: anywhere;
}
.maintenance-metric-icon {
  position: absolute;
  top: 17px;
  right: 17px;
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 9px;
  font-size: 1rem;
}
.icon-due {
  color: #a75a00;
  background: #fff0cf;
}
.icon-workshop {
  color: #6849b3;
  background: #eee8fa;
}
.icon-completed {
  color: #0c7850;
  background: #ddf4e9;
}
.icon-spend {
  color: #265b9d;
  background: #e6effb;
}
.metric-due {
  color: #a75a00;
}
.metric-workshop {
  color: #6849b3;
}
.metric-completed {
  color: #0c7850;
}
.metric-spend {
  color: #265b9d;
}
.resource-workspace h2 {
  text-wrap: balance;
}
.table td {
  overflow-wrap: anywhere;
  line-height: 1.5;
}
.table td:last-child {
  white-space: nowrap;
}
.preserve-lines {
  white-space: pre-wrap;
}
.resource-confirm {
  max-width: min(500px, calc(100vw - 32px));
  color: var(--ink);
  background: var(--paper-strong);
  border: 1px solid var(--line);
}
.resource-confirm::backdrop {
  background: var(--gss-palette-24160e80);
}
.resource-workspace :is(button, a, input, select, textarea):focus-visible {
  outline: 2px solid var(--cocoa);
  outline-offset: 3px;
}
.resource-workspace :is(button, a) {
  transition:
    background-color 0.15s ease,
    color 0.15s ease,
    box-shadow 0.15s ease;
}
.resource-workspace .gss-status {
  white-space: nowrap;
}
@media (prefers-reduced-motion: reduce) {
  .resource-workspace :is(button, a) {
    transition: none;
  }
}
@media (max-width: 575px) {
  .resource-month-nav {
    width: 100%;
    margin-top: 0.5rem;
  }
}
</style>
