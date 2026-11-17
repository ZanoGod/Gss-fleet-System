<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { carLicenseApi } from '../api'
import type { CarLicense, LicenseVehicleOption } from '../types'
import { toast } from '@/services/toast'
import { suggestedExpiryDate } from '../terms'

const props = defineProps<{ mode: 'create' | 'edit' | 'renew' }>()
const route = useRoute(),
  router = useRouter()
const initial = ref(props.mode !== 'create'),
  saving = ref(false),
  loadError = ref('')
const vehicleOptions = ref<LicenseVehicleOption[]>([]),
  offices = ref<Record<string, string[]>>({}),
  files = ref<File[]>([]),
  expiryTouched = ref(false)
const errors = ref<Record<string, string[]>>({})
const now = new Date(),
  today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
const form = reactive({
  vehicle_key: '',
  license_number: '',
  license_period: 1,
  issue_date: today,
  expiry_date: '',
  renewal_date: '',
  renewal_fee: '0',
  office_region: '',
  office_branch: '',
  remarks: '',
})
const title = computed(() =>
  props.mode === 'create'
    ? 'Add Car License'
    : props.mode === 'renew'
      ? 'Renew Car License'
      : 'Edit Car License',
)
const sourceLicense = ref<CarLicense | null>(null)
watch(
  () => [form.issue_date, form.license_period],
  () => {
    if (!expiryTouched.value)
      form.expiry_date = suggestedExpiryDate(form.issue_date, form.license_period)
  },
  { immediate: true },
)
watch(
  () => form.office_region,
  (region) => {
    if (form.office_branch && !offices.value[region]?.includes(form.office_branch))
      form.office_branch = ''
  },
)
async function initialize() {
  try {
    const [options, record] = await Promise.all([
      carLicenseApi.options(),
      props.mode === 'create' ? Promise.resolve(null) : carLicenseApi.get(Number(route.params.id)),
    ])
    vehicleOptions.value = options.vehicles
    offices.value = options.offices
    if (record) {
      sourceLicense.value = record
      form.vehicle_key = `${record.vehicle?.fleet || (record.car_id ? 'daily' : 'monthly')}:${record.vehicle?.id || record.car_id || record.monthly_car_id}`
      form.license_number = record.license_number
      form.license_period = record.license_period
      form.issue_date = props.mode === 'renew' ? today : record.issue_date
      form.expiry_date =
        props.mode === 'renew'
          ? suggestedExpiryDate(today, record.license_period)
          : record.expiry_date
      form.renewal_date = props.mode === 'renew' ? today : record.renewal_date || ''
      form.renewal_fee = '0'
      form.office_region = record.office_region
      form.office_branch = record.office_branch
      form.remarks = record.remarks || ''
      expiryTouched.value = props.mode !== 'renew'
    } else {
      form.expiry_date = suggestedExpiryDate(form.issue_date, form.license_period)
      form.office_region = Object.keys(offices.value)[0] || ''
    }
  } catch (error) {
    loadError.value = error instanceof Error ? error.message : 'Unable to load license details.'
  } finally {
    initial.value = false
  }
}
function collectFiles(event: Event) {
  const input = event.target as HTMLInputElement
  files.value = input.files ? Array.from(input.files) : []
}
function requestErrors(error: unknown) {
  const response = typeof error === 'object' && error ? Reflect.get(error, 'response') : null
  const data = typeof response === 'object' && response ? Reflect.get(response, 'data') : null
  const validation = typeof data === 'object' && data ? Reflect.get(data, 'errors') : null
  if (validation && typeof validation === 'object')
    errors.value = validation as Record<string, string[]>
  const message = typeof data === 'object' && data ? Reflect.get(data, 'message') : null
  return typeof message === 'string'
    ? message
    : error instanceof Error
      ? error.message
      : 'Unable to save car license.'
}
async function save() {
  saving.value = true
  errors.value = {}
  const [fleet, id] = form.vehicle_key.split(':')
  const payload: Record<string, string | number | null> = {
    license_number: form.license_number.trim(),
    license_period: Number(form.license_period),
    issue_date: form.issue_date,
    expiry_date: form.expiry_date,
    renewal_date: props.mode === 'renew' ? form.renewal_date || today : form.renewal_date || null,
    renewal_fee: form.renewal_fee,
    office_region: form.office_region,
    office_branch: form.office_branch,
    remarks: form.remarks || null,
  }
  if (props.mode !== 'renew') {
    payload.car_id = fleet === 'daily' ? Number(id) : null
    payload.monthly_car_id = fleet === 'monthly' ? Number(id) : null
  }
  try {
    const record =
      props.mode === 'renew'
        ? await carLicenseApi.renew(Number(route.params.id), payload)
        : await carLicenseApi.save(
            payload,
            props.mode === 'edit' ? Number(route.params.id) : undefined,
          )
    const uploadErrors: string[] = []
    for (const file of files.value) {
      try {
        await carLicenseApi.upload(record.id, file)
      } catch {
        uploadErrors.push(file.name)
      }
    }
    toast.success(
      props.mode === 'renew' ? 'Car license renewal recorded.' : 'Car license saved successfully.',
    )
    if (uploadErrors.length)
      toast.error(
        `License saved, but these documents could not be uploaded: ${uploadErrors.join(', ')}`,
      )
    await router.push(`/car-licenses/${record.id}`)
  } catch (error) {
    toast.error(requestErrors(error))
  } finally {
    saving.value = false
  }
}
onMounted(() => void initialize())
</script>

<template>
  <section
    class="fleet-module page-content resource-workspace car-license-form"
    aria-label="Car license form"
  >
    <PageHeader
      :title="title"
      description="Record the details shown on the vehicle registration and renewal documents."
      icon="bi-card-checklist"
      ><RouterLink to="/car-licenses" class="btn btn-primary"
        >All Car Licenses</RouterLink
      ></PageHeader
    >
    <PageSkeleton v-if="initial" kind="form" label="Loading car license form" />
    <div v-else-if="loadError" class="alert alert-warning" role="alert">
      {{ loadError }} <RouterLink to="/car-licenses">Return to licenses</RouterLink>
    </div>
    <section v-else class="card-shell p-4">
      <form class="row g-3" @submit.prevent="save">
        <p class="text-muted mb-0">
          RTAD materials describe motor vehicle registration renewal before expiry and commonly
          offer booking within three months of expiry. Enter the period and expiry date printed or
          issued by RTAD; passenger-car periods vary by current office and vehicle record.
        </p>
        <div v-if="mode !== 'renew'" class="col-md-6">
          <label for="license-vehicle" class="form-label">Vehicle / Plate Number *</label
          ><select
            id="license-vehicle"
            v-model="form.vehicle_key"
            class="form-select"
            required
            :class="{ 'is-invalid': errors.car_id || errors.monthly_car_id || errors.vehicle }"
          >
            <option value="">Select vehicle</option>
            <option v-for="vehicle in vehicleOptions" :key="vehicle.value" :value="vehicle.value">
              {{ vehicle.plate_no }} · {{ vehicle.model_name }} ({{ vehicle.fleet }} fleet)
            </option>
          </select>
          <div class="invalid-feedback">
            {{ errors.vehicle?.[0] || errors.car_id?.[0] || errors.monthly_car_id?.[0] }}
          </div>
        </div>
        <div v-else class="col-md-6">
          <label class="form-label">Vehicle / Plate Number</label>
          <p class="form-control-plaintext fw-semibold">
            {{ sourceLicense?.vehicle?.plate_no }} · {{ sourceLicense?.vehicle?.model_name }}
          </p>
        </div>
        <div class="col-md-6">
          <label for="license-number" class="form-label">License Number *</label
          ><input
            id="license-number"
            v-model="form.license_number"
            class="form-control"
            maxlength="100"
            required
            :class="{ 'is-invalid': errors.license_number }"
          />
          <div class="invalid-feedback">{{ errors.license_number?.join(' ') }}</div>
        </div>
        <div class="col-md-4">
          <label for="license-period" class="form-label">License Period *</label
          ><select
            id="license-period"
            v-model.number="form.license_period"
            class="form-select"
            required
          >
            <option :value="1">1 year</option>
            <option :value="2">2 years</option>
            <option :value="3">3 years</option>
          </select>
        </div>
        <div class="col-md-4">
          <label for="license-issued" class="form-label">Issue Date *</label
          ><input
            id="license-issued"
            v-model="form.issue_date"
            class="form-control"
            type="date"
            required
            :class="{ 'is-invalid': errors.issue_date }"
          />
          <div class="invalid-feedback">{{ errors.issue_date?.join(' ') }}</div>
        </div>
        <div class="col-md-4">
          <label for="license-expiry" class="form-label">Expiry Date *</label
          ><input
            id="license-expiry"
            v-model="form.expiry_date"
            class="form-control"
            type="date"
            required
            :class="{ 'is-invalid': errors.expiry_date }"
            @input="expiryTouched = true"
          />
          <div class="form-text">
            Suggested from the selected term; confirm against the RTAD document.
          </div>
          <div class="invalid-feedback">{{ errors.expiry_date?.join(' ') }}</div>
        </div>
        <div class="col-md-4">
          <label for="license-renewal-date" class="form-label"
            >Renewal Date{{ mode === 'renew' ? ' *' : '' }}</label
          ><input
            id="license-renewal-date"
            v-model="form.renewal_date"
            class="form-control"
            type="date"
            :required="mode === 'renew'"
            :class="{ 'is-invalid': errors.renewal_date }"
          />
          <div class="invalid-feedback">{{ errors.renewal_date?.join(' ') }}</div>
        </div>
        <div class="col-md-4">
          <label for="license-fee" class="form-label">Renewal Fee (MMK) *</label
          ><input
            id="license-fee"
            v-model="form.renewal_fee"
            class="form-control"
            type="number"
            min="0"
            step="0.01"
            required
            :class="{ 'is-invalid': errors.renewal_fee }"
          />
          <div class="invalid-feedback">{{ errors.renewal_fee?.join(' ') }}</div>
        </div>
        <div class="col-md-4">
          <label for="license-region" class="form-label">License Office Region *</label
          ><select
            id="license-region"
            v-model="form.office_region"
            class="form-select"
            required
            :class="{ 'is-invalid': errors.office_region }"
          >
            <option value="">Select region</option>
            <option v-for="region in Object.keys(offices)" :key="region">{{ region }}</option>
          </select>
          <div class="invalid-feedback">{{ errors.office_region?.join(' ') }}</div>
        </div>
        <div class="col-md-6">
          <label for="license-branch" class="form-label">License Office Branch *</label
          ><select
            id="license-branch"
            v-model="form.office_branch"
            class="form-select"
            required
            :class="{ 'is-invalid': errors.office_branch }"
          >
            <option value="">Select branch</option>
            <option v-for="branch in offices[form.office_region] || []" :key="branch">
              {{ branch }}
            </option>
          </select>
          <div class="invalid-feedback">{{ errors.office_branch?.join(' ') }}</div>
        </div>
        <div class="col-md-6">
          <label for="license-documents" class="form-label">Documents</label
          ><input
            id="license-documents"
            class="form-control"
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            multiple
            @change="collectFiles"
          />
          <div class="form-text">PDF, JPG, or PNG up to 10 MB each. Stored privately.</div>
        </div>
        <div class="col-12">
          <label for="license-remarks" class="form-label">Remarks</label
          ><textarea
            id="license-remarks"
            v-model="form.remarks"
            class="form-control"
            rows="3"
            maxlength="20000"
            :class="{ 'is-invalid': errors.remarks }"
          ></textarea>
          <div class="invalid-feedback">{{ errors.remarks?.join(' ') }}</div>
        </div>
        <div class="col-12">
          <button class="btn btn-primary" :disabled="saving">
            <BaseLoading v-if="saving" label="Saving license…" /><template v-else>{{
              mode === 'renew' ? 'Record Renewal' : 'Save Car License'
            }}</template></button
          ><RouterLink to="/car-licenses" class="btn btn-outline-secondary ms-2">Cancel</RouterLink>
        </div>
      </form>
    </section>
  </section>
</template>
