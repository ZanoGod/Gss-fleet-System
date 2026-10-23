<script setup lang="ts">
import { RouterLink } from 'vue-router'
const metrics = [
  {label:'Total Bookings',hint:'All trip records in the system'},
  {label:'Available Cars',hint:'Ready for assignment'},
  {label:'Available Drivers',hint:'Ready for assignment'},
  {label:'Ongoing',hint:'Confirmed bookings not yet ended'},
  {label:'Monthly Contracts',hint:'Expiring soon'},
]
</script>
<template>
  <section class="overview-grid" aria-label="Fleet overview">
    <div v-for="metric in metrics" :key="metric.label" class="card-shell metric"><div class="metric-label">{{ metric.label }}</div><div class="metric-value" aria-label="Data not connected">—</div><small>{{ metric.hint }}</small></div>
  </section>
  <section class="card-shell section-card">
    <div class="section-head"><div><h2>Recent Bookings</h2><p>Latest assignments across vehicles and drivers.</p></div><RouterLink to="/daily-bookings" class="btn-outline-warm">View All <i class="bi bi-arrow-right ms-2"></i></RouterLink></div>
    <div class="table-responsive"><table class="table data-table"><thead><tr><th>Guest / Company</th><th>Car</th><th>Driver</th><th>Dates</th><th>Status</th></tr></thead><tbody><tr><td colspan="5"><div class="empty-state"><i class="bi bi-calendar2-week" aria-hidden="true"></i><div class="fw-bold mt-2">Bookings data not connected yet</div><p class="mb-0">The dashboard is ready for the Laravel bookings API.</p></div></td></tr></tbody></table></div>
  </section>
  <section class="card-shell section-card"><div class="section-head"><div><h2>Quick Actions</h2><p>Jump into your daily operations.</p></div></div><div class="d-flex flex-wrap gap-2"><RouterLink to="/daily-bookings" class="btn-gold"><i class="bi bi-plus-circle me-2"></i>Daily Bookings</RouterLink><RouterLink to="/monthly-contracts" class="btn-outline-warm"><i class="bi bi-file-earmark-text me-2"></i>Monthly Contracts</RouterLink><RouterLink to="/fleet" class="btn-outline-warm"><i class="bi bi-car-front me-2"></i>Fleet</RouterLink></div></section>
</template>
