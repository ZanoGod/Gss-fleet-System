<script setup lang="ts">
import { usePendingAction } from '@/components/common/usePendingAction'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { useDailyBookingStore } from '../stores/dailyBookings'
import PageHeader from '@/components/common/PageHeader.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import { dailyBookingApi, type DailyBookingSummary } from '../api'
import { dailyBookingMetrics } from '../overview'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import RecordAction from '@/components/common/RecordAction.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import { toast } from '@/services/toast'
const store = useDailyBookingStore(),
  filters = reactive({
    search: '',
    status: '',
    date_from: '',
    date_to: '',
  })
const initial = ref(true)
const summary = ref<DailyBookingSummary | null>(null)
const summaryLoading = ref(true)
const results = ref<HTMLElement | null>(null)
const fullscreen = ref(false)
const metrics = computed(() => dailyBookingMetrics(summary.value))
const { pending, run } = usePendingAction()
async function load(page = 1) {
  try {
    await store.load({ ...filters, page, per_page: 10 })
  } catch {
    toast.error(store.error || 'Unable to load bookings.')
    summaryLoading.value = false
    return
  }
  try {
    summary.value = await dailyBookingApi.summary()
  } catch {
    toast.error('Unable to load booking status summary.')
  } finally {
    summaryLoading.value = false
  }
}
async function reset() {
  filters.search = ''
  filters.status = ''
  filters.date_from = ''
  filters.date_to = ''
  await load(1)
}
function syncFullscreen() {
  fullscreen.value = document.fullscreenElement === results.value
}
async function toggleFullscreen() {
  const card = results.value
  if (!card?.requestFullscreen || !document.fullscreenEnabled) {
    toast.info('Fullscreen is unavailable in this browser.')
    return
  }
  try {
    if (document.fullscreenElement === card) await document.exitFullscreen()
    else await card.requestFullscreen()
  } catch {
    toast.error('Unable to change fullscreen mode.')
  }
}
onUnmounted(() => document.removeEventListener('fullscreenchange', syncFullscreen))
onMounted(async () => {
  document.addEventListener('fullscreenchange', syncFullscreen)
  try {
    await store.loadOptions()
  } catch (e) {
    store.error = e instanceof Error ? e.message : 'Unable to load selectors.'
    toast.error(store.error)
  }
  await load()
  initial.value = false
})
</script>
<template>
  <main class="fleet-module booking-list-page page-content glass-module">
    <PageHeader
      title="Daily Bookings"
      description="Manage daily booking records."
      icon="bi-calendar-check"
      class="d-flex flex-wrap justify-content-end"
    >
      <RouterLink to="/daily-bookings/create" class="btn btn-primary"
        ><i class="bi bi-plus-lg me-1" aria-hidden="true" /> Add Booking</RouterLink
      >
    </PageHeader>
    <StatusSummary
      :items="metrics"
      :loading="summaryLoading"
      label="Daily booking status summary"
    />
    <section class="card-shell booking-filter-card mb-4" aria-label="Booking filters">
      <div class="booking-section-title">
        <h2>Search Bookings</h2>
        <p>Filter by guest, vehicle, driver, operator, status, or date.</p>
      </div>
      <form
        class="fleet-filter-grid fleet-filter-grid--daily-bookings"
        @submit.prevent="run('search', () => load())"
      >
        <div class="fleet-filter-field fleet-filter-field--wide">
          <label class="form-label" for="booking-search">Search bookings</label
          ><input
            id="booking-search"
            v-model="filters.search"
            class="form-control"
            placeholder="Guest, vehicle, driver…"
          />
        </div>
        <div class="fleet-filter-field">
          <label class="form-label" for="booking-status-filter">Status</label
          ><select id="booking-status-filter" v-model="filters.status" class="form-select">
            <option value="">All statuses</option>
            <option v-for="s in ['Pending', 'Confirm', 'Completed', 'Cancelled']" :key="s">
              {{ s }}
            </option>
          </select>
        </div>
        <div class="fleet-filter-field">
          <label class="form-label" for="booking-from">Start date from</label
          ><input
            id="booking-from"
            v-model="filters.date_from"
            type="date"
            class="form-control"
            aria-label="Start date from"
          />
        </div>
        <div class="fleet-filter-field">
          <label class="form-label" for="booking-to">End date to</label
          ><input
            id="booking-to"
            v-model="filters.date_to"
            type="date"
            class="form-control"
            aria-label="End date to"
          />
        </div>
        <div class="fleet-filter-actions fleet-filter-actions--daily-bookings module-filter-actions">
          <button
            class="btn module-filter-apply fleet-filter-submit"
            :disabled="initial || pending('search') || pending('reset')"
          >
            <BaseLoading v-if="pending('search')" label="Searching…" /><template v-else
              ><i class="bi bi-funnel" aria-hidden="true"></i> Search</template
            >
          </button>
          <span class="module-filter-divider" aria-hidden="true"></span>
          <button
            type="button"
            class="btn module-filter-reset fleet-filter-submit"
            :disabled="initial || pending('search') || pending('reset')"
            @click="run('reset', reset)"
          >
            <BaseLoading v-if="pending('reset')" label="Resetting…" /><template v-else
              ><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</template
            >
          </button>
        </div>
      </form>
    </section>
    <section ref="results" class="card-shell booking-results-card" aria-label="Booking results">
      <div class="booking-section-title booking-results-title">
        <div>
          <h2>Booking List</h2>
          <p>{{ store.total }} matching bookings</p>
        </div>
        <button type="button" class="btn btn-outline-primary" @click="toggleFullscreen">
          {{ fullscreen ? 'Exit Fullscreen' : 'Fullscreen Table' }}
        </button>
      </div>
      <BaseLoading
        v-if="!initial && store.loading"
        class="px-3 py-2 d-block"
        label="Updating bookings…"
      />
      <PageSkeleton v-if="initial" label="Loading bookings" :columns="7" />
      <div v-else-if="store.rows.length" class="table-responsive booking-table-wrap">
        <table class="table table-hover align-middle mb-0 booking-table">
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
                <div class="record-actions">
                  <RecordAction action="view" icon-only :aria-label="`View booking ${b.id}`" :to="`/daily-bookings/${b.id}`" />
                  <RecordAction action="edit" icon-only :aria-label="`Edit booking ${b.id}`" :to="`/daily-bookings/${b.id}/edit`" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-4 text-center text-muted">No bookings match the selected filters.</div>
      <div class="booking-results-footer">
        <BasePagination
          :page="store.page"
          :per-page="10"
          :total="store.total"
          :pending-page="(target) => pending(`page:${target}`)"
          @update:page="run(`page:${$event}`, () => load($event))"
        />
      </div>
    </section>
  </main>
</template>
