<script setup lang="ts">
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { dailyBookingApi } from '../api'
import type { DailyBooking } from '../types'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import { toast } from '@/services/toast'
const route = useRoute(),
  router = useRouter(),
  booking = ref<DailyBooking | null>(null),
  loading = ref(true),
  error = ref(''),
  deleting = ref(false)
async function load() {
  loading.value = true
  try {
    booking.value = await dailyBookingApi.get(Number(route.params.id))
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to load booking.'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}
async function remove() {
  if (deleting.value) return
  if (!booking.value || !window.confirm('Delete this booking permanently?')) return
  deleting.value = true
  try {
    await dailyBookingApi.remove(booking.value.id)
    toast.success('Booking deleted successfully.')
    await router.push('/daily-bookings')
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to delete booking.'
    toast.error(error.value)
  } finally {
    deleting.value = false
  }
}
onMounted(load)
</script>
<template>
  <main class="fleet-module page-content">
    <div class="page-heading">
      <div v-if="booking" class="d-flex gap-2">
        <RouterLink :to="`/daily-bookings/${booking.id}/edit`" class="btn btn-outline-primary"
          >Edit booking</RouterLink
        ><button class="btn btn-outline-danger" :disabled="deleting" @click="remove">
          <BaseLoading v-if="deleting" label="Deleting…" /><template v-else
            >Delete</template
          ></button
        ><RouterLink to="/daily-bookings" class="btn btn-outline-secondary">Back</RouterLink>
      </div>
    </div>
    <PageSkeleton v-if="loading" kind="detail" label="Loading booking…" />
    <section v-else-if="booking" class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0">Booking #{{ booking.id }} · {{ booking.guest_company_name }}</h2>
        <BaseStatusBadge :status="booking.status" />
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">Primary vehicle</dt>
          <dd class="col-sm-9">
            {{ booking.car?.plate_no || booking.custom_car_name || '—' }}
            <span v-if="booking.car">· {{ booking.car.model_name }}</span>
          </dd>
          <dt class="col-sm-3">Second vehicle</dt>
          <dd class="col-sm-9">
            {{ booking.secondary_car?.plate_no || '—' }}
            <span v-if="booking.secondary_car">· {{ booking.secondary_car.model_name }}</span>
          </dd>
          <dt class="col-sm-3">Driver</dt>
          <dd class="col-sm-9">
            {{ booking.driver?.full_name || booking.custom_driver_name || '—' }}
          </dd>
          <dt class="col-sm-3">Operator</dt>
          <dd class="col-sm-9">
            {{ booking.operator_name }}
            <span v-if="booking.operator?.phone_number" class="text-muted"
              >· {{ booking.operator.phone_number }}</span
            >
          </dd>
          <dt class="col-sm-3">Even / odd</dt>
          <dd class="col-sm-9">{{ booking.even_odd || '—' }}</dd>
          <dt class="col-sm-3">Start date</dt>
          <dd class="col-sm-9">{{ booking.start_date }}</dd>
          <dt class="col-sm-3">End date</dt>
          <dd class="col-sm-9">{{ booking.end_date }}</dd>
          <dt class="col-sm-3">Remark</dt>
          <dd class="col-sm-9">{{ booking.remark || '—' }}</dd>
        </dl>
      </div>
    </section>
  </main>
</template>
