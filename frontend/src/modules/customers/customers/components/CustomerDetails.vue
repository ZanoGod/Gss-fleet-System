<script setup lang="ts">
import { onUnmounted, ref, watch } from 'vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import { customerApi, customerError } from '../api'
import { currentMonth, shiftMonth, type Customer } from '../types'
const props = defineProps<{ id: number; month?: string }>()
const emit = defineEmits<{ deleted: []; monthChange: [month: string] }>()
const customer = ref<Customer | null>(null), loading = ref(false), error = ref(''), deleting = ref(false), confirming = ref(false)
const month = ref(props.month || currentMonth())
let request = 0
onUnmounted(() => { request++ })
watch(() => props.month, value => { if (value) month.value = value })
watch([() => props.id, month], load, { immediate: true })
watch(month, value => emit('monthChange', value))
async function load() {
  const version = ++request
  loading.value = true; error.value = ''; customer.value = null; confirming.value = false
  try { const result = await customerApi.get(props.id, month.value); if (version === request) customer.value = result }
  catch (cause) { if (version === request) error.value = customerError(cause) }
  finally { if (version === request) loading.value = false }
}
async function remove() {
  if (!customer.value || deleting.value) return
  deleting.value = true; error.value = ''
  try { await customerApi.remove(customer.value.id); emit('deleted') }
  catch (cause) { error.value = customerError(cause); confirming.value = false }
  finally { deleting.value = false }
}
const labels: { key: 'guest_name' | 'contact_person' | 'contact_phone' | 'contact_email' | 'customer_address' | 'off_day' | 'service_hour' | 'remark'; text: string }[] = [
  { key: 'guest_name', text: 'Guest Name' }, { key: 'contact_person', text: 'Contact Person' },
  { key: 'contact_phone', text: 'Phone' }, { key: 'contact_email', text: 'Email' },
  { key: 'customer_address', text: 'Address' }, { key: 'off_day', text: 'Off Day' },
  { key: 'service_hour', text: 'Service Hour' }, { key: 'remark', text: 'Remark' },
]
</script>
<template>
  <div class="customer-details" :aria-busy="loading">
    <div v-if="error" class="alert alert-danger" role="alert">{{ error }} <button v-if="!customer" class="btn btn-sm btn-outline-secondary ms-2" @click="load">Retry</button></div>
    <BaseLoading v-if="loading" label="Loading customer details…"/>
    <template v-if="customer">
      <div class="d-flex justify-content-between align-items-start gap-3"><div><h2 class="h4">{{ customer.company_name || 'Customer' }}</h2><p class="text-muted">{{ customer.contract_count }} contracts · {{ customer.active_contract_count }} active</p></div><BaseStatusBadge :status="customer.customer_status"/></div>
      <dl class="row"><template v-for="label in labels" :key="label.key"><dt class="col-sm-4">{{ label.text }}</dt><dd class="col-sm-8">{{ customer[label.key] || '—' }}</dd></template><dt class="col-sm-4">Latest expiry</dt><dd class="col-sm-8">{{ customer.latest_expiry || '—' }}</dd></dl>
      <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap my-3"><h3 class="h5 mb-0">Monthly Contracts</h3><div class="d-flex align-items-center gap-2"><button class="btn-outline-warm" aria-label="Previous contract month" @click="month = shiftMonth(month, -1)">‹</button><span>{{ month }}</span><button class="btn-outline-warm" aria-label="Next contract month" @click="month = shiftMonth(month, 1)">›</button><button class="btn-outline-warm" :disabled="month === currentMonth()" @click="month = currentMonth()">Today</button></div></div>
      <p class="small text-muted">Contract cards show the selected month. Counts and latest expiry include all contracts.</p>
      <p v-if="!customer.contracts?.length" class="notice">No contracts for the selected month.</p>
      <article v-for="contract in customer.contracts" :key="contract.id" class="card-shell p-3 mb-3">
        <div class="d-flex justify-content-between gap-2"><RouterLink :to="`/monthly-contracts/${contract.id}`" class="fw-bold">{{ contract.contract_no }}</RouterLink><BaseStatusBadge :status="contract.status"/></div>
        <dl class="row mb-0 small"><dt class="col-4">Guest / contact</dt><dd class="col-8">{{ contract.guest_name || '—' }} / {{ contract.contact_person || '—' }}</dd><dt class="col-4">Assignment</dt><dd class="col-8">{{ contract.assignment_type }}</dd><dt class="col-4">Vehicle</dt><dd class="col-8">{{ contract.car_type || '—' }} · {{ contract.plate_no || '—' }}</dd><dt class="col-4">Driver</dt><dd class="col-8">{{ contract.driver_name || '—' }}</dd><dt class="col-4">Operator</dt><dd class="col-8">{{ contract.operator_name || '—' }}</dd><dt class="col-4">Rental</dt><dd class="col-8">{{ contract.rental_status }}</dd><dt class="col-4">Period</dt><dd class="col-8">{{ contract.start_date }} → {{ contract.end_date }}</dd></dl>
      </article>
      <div v-if="confirming" class="alert alert-warning mt-3" role="alert"><p>Delete {{ customer.company_name }}? This permanently removes this customer.</p><button class="btn btn-danger" :disabled="deleting" @click="remove">{{ deleting ? 'Deleting…' : 'Confirm delete' }}</button><button class="btn-outline-warm ms-2" :disabled="deleting" @click="confirming = false">Cancel</button></div>
      <div v-else class="d-flex gap-2 flex-wrap mt-4"><RouterLink :to="`/customers/${customer.id}/edit`" class="btn-gold">Edit Customer</RouterLink><button class="btn btn-outline-danger" :disabled="!customer.can_delete" @click="confirming = true">Delete Customer</button></div>
      <p v-if="!customer.can_delete" class="small text-muted mt-2">Customers linked to any monthly contract cannot be deleted.</p>
    </template>
  </div>
</template>
<style scoped>dd{white-space:pre-wrap;overflow-wrap:anywhere}button,a{flex-shrink:0}</style>
