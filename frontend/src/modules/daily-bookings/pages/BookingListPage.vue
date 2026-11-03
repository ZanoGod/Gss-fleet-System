<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useDailyBookingStore } from '../stores/dailyBookings'
import StatusCard from '@/components/common/StatusCard.vue'
import { dailyBookingApi } from '../api'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
const store = useDailyBookingStore(),
  filters = reactive({
    search: '',
    status: '',
    date_from: '',
    date_to: '',
    car_id: '',
    driver_id: '',
  })
const summaryCards = [
  { label: 'Total bookings', status: '', icon: 'bi-calendar-check' },
  { label: 'Pending', status: 'Pending', icon: 'bi-hourglass-split' },
  { label: 'Confirmed', status: 'Confirm', icon: 'bi-check-circle' },
  { label: 'Completed', status: 'Completed', icon: 'bi-calendar2-check' },
  { label: 'Cancelled', status: 'Cancelled', icon: 'bi-x-circle' },
]
const summary = ref<(number | null)[]>(summaryCards.map(() => null))
const summaryLoading = ref(false)
let summaryGeneration = 0
async function loadSummary() {
  const generation = ++summaryGeneration
  summaryLoading.value = true
  summary.value = summaryCards.map(() => null)
  // API paginator totals cover all matching bookings, not just the visible page.
  // The status selector filters the table; the summary keeps all statuses visible.
  const query = { ...filters, page: 1, per_page: 1 }
  const results = await Promise.allSettled(summaryCards.map(card =>
    dailyBookingApi.list({ ...query, status: card.status }),
  ))
  if (generation !== summaryGeneration) return
  summary.value = results.map(result => result.status === 'fulfilled' && Number.isFinite(result.value.total)
    ? result.value.total : null)
  summaryLoading.value = false
}
async function load(page = 1) {
  await Promise.all([
    store.load({ ...filters, page, per_page: 15 }).catch(() => {}),
    loadSummary(),
  ])
}
onMounted(async () => {
  try {
    await store.loadOptions()
  } catch (e) {
    store.error = e instanceof Error ? e.message : 'Unable to load selectors.'
  }
  await load()
})
</script>
<template>
  <main class="fleet-module page-content">
    <div class="page-heading">
      <RouterLink to="/daily-bookings/create" class="btn btn-primary"
        ><i class="bi bi-plus-lg me-1" aria-hidden="true" /> Add Booking</RouterLink
      >
    </div>
    <div v-if="store.error" class="alert alert-danger" role="alert">{{ store.error }}</div>
    <div class="row g-3 mb-4 status-card-grid" aria-label="Daily booking status summary">
      <div v-for="(card, index) in summaryCards" :key="card.label" class="col-12 col-md-6 col-xl-3">
        <StatusCard :label="card.label" :value="summary[index]" :icon="card.icon"
          :loading="summaryLoading" description="Across search, vehicle, driver and date filters" />
      </div>
    </div>
    <section class="card shadow-sm">
      <div class="card-header bg-white">
        <form class="row g-2" @submit.prevent="load()">
          <div class="col-lg-3">
            <label class="form-label" for="booking-search">Search bookings</label
            ><input
              id="booking-search"
              v-model="filters.search"
              class="form-control"
              placeholder="Guest, vehicle, driver…"
            />
          </div>
          <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="booking-status-filter">Status</label
            ><select id="booking-status-filter" v-model="filters.status" class="form-select">
              <option value="">All statuses</option>
              <option v-for="s in ['Pending', 'Confirm', 'Completed', 'Cancelled']" :key="s">
                {{ s }}
              </option>
            </select>
          </div>
          <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="booking-car-filter">Vehicle</label
            ><select id="booking-car-filter" v-model="filters.car_id" class="form-select">
              <option value="">All vehicles</option>
              <option v-for="c in store.options?.cars || []" :key="c.id" :value="String(c.id)">
                {{ c.plate_no }}
              </option>
            </select>
          </div>
          <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="booking-driver-filter">Driver</label
            ><select id="booking-driver-filter" v-model="filters.driver_id" class="form-select">
              <option value="">All drivers</option>
              <option v-for="d in store.options?.drivers || []" :key="d.id" :value="String(d.id)">
                {{ d.full_name }}
              </option>
            </select>
          </div>
          <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="booking-from">Start date from</label
            ><input
              id="booking-from"
              v-model="filters.date_from"
              type="date"
              class="form-control"
              aria-label="Start date from"
            />
          </div>
          <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="booking-to">End date to</label
            ><input
              id="booking-to"
              v-model="filters.date_to"
              type="date"
              class="form-control"
              aria-label="End date to"
            />
          </div>
          <div class="col-sm-6 col-lg-1">
            <button class="btn btn-outline-primary w-100 fleet-filter-submit">Search</button>
          </div>
        </form>
      </div>
      <div v-if="store.loading" class="p-4 text-center">Loading bookings…</div>
      <div v-else-if="store.rows.length" class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Guest / company</th>
              <th>Vehicle</th>
              <th>Driver</th>
              <th>Operator</th>
              <th>Dates</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in store.rows" :key="b.id">
              <td>
                {{ b.guest_company_name }}
                <div class="small text-muted">{{ b.even_odd || '—' }}</div>
              </td>
              <td>
                {{ b.car?.plate_no || b.custom_car_name || '—' }}
                <div v-if="b.secondary_car" class="small text-muted">
                  + {{ b.secondary_car.plate_no }}
                </div>
              </td>
              <td>{{ b.driver?.full_name || b.custom_driver_name || '—' }}</td>
              <td>{{ b.operator_name }}</td>
              <td>
                {{ b.start_date }}
                <div class="small text-muted">to {{ b.end_date }}</div>
              </td>
              <td><BaseStatusBadge :status="b.status" /></td>
              <td>
                <div class="d-flex gap-1">
                  <RouterLink
                    :to="`/daily-bookings/${b.id}`"
                    class="btn btn-sm btn-outline-secondary"
                    >View</RouterLink
                  ><RouterLink
                    :to="`/daily-bookings/${b.id}/edit`"
                    class="btn btn-sm btn-outline-primary"
                    >Edit</RouterLink
                  >
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-4 text-center text-muted">No bookings match the selected filters.</div>
      <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="small text-muted">{{ store.total }} bookings</span>
        <div class="btn-group">
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="store.page <= 1 || store.loading"
            @click="load(store.page - 1)"
          >
            Previous</button
          ><span class="btn btn-outline-secondary btn-sm disabled"
            >{{ store.page }} / {{ store.lastPage }}</span
          ><button
            class="btn btn-outline-secondary btn-sm"
            :disabled="store.page >= store.lastPage || store.loading"
            @click="load(store.page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </section>
  </main>
</template>
