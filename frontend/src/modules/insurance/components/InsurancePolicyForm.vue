<script setup lang="ts">
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { insuranceApi } from '../api'
import type { InsurancePolicy, InsurancePolicyInput, InsuranceProvider, InsuranceVehicle } from '../types'
import { toast } from '@/services/toast'

const props = defineProps<{ policyId?: number; renewalFromId?: number }>()
const router = useRouter()
const loading = ref(true),
  saving = ref(false),
  error = ref('')
const providers = ref<InsuranceProvider[]>([])
const vehicles = ref<InsuranceVehicle[]>([])
const showVehicleOptions = ref(false)
const vehicleOptionList = ref<HTMLElement | null>(null)
const fields = reactive<InsurancePolicyInput>({
  vehicle_plate_no: '',
  provider_id: 0,
  policy_number: '',
  insurance_type: '',
  coverage_details: '',
  start_date: '',
  expiration_date: '',
  premium_amount: '',
  currency: 'MMK',
  coverage_amount: '',
  windscreen_amount: '',
  no_claim_bonus: '',
  betterment_cover: false,
  notes: '',
})
const errors = ref<Record<string, string[]>>({})
const matchingVehicles = computed(() => {
  const query = fields.vehicle_plate_no.trim().toLocaleLowerCase()
  if (!query) return []
  return vehicles.value
    .filter((vehicle) => vehicle.plate_no.toLocaleLowerCase().includes(query))
    .sort((a, b) => {
      const aPrefix = a.plate_no.toLocaleLowerCase().startsWith(query) ? 0 : 1
      const bPrefix = b.plate_no.toLocaleLowerCase().startsWith(query) ? 0 : 1
      return aPrefix - bPrefix || a.plate_no.localeCompare(b.plate_no)
    })
})
const vehicleSuggestions = computed(() => matchingVehicles.value.slice(0, 8))
function selectVehicle(vehicle: InsuranceVehicle) {
  fields.vehicle_plate_no = vehicle.plate_no
  delete errors.value.vehicle_plate_no
  showVehicleOptions.value = false
}
async function focusFirstVehicleSuggestion() {
  if (!matchingVehicles.value.length) return
  showVehicleOptions.value = true
  await nextTick()
  vehicleOptionList.value?.querySelector<HTMLButtonElement>('button')?.focus()
}
function assign(policy: InsurancePolicy) {
  Object.assign(fields, {
    vehicle_plate_no: policy.vehicle?.plate_no || '',
    provider_id: policy.provider_id,
    policy_number: policy.policy_number,
    insurance_type: policy.insurance_type,
    coverage_details: policy.coverage_details || '',
    start_date: policy.start_date,
    expiration_date: policy.expiration_date,
    premium_amount: policy.premium_amount,
    currency: policy.currency,
    coverage_amount: policy.coverage_amount,
    windscreen_amount: policy.windscreen_amount,
    no_claim_bonus: policy.no_claim_bonus,
    betterment_cover: policy.betterment_cover,
    notes: policy.notes || '',
  })
}
type MoneyField = 'premium_amount' | 'coverage_amount' | 'windscreen_amount' | 'no_claim_bonus'
function setMoney(field: MoneyField, event: Event, allowNegative = false) {
  const input = event.target as HTMLInputElement
  const raw = input.value.replace(/,/g, '').replace(allowNegative ? /[^0-9.-]/g : /[^0-9.]/g, '')
  const negative = allowNegative && raw.startsWith('-')
  const digits = raw.replace(/-/g, '')
  const [whole = '', ...fraction] = digits.split('.')
  const formattedWhole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  fields[field] = `${negative ? '-' : ''}${formattedWhole}${digits.includes('.') ? `.${fraction.join('').slice(0, 2)}` : ''}`
}
function moneyValue(value: string) {
  const [whole = '', fraction] = value.split('.')
  return `${whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}${fraction === undefined ? '' : `.${fraction}`}`
}
function nextDate(value: string) {
  const [y = 0, m = 1, d = 0] = value.split('-').map(Number)
  const date = new Date(Date.UTC(y, m - 1, d + 1))
  return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`
}
onMounted(async () => {
  loading.value = true
  try {
    ;[providers.value, vehicles.value] = await Promise.all([
      insuranceApi.providers(),
      insuranceApi.vehicles(),
    ])
    if (props.policyId || props.renewalFromId) {
      const original = await insuranceApi.policy(props.policyId || props.renewalFromId!)
      assign(original)
      if (props.renewalFromId) {
        fields.policy_number = ''
        fields.start_date = nextDate(original.expiration_date)
        fields.expiration_date = ''
      }
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Unable to load policy form.'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
})
async function submit() {
  if (saving.value) return
  saving.value = true
  errors.value = {}
  error.value = ''
  try {
    const payload = {
      ...fields,
      premium_amount: fields.premium_amount.replace(/,/g, ''),
      coverage_amount: fields.coverage_amount.replace(/,/g, ''),
      windscreen_amount: fields.windscreen_amount.replace(/,/g, ''),
      no_claim_bonus: fields.no_claim_bonus.replace(/,/g, ''),
    }
    const saved = props.renewalFromId
      ? await insuranceApi.renewPolicy(props.renewalFromId, payload)
      : await insuranceApi.savePolicy(payload, props.policyId)
    toast.success(props.renewalFromId ? 'Policy renewed successfully.' : props.policyId ? 'Policy updated successfully.' : 'Policy created successfully.')
    await router.push(`/insurance/${saved.id}`)
  } catch (e: any) {
    errors.value = e?.response?.data?.errors || {}
    error.value = e?.response?.data?.message || e?.message || 'Unable to save policy.'
    toast.error(error.value)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <PageSkeleton v-if="loading" kind="form" label="Loading policy information…" />
  <div v-else>
    <form class="card shadow-sm" @submit.prevent="submit">
      <div class="card-body">
        <div v-if="renewalFromId" class="alert alert-info">
          Renewal terms remain linked to the same fleet vehicle and insurance type. The prior policy
          stays in the renewal history.
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="ins-vehicle">Car No <span class="text-danger">*</span></label>
            <div class="insurance-vehicle-picker position-relative" :class="{ 'is-open': showVehicleOptions && fields.vehicle_plate_no.trim() }">
              <input
                id="ins-vehicle"
                v-model="fields.vehicle_plate_no"
                class="form-control"
                required
                maxlength="100"
                placeholder="Type a plate number, e.g. 3K/4384"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                :aria-expanded="showVehicleOptions && Boolean(fields.vehicle_plate_no.trim())"
                :aria-controls="matchingVehicles.length ? 'ins-vehicle-options' : undefined"
                @focus="showVehicleOptions = true"
                @input="showVehicleOptions = true"
                @keydown.esc="showVehicleOptions = false"
                @keydown.down.prevent="focusFirstVehicleSuggestion"
              />
              <div
                v-if="showVehicleOptions && fields.vehicle_plate_no.trim()"
                class="insurance-vehicle-options position-absolute start-0 end-0"
              >
                <div v-if="matchingVehicles.length" class="insurance-vehicle-options-heading" aria-hidden="true">
                  {{ matchingVehicles.length > 8 ? `Top 8 of ${matchingVehicles.length} matches` : `${matchingVehicles.length} ${matchingVehicles.length === 1 ? 'match' : 'matches'}` }}
                </div>
                <div v-if="matchingVehicles.length" id="ins-vehicle-options" ref="vehicleOptionList" class="insurance-vehicle-option-list" role="listbox" aria-label="Matching fleet vehicles">
                  <button
                    v-for="vehicle in vehicleSuggestions"
                    :key="`${vehicle.fleet}-${vehicle.id}`"
                    type="button"
                    class="insurance-vehicle-option-button"
                    role="option"
                    :aria-selected="fields.vehicle_plate_no === vehicle.plate_no"
                    @mousedown.prevent="selectVehicle(vehicle)"
                    @click="selectVehicle(vehicle)"
                  >
                    <span class="fw-semibold">{{ vehicle.plate_no }}</span>
                    <span class="text-muted small ms-2">{{ vehicle.model_name }} · {{ vehicle.fleet }} fleet</span>
                  </button>
                </div>
                <div v-else class="insurance-vehicle-empty" role="status">
                  No registered fleet plates match “{{ fields.vehicle_plate_no }}”. Keep typing or check the plate number.
                </div>
              </div>
            </div>
            <div v-if="errors.vehicle_plate_no" class="text-danger small">{{ errors.vehicle_plate_no[0] }}</div>
            <div v-else-if="!showVehicleOptions" class="form-text">Type part of the plate number to search registered fleet vehicles.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ins-provider"
              >Provider <span class="text-danger">*</span></label
            ><select
              id="ins-provider"
              v-model.number="fields.provider_id"
              class="form-select"
              required
            >
              <option :value="0">Choose provider</option>
              <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }} | {{ p.contact_name }} </option>
            </select>
            <div v-if="errors.provider_id" class="text-danger small">
              {{ errors.provider_id[0] }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ins-number">Policy number</label
            ><input
              id="ins-number"
              v-model="fields.policy_number"
              class="form-control"
              required
              maxlength="100"
            />
            <div v-if="errors.policy_number" class="text-danger small">
              {{ errors.policy_number[0] }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ins-type">Insurance type</label
            ><input
              id="ins-type"
              v-model="fields.insurance_type"
              class="form-control"
              required
              maxlength="80"
            />
            <div v-if="errors.insurance_type" class="text-danger small">
              {{ errors.insurance_type[0] }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ins-start">Start date</label
            ><input
              id="ins-start"
              v-model="fields.start_date"
              class="form-control"
              type="date"
              required
            />
            <div v-if="errors.start_date" class="text-danger small">{{ errors.start_date[0] }}</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="ins-expiry">Expiry Date</label
            ><input
              id="ins-expiry"
              v-model="fields.expiration_date"
              class="form-control"
              type="date"
              required
            />
            <div v-if="errors.expiration_date" class="text-danger small">
              {{ errors.expiration_date[0] }}
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="ins-premium">Total Premium</label
            ><input
              id="ins-premium"
              :value="moneyValue(fields.premium_amount)"
              class="form-control"
              inputmode="decimal"
              required
              @input="setMoney('premium_amount', $event)"
            />
            <div v-if="errors.premium_amount" class="text-danger small">
              {{ errors.premium_amount[0] }}
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="ins-coverage">Sum Insured</label
            ><input
              id="ins-coverage"
              :value="moneyValue(fields.coverage_amount)"
              class="form-control"
              inputmode="decimal"
              required
              @input="setMoney('coverage_amount', $event)"
            />
            <div v-if="errors.coverage_amount" class="text-danger small">
              {{ errors.coverage_amount[0] }}
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="ins-windscreen">Windscreen</label>
            <input id="ins-windscreen" :value="moneyValue(fields.windscreen_amount)" class="form-control" inputmode="decimal" required @input="setMoney('windscreen_amount', $event)" />
            <div v-if="errors.windscreen_amount" class="text-danger small">{{ errors.windscreen_amount[0] }}</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="ins-ncb">No Claim Bonus</label>
            <input id="ins-ncb" :value="moneyValue(fields.no_claim_bonus)" class="form-control" inputmode="decimal" required @input="setMoney('no_claim_bonus', $event, true)" />
            <div v-if="errors.no_claim_bonus" class="text-danger small">{{ errors.no_claim_bonus[0] }}</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="ins-betterment">Betterment Cover</label>
            <select id="ins-betterment" v-model="fields.betterment_cover" class="form-select">
              <option :value="true">Yes</option>
              <option :value="false">No</option>
            </select>
            <div v-if="errors.betterment_cover" class="text-danger small">{{ errors.betterment_cover[0] }}</div>
          </div>
          <div class="col-12">
            <label class="form-label" for="ins-notes">Notes</label
            ><textarea id="ins-notes" v-model="fields.notes" class="form-control" rows="2" />
          </div>
        </div>
      </div>
      <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <RouterLink class="btn btn-outline-secondary" to="/insurance">Cancel</RouterLink
        ><button class="btn btn-primary" :disabled="saving">
          <BaseLoading v-if="saving" label="Saving…" /><template v-else>Save policy</template>
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.insurance-vehicle-picker.is-open {
  z-index: 30;
}

.insurance-vehicle-options {
  top: calc(100% + 0.35rem);
  z-index: 31;
  max-height: 17rem;
  overflow-y: auto;
  border: 1px solid var(--bs-border-color, #d9dee5);
  border-radius: 0.55rem;
  background: var(--bs-body-bg, #fff);
  box-shadow: 0 0.65rem 1.5rem rgb(20 32 48 / 18%);
  isolation: isolate;
}

.insurance-vehicle-options-heading {
  position: sticky;
  top: 0;
  padding: 0.45rem 0.8rem;
  border-bottom: 1px solid var(--bs-border-color, #d9dee5);
  background: var(--bs-body-bg, #fff);
  color: var(--bs-secondary-color, #667085);
  font-size: 0.78rem;
  font-weight: 600;
}

.insurance-vehicle-option-button + .insurance-vehicle-option-button {
  border-top: 1px solid var(--bs-border-color, #edf0f3);
}

.insurance-vehicle-option-button {
  display: flex;
  width: 100%;
  min-height: 2.8rem;
  align-items: center;
  justify-content: space-between;
  gap: 0.25rem 0.75rem;
  padding: 0.55rem 0.8rem;
  border: 0;
  background: transparent;
  color: inherit;
  text-align: left;
  cursor: pointer;
}

.insurance-vehicle-option-button:hover,
.insurance-vehicle-option-button:focus-visible {
  background: var(--bs-tertiary-bg, #f1f4f7);
}

.insurance-vehicle-option-button:focus-visible {
  outline: 2px solid var(--bs-primary, #0d6efd);
  outline-offset: -2px;
}

.insurance-vehicle-empty {
  padding: 0.8rem;
  color: var(--bs-secondary-color, #667085);
  font-size: 0.9rem;
}
</style>
