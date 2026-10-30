<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { customerApi, customerError, customerFieldErrors } from '../api'
import { offDays, type Customer, type CustomerInput } from '../types'
const props = defineProps<{ customer?: Customer }>()
const router = useRouter()
const busy = ref(false)
const error = ref('')
const errors = ref<Record<string, string[]>>({})
const form = reactive<CustomerInput>({ company_name: '', guest_name: '', contact_person: '', contact_phone: '', contact_email: '', customer_address: '', off_day: '', service_hour: '', remark: '' })
const fields: { key: keyof CustomerInput; label: string; max: number; required?: boolean; type?: string; placeholder?: string }[] = [
  { key: 'company_name', label: 'Company Name', max: 255, required: true },
  { key: 'guest_name', label: 'Guest Name', max: 255 },
  { key: 'contact_person', label: 'Contact Person', max: 255 },
  { key: 'contact_phone', label: 'Contact Phone', max: 50 },
  { key: 'contact_email', label: 'Contact Email', max: 150, type: 'email' },
  { key: 'service_hour', label: 'Service Hour', max: 100, required: true, placeholder: '09:00 AM - 06:00 PM or 10 hours' },
]
watch(() => props.customer, customer => {
  for (const key of Object.keys(form) as (keyof CustomerInput)[]) form[key] = customer?.[key] || ''
}, { immediate: true })
async function submit() {
  if (busy.value) return
  errors.value = {}; error.value = ''
  for (const field of fields) {
    if (field.required && !form[field.key].trim()) errors.value[field.key] = [`${field.label} is required.`]
  }
  if (Object.keys(errors.value).length) return
  busy.value = true
  try {
    await customerApi.save(Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v.trim()])) as unknown as CustomerInput, props.customer?.id)
    await router.push({ path: '/customers', query: { notice: props.customer ? 'updated' : 'created' } })
  } catch (cause) { error.value = customerError(cause); errors.value = customerFieldErrors(cause) }
  finally { busy.value = false }
}
</script>
<template>
  <section class="card-shell p-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h4 mb-0">{{ customer ? 'Edit Customer' : 'Add Customer' }}</h2><RouterLink to="/customers" class="btn-outline-warm">All Customers</RouterLink></div>
    <p class="text-muted">Contract assignments, vehicles, drivers, and billing stay in <RouterLink to="/monthly-contracts">Monthly Contracts</RouterLink>.</p>
    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
    <form class="row g-3" @submit.prevent="submit">
      <fieldset :disabled="busy" class="row g-3 m-0 p-0 border-0">
        <div v-for="field in fields" :key="field.key" class="col-md-6">
          <label :for="`customer-${field.key}`" class="form-label">{{ field.label }} <span v-if="field.required" aria-hidden="true">*</span></label>
          <input :id="`customer-${field.key}`" v-model="form[field.key]" :type="field.type || 'text'" :maxlength="field.max" :required="field.required" :placeholder="field.placeholder" class="form-control" :class="{ 'is-invalid': errors[field.key] }" :aria-invalid="!!errors[field.key]" :aria-describedby="errors[field.key] ? `error-${field.key}` : undefined">
          <div v-if="errors[field.key]" :id="`error-${field.key}`" class="invalid-feedback">{{ errors[field.key]?.join(' ') }}</div>
        </div>
        <div class="col-md-6"><label for="customer-offday" class="form-label">Off Day</label><select id="customer-offday" v-model="form.off_day" class="form-select" :class="{ 'is-invalid': errors.off_day }"><option value="">Select off day</option><option v-if="form.off_day && !offDays.includes(form.off_day)" :value="form.off_day">{{ form.off_day }}</option><option v-for="day in offDays" :key="day">{{ day }}</option></select><div v-if="errors.off_day" class="invalid-feedback">{{ errors.off_day.join(' ') }}</div></div>
        <div class="col-12"><label for="customer-address" class="form-label">Customer Address</label><textarea id="customer-address" v-model="form.customer_address" rows="3" class="form-control" :class="{ 'is-invalid': errors.customer_address }"/><div v-if="errors.customer_address" class="invalid-feedback">{{ errors.customer_address.join(' ') }}</div></div>
        <div class="col-12"><label for="customer-remark" class="form-label">Remark</label><textarea id="customer-remark" v-model="form.remark" rows="4" class="form-control" :class="{ 'is-invalid': errors.remark }"/><div v-if="errors.remark" class="invalid-feedback">{{ errors.remark.join(' ') }}</div></div>
        <div class="col-12"><p class="small text-muted">{{ customer ? `Current status: ${customer.customer_status}. Saving this form preserves it.` : 'New customers start Inactive. Saving an Active contract activates the linked customer.' }}</p><button class="btn-gold" type="submit">{{ busy ? 'Saving…' : customer ? 'Update Customer' : 'Save Customer' }}</button><RouterLink to="/customers" class="btn-outline-warm ms-2" :class="{ disabled: busy }" :tabindex="busy ? -1 : undefined" @click="busy && $event.preventDefault()">Cancel</RouterLink></div>
      </fieldset>
    </form>
  </section>
</template>
