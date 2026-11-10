<script setup lang="ts">
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { useModuleRecord } from '@/modules/shared/moduleRequests'
import { maintenanceConfig as config } from '../config'
import {
  useMaintenanceVehicles,
  serializeVehicle,
  mapVehicleErrors,
} from '../useMaintenanceVehicles'

const { mode } = defineProps<{ mode: 'create' | 'edit' }>()
const { options, initializeForm } = useMaintenanceVehicles()
const fields = config.fields
const { record, initial, form, errors, saving, save, link } = useModuleRecord(config, () => mode, {
  initializeForm,
  serialize: serializeVehicle,
  mapErrors: mapVehicleErrors,
})
</script>

<template>
  <section class="fleet-module page-content resource-workspace" aria-label="Module management">
    <div class="d-flex flex-wrap justify-content-end">
      <PageHeader
        :title="`${mode === 'create' ? 'Add' : 'Edit'} ${config.singular}`"
        :description="config.description"
        :icon="config.icon"
      >
        <RouterLink :to="link(config.path)" class="btn btn-primary">{{
          `All ${config.title}`
        }}</RouterLink>
      </PageHeader>
    </div>
    <PageSkeleton v-if="initial" kind="form" :label="`Loading ${config.singular.toLowerCase()}`" />
    <section v-else-if="mode === 'create' || record" class="card-shell p-4">
      <form class="row g-3" @submit.prevent="save">
        <p class="text-muted mb-0">{{ config.formHelp }}</p>
        <fieldset class="row g-3 border-0 m-0 p-0">
          <div
            v-for="field in fields"
            :key="field.key"
            :class="field.type === 'textarea' ? 'col-12' : 'col-md-6'"
          >
            <label :for="`field-${field.key}`" class="form-label"
              >{{ field.label }}<span v-if="field.required" aria-hidden="true"> *</span></label
            ><select
              v-if="field.options || field.key === 'vehicle_key'"
              :id="`field-${field.key}`"
              v-model="form[field.key]"
              class="form-select"
              :required="field.required"
              :class="{ 'is-invalid': errors[field.key] }"
              :aria-invalid="!!errors[field.key]"
              :aria-describedby="`error-${field.key}`"
            >
              <option value="">Select {{ field.label.toLowerCase() }}</option>
              <template v-if="field.key === 'vehicle_key'"
                ><option v-for="option in options" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option></template
              ><template v-else
                ><option v-for="option in field.options" :key="option">
                  {{ option }}
                </option></template
              ></select
            ><textarea
              v-else-if="field.type === 'textarea'"
              :id="`field-${field.key}`"
              v-model="form[field.key]"
              class="form-control"
              rows="4"
              :maxlength="field.max || 20000"
              :class="{ 'is-invalid': errors[field.key] }"
              :aria-invalid="!!errors[field.key]"
              :aria-describedby="`error-${field.key}`"
            /><input
              v-else
              :id="`field-${field.key}`"
              v-model="form[field.key]"
              :type="field.type || 'text'"
              :required="field.required"
              :maxlength="field.max"
              :min="field.min"
              :max="field.maxNumber"
              :step="field.step"
              class="form-control"
              :class="{ 'is-invalid': errors[field.key] }"
              :aria-invalid="!!errors[field.key]"
              :aria-describedby="`error-${field.key}`"
            />
            <div :id="`error-${field.key}`" class="invalid-feedback">
              {{ errors[field.key]?.join(' ') }}
            </div>
          </div>
          <div class="col-12">
            <button class="btn btn-primary" :disabled="saving">
              <BaseLoading v-if="saving" label="Saving…" /><template v-else
                >Save {{ config.singular }}</template
              ></button
            ><RouterLink :to="link(config.path)" class="btn btn-outline-secondary ms-2"
              >Cancel</RouterLink
            >
          </div>
        </fieldset>
      </form>
    </section>
  </section>
</template>
