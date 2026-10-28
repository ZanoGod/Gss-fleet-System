<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { insuranceApi } from '../api'
import type { InsurancePolicy, InsurancePolicyInput, InsuranceProvider, InsuranceVehicle } from '../types'

const props = defineProps<{ policyId?: number; renewalFromId?: number }>()
const router = useRouter()
const loading = ref(false), saving = ref(false), message = ref(''), error = ref('')
const vehicles = ref<InsuranceVehicle[]>([]), providers = ref<InsuranceProvider[]>([])
const fields = reactive<InsurancePolicyInput>({ car_id: null, monthly_car_id: null, provider_id: 0, policy_number: '', insurance_type: '', coverage_details: '', start_date: '', expiration_date: '', premium_amount: '', currency: 'MMK', coverage_amount: '', notes: '' })
const errors = ref<Record<string, string[]>>({})
const vehicleSelection = computed({ get: () => fields.car_id ? `daily:${fields.car_id}` : fields.monthly_car_id ? `monthly:${fields.monthly_car_id}` : '', set: (value: string) => { fields.car_id = value.startsWith('daily:') ? Number(value.split(':')[1]) : null; fields.monthly_car_id = value.startsWith('monthly:') ? Number(value.split(':')[1]) : null } })
function assign(policy: InsurancePolicy) { Object.assign(fields, { car_id: policy.car_id, monthly_car_id: policy.monthly_car_id, provider_id: policy.provider_id, policy_number: policy.policy_number, insurance_type: policy.insurance_type, coverage_details: policy.coverage_details || '', start_date: policy.start_date, expiration_date: policy.expiration_date, premium_amount: policy.premium_amount, currency: policy.currency, coverage_amount: policy.coverage_amount, notes: policy.notes || '' }) }
function nextDate(value: string) { const [y=0,m=1,d=0] = value.split('-').map(Number); const date = new Date(Date.UTC(y,m-1,d+1)); return `${date.getUTCFullYear()}-${String(date.getUTCMonth()+1).padStart(2,'0')}-${String(date.getUTCDate()).padStart(2,'0')}` }
onMounted(async () => { loading.value = true; try { [vehicles.value, providers.value] = await Promise.all([insuranceApi.vehicles(), insuranceApi.providers()]); if (props.policyId || props.renewalFromId) { const original = await insuranceApi.policy(props.policyId || props.renewalFromId!); assign(original); if (props.renewalFromId) { fields.policy_number = ''; fields.start_date = nextDate(original.expiration_date); fields.expiration_date = '' } } } catch (e) { error.value = e instanceof Error ? e.message : 'Unable to load policy form.' } finally { loading.value = false } })
async function submit() { saving.value = true; errors.value = {}; error.value = ''; message.value = ''; try { const saved = props.renewalFromId ? await insuranceApi.renewPolicy(props.renewalFromId, fields) : await insuranceApi.savePolicy(fields, props.policyId); message.value = props.renewalFromId ? 'Policy renewed successfully.' : 'Policy saved successfully.'; await router.push(`/insurance/${saved.id}`) } catch (e: any) { errors.value = e?.response?.data?.errors || {}; error.value = e?.response?.data?.message || e?.message || 'Unable to save policy.' } finally { saving.value = false } }
</script>

<template>
  <div v-if="loading" class="alert alert-light border">Loading policy information…</div>
  <div v-else>
    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div><div v-if="message" class="alert alert-success">{{ message }}</div>
    <form class="card shadow-sm" @submit.prevent="submit"><div class="card-body">
      <div v-if="renewalFromId" class="alert alert-info">Renewal terms remain linked to the same fleet vehicle and insurance type. The prior policy stays in the renewal history.</div><div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="ins-vehicle">Fleet vehicle <span class="text-danger">*</span></label><select id="ins-vehicle" v-model="vehicleSelection" class="form-select" required><option value="">Choose vehicle</option><optgroup label="Daily fleet"><option v-for="v in vehicles.filter(v => v.fleet === 'daily')" :key="`d${v.id}`" :value="`daily:${v.id}`">{{ v.plate_no }} · {{ v.model_name }}</option></optgroup><optgroup label="Monthly fleet"><option v-for="v in vehicles.filter(v => v.fleet === 'monthly')" :key="`m${v.id}`" :value="`monthly:${v.id}`">{{ v.plate_no }} · {{ v.model_name }}</option></optgroup></select><div v-if="errors.car_id || errors.monthly_car_id" class="text-danger small">{{ (errors.car_id || errors.monthly_car_id)?.[0] }}</div></div>
        <div class="col-md-6"><label class="form-label" for="ins-provider">Provider <span class="text-danger">*</span></label><select id="ins-provider" v-model.number="fields.provider_id" class="form-select" required><option :value="0">Choose provider</option><option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option></select><div v-if="errors.provider_id" class="text-danger small">{{ errors.provider_id[0] }}</div></div>
        <div class="col-md-6"><label class="form-label" for="ins-number">Policy number</label><input id="ins-number" v-model="fields.policy_number" class="form-control" required maxlength="100"><div v-if="errors.policy_number" class="text-danger small">{{ errors.policy_number[0] }}</div></div>
        <div class="col-md-6"><label class="form-label" for="ins-type">Insurance type</label><input id="ins-type" v-model="fields.insurance_type" class="form-control" required maxlength="80"><div v-if="errors.insurance_type" class="text-danger small">{{ errors.insurance_type[0] }}</div></div>
        <div class="col-md-6"><label class="form-label" for="ins-start">Start date</label><input id="ins-start" v-model="fields.start_date" class="form-control" type="date" required><div v-if="errors.start_date" class="text-danger small">{{ errors.start_date[0] }}</div></div>
        <div class="col-md-6"><label class="form-label" for="ins-expiry">Expiration date</label><input id="ins-expiry" v-model="fields.expiration_date" class="form-control" type="date" required><div v-if="errors.expiration_date" class="text-danger small">{{ errors.expiration_date[0] }}</div></div>
        <div class="col-md-4"><label class="form-label" for="ins-currency">Currency</label><select id="ins-currency" v-model="fields.currency" class="form-select"><option>MMK</option><option>USD</option></select></div>
        <div class="col-md-4"><label class="form-label" for="ins-premium">Premium amount</label><input id="ins-premium" v-model="fields.premium_amount" class="form-control" inputmode="decimal" required><div v-if="errors.premium_amount" class="text-danger small">{{ errors.premium_amount[0] }}</div></div>
        <div class="col-md-4"><label class="form-label" for="ins-coverage">Coverage amount</label><input id="ins-coverage" v-model="fields.coverage_amount" class="form-control" inputmode="decimal" required><div v-if="errors.coverage_amount" class="text-danger small">{{ errors.coverage_amount[0] }}</div></div>
        <div class="col-12"><label class="form-label" for="ins-details">Coverage details</label><textarea id="ins-details" v-model="fields.coverage_details" class="form-control" rows="3" /></div><div class="col-12"><label class="form-label" for="ins-notes">Notes</label><textarea id="ins-notes" v-model="fields.notes" class="form-control" rows="2" /></div>
      </div>
    </div><div class="card-footer bg-white d-flex justify-content-end gap-2"><RouterLink class="btn btn-outline-secondary" to="/insurance">Cancel</RouterLink><button class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving…' : 'Save policy' }}</button></div></form>
  </div>
</template>
