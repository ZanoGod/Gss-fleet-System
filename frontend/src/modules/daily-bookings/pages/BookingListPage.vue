<script setup lang="ts">
import { usePendingAction } from '@/components/common/usePendingAction'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import { computed, onMounted, reactive, ref } from 'vue'
import { useDailyBookingStore } from '../stores/dailyBookings'
import PageHeader from '@/components/common/PageHeader.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import { dailyBookingApi, type DailyBookingSummary } from '../api'
import { dailyBookingMetrics } from '../overview'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import { toast } from '@/services/toast'
const store = useDailyBookingStore(),
  filters = reactive({
    search: '',
    status: '',
    date_from: '',
    date_to: '',
    car_id: '',
  })
const initial = ref(true)
const summary = ref<DailyBookingSummary | null>(null)
const summaryLoading = ref(true)
const metrics = computed(() => dailyBookingMetrics(summary.value))
const { pending, run } = usePendingAction()
async function load(page = 1) {
  try {
    await store.load({ ...filters, page, per_page: 15 })
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
onMounted(async () => {
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
  <main class="fleet-module page-content">
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
    <StatusSummary :items="metrics" :loading="summaryLoading" label="Daily booking status summary" />
    <section class="card shadow-sm">
      <div class="card-header bg-white">
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
            <label class="form-label" for="booking-car-filter">Vehicle</label
            ><select id="booking-car-filter" v-model="filters.car_id" class="form-select">
              <option value="">All vehicles</option>
              <option v-for="c in store.options?.cars || []" :key="c.id" :value="String(c.id)">
                {{ c.plate_no }}
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
        label="Updating bookings…"
      />
      <PageSkeleton v-if="initial" label="Loading bookings" :columns="7" />
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
      <div class="card-footer bg-white">
        <BasePagination
          :page="store.page"
          :per-page="15"
          :total="store.total"
          :pending-page="(target) => pending(`page:${target}`)"
          @update:page="run(`page:${$event}`, () => load($event))"
        />
      </div>
    </section>
  </main>
</template>
