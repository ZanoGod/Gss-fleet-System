<script setup lang="ts">
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import StatusSummary from '@/components/common/StatusSummary.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import api from '@/services/api'
import { getApiErrorMessage } from '@/services/apiErrors'
import { toast } from '@/services/toast'
import { dashboardMetrics, type DashboardSummary } from '@/modules/dashboard/overview'
const summary = ref<DashboardSummary | null>(null)
const loading = ref(true)
const initial = ref(true)
const error = ref('')
async function load() {
  loading.value = true
  try {
    summary.value = (await api.get<{ data: DashboardSummary }>('/api/dashboard')).data.data
    error.value = ''
  } catch (cause) {
    error.value = getApiErrorMessage(cause)
    toast.error(error.value)
  } finally {
    loading.value = false
    initial.value = false
  }
}
onMounted(load)
</script>
<template>
  <button v-if="error" class="btn-outline-warm mb-3" :disabled="loading" @click="load">
    <BaseLoading v-if="loading" label="Retrying…" /><template v-else>Retry dashboard</template>
  </button>
  <StatusSummary :items="dashboardMetrics(summary)" :loading="initial" label="Fleet overview" />
  <section class="card-shell section-card">
    <div class="section-head">
      <div>
        <h2>Recent Bookings</h2>
        <p>Latest assignments across vehicles and drivers.</p>
      </div>
      <RouterLink to="/daily-bookings" class="btn-outline-warm"
        >View All <i class="bi bi-arrow-right ms-2"></i
      ></RouterLink>
    </div>
    <PageSkeleton v-if="initial" label="Loading recent bookings" />
    <div v-else class="table-responsive">
      <table class="table data-table">
        <thead>
          <tr>
            <th>Guest / Company</th>
            <th>Car</th>
            <th>Driver</th>
            <th>Dates</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="booking in summary?.recent_bookings || []" :key="booking.id">
            <td>
              <RouterLink :to="`/daily-bookings/${booking.id}`">{{
                booking.guest_company_name
              }}</RouterLink>
            </td>
            <td>
              {{ booking.car?.plate_no || booking.custom_car_name || '—'
              }}<span v-if="booking.secondary_car"> / {{ booking.secondary_car.plate_no }}</span>
            </td>
            <td>{{ booking.driver?.full_name || booking.custom_driver_name || '—' }}</td>
            <td>{{ booking.start_date }} – {{ booking.end_date }}</td>
            <td><BaseStatusBadge :status="booking.status" /></td>
          </tr>
          <tr v-if="!summary?.recent_bookings.length">
            <td colspan="5">
              <div class="empty-state">
                {{ error ? 'Recent bookings unavailable.' : 'No bookings recorded yet.' }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
  <section class="card-shell section-card">
    <div class="section-head">
      <div>
        <h2>Quick Actions</h2>
        <p>Jump into your daily operations.</p>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <RouterLink to="/daily-bookings" class="btn-gold"
        ><i class="bi bi-plus-circle me-2"></i>Daily Bookings</RouterLink
      ><RouterLink to="/monthly-contracts" class="btn-outline-warm"
        ><i class="bi bi-file-earmark-text me-2"></i>Monthly Contracts</RouterLink
      ><RouterLink to="/fleet" class="btn-outline-warm"
        ><i class="bi bi-car-front me-2"></i>Fleet</RouterLink
      >
    </div>
  </section>
</template>
