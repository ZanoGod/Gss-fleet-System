<script setup lang="ts">
import { computed } from 'vue'
import { useCreateFormSkeleton } from '@/components/common/useCreateFormSkeleton'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import { useModuleRecord } from '@/modules/shared/moduleRequests'
import { operatorConfig as config } from '../config'

const { mode } = defineProps<{ mode: 'create' | 'edit' }>()
const fields = config.fields
const { record, initial, form, errors, saving, save, link } = useModuleRecord(config, () => mode)
const showingCreateSkeleton = useCreateFormSkeleton(mode === 'create')
const loading = computed(() => initial.value || showingCreateSkeleton.value)
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
    <span v-if="loading" class="visually-hidden" role="status">Loading {{ config.singular.toLowerCase() }}…</span>
    <section v-if="loading || mode === 'create' || record" class="card-shell p-4">
      <form
        class="row g-3"
        :class="{ 'gss-form-skeleton': loading }"
        :inert="loading"
        :aria-hidden="loading || undefined"
        @submit.prevent="!loading && save()"
      >
        <p class="text-muted mb-0">{{ config.formHelp }}</p>
        <fieldset class="row g-3 border-0 m-0 p-0">
          <div
            v-for="field in fields"
            :key="field.key"
            :class="field.type === 'textarea' ? 'col-12' : 'col-md-6'"
          >
            <label :id="`label-${field.key}`" :for="`field-${field.key}`" class="form-label"
              >{{ field.label }}<span v-if="field.required" aria-hidden="true"> *</span></label
            ><div
              v-if="field.key === 'operator_status'"
              class="operator-status-control"
              :class="{ 'has-error': errors[field.key] }"
            >
              <button
                :id="`field-${field.key}`"
                class="operator-status-switch"
                :class="{
                  'is-on': form[field.key] === 'Active',
                  'is-off': form[field.key] !== 'Active',
                  'is-invalid': errors[field.key],
                }"
                type="button"
                role="switch"
                :aria-labelledby="`label-${field.key}`"
                :aria-checked="form[field.key] === 'Active'"
                :aria-invalid="!!errors[field.key]"
                :aria-describedby="`error-${field.key}`"
                @click="form[field.key] = form[field.key] === 'Active' ? 'Inactive' : 'Active'"
              >
                <span class="operator-status-switch-knob"></span>
              </button>
              <span
                class="operator-status-value"
                :class="{ 'is-inactive': form[field.key] !== 'Active' }"
                aria-hidden="true"
                >{{ form[field.key] }}</span
              >
            </div><select
              v-else-if="field.options"
              :id="`field-${field.key}`"
              v-model="form[field.key]"
              class="form-select"
              :required="field.required"
              :class="{ 'is-invalid': errors[field.key] }"
              :aria-invalid="!!errors[field.key]"
              :aria-describedby="`error-${field.key}`"
            >
              <option value="">Select {{ field.label.toLowerCase() }}</option>
              <option v-for="option in field.options" :key="option">
                {{ option }}
              </option></select
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

<style scoped>
.operator-status-control {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-height: 38px;
}

.operator-status-switch {
  display: inline-flex;
  align-items: center;
  width: 48px;
  height: 28px;
  padding: 3px;
  border: 1px solid var(--gss-button-outline-border);
  border-radius: 999px;
  background: var(--gss-surface-card-header);
  box-shadow: inset 0 1px 2px var(--gss-shadow-tint-subtle);
  cursor: pointer;
  transition: background-color 0.18s, border-color 0.18s, box-shadow 0.18s;
}

.operator-status-switch.is-on {
  border-color: var(--gss-button-primary-border);
  background: var(--gss-button-primary-bg);
  box-shadow: inset 0 1px 0 rgb(255 255 255 / 55%);
}

.operator-status-switch.is-off {
  border-color: #d98b91;
  background: #fbe8e9;
  box-shadow: inset 0 1px 2px rgb(126 34 44 / 10%);
}

.operator-status-switch:focus-visible {
  outline: 3px solid color-mix(in srgb, var(--gss-brand-secondary), transparent 38%);
  outline-offset: 2px;
}

.operator-status-switch.is-invalid {
  border-color: var(--bs-form-invalid-border-color, #dc3545);
}

.operator-status-control.has-error + .invalid-feedback {
  display: block;
}

.operator-status-switch-knob {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--gss-surface-card);
  box-shadow: 0 1px 3px var(--gss-shadow-tint-medium);
  transition: transform 0.18s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.operator-status-switch.is-on .operator-status-switch-knob {
  transform: translateX(20px);
}

.operator-status-value {
  color: var(--gss-text-secondary);
  font-size: 0.84rem;
  font-weight: 600;
}

.operator-status-value.is-inactive {
  color: #a52834;
}
</style>
