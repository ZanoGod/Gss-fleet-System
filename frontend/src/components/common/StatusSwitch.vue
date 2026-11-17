<script setup lang="ts">
import { computed } from 'vue'

type StatusValue = boolean | 'Active' | 'Inactive'

const props = withDefaults(defineProps<{
  id: string
  label: string
  modelValue: StatusValue
  invalid?: boolean
  disabled?: boolean
}>(), { invalid: false, disabled: false })

const emit = defineEmits<{ 'update:modelValue': [value: StatusValue] }>()
const isActive = computed(() => props.modelValue === true || props.modelValue === 'Active')

function toggle() {
  emit('update:modelValue', typeof props.modelValue === 'boolean' ? !props.modelValue : isActive.value ? 'Inactive' : 'Active')
}
</script>

<template>
  <div class="operator-status-control">
    <button
      :id="id"
      class="operator-status-switch"
      :class="{ 'is-on': isActive, 'is-off': !isActive, 'is-invalid': invalid }"
      type="button"
      role="switch"
      :aria-label="`${label}: ${isActive ? 'Active' : 'Inactive'}`"
      :aria-checked="isActive"
      :aria-invalid="invalid || undefined"
      :disabled="disabled"
      @click="toggle"
    >
      <span class="operator-status-switch-knob"></span>
    </button>
    <span class="operator-status-value" :class="{ 'is-inactive': !isActive }" aria-hidden="true">
      {{ isActive ? 'Active' : 'Inactive' }}
    </span>
  </div>
</template>

<style scoped>
.operator-status-control { display:flex; align-items:center; gap:.65rem; min-height:38px; }
.operator-status-switch { display:inline-flex; align-items:center; width:48px; height:28px; padding:3px; border:1px solid var(--gss-button-outline-border); border-radius:999px; background:var(--gss-surface-card-header); box-shadow:inset 0 1px 2px var(--gss-shadow-tint-subtle); cursor:pointer; transition:background-color .18s,border-color .18s,box-shadow .18s; }
.operator-status-switch.is-on { border-color:var(--gss-button-primary-border); background:var(--gss-button-primary-bg); box-shadow:inset 0 1px 0 rgb(255 255 255 / 55%); }
.operator-status-switch.is-off { border-color:#d98b91; background:#fbe8e9; box-shadow:inset 0 1px 2px rgb(126 34 44 / 10%); }
.operator-status-switch:focus-visible { outline:3px solid color-mix(in srgb,var(--gss-brand-secondary),transparent 38%); outline-offset:2px; }
.operator-status-switch.is-invalid { border-color:var(--bs-form-invalid-border-color,#dc3545); }
.operator-status-switch-knob { width:20px; height:20px; border-radius:50%; background:var(--gss-surface-card); box-shadow:0 1px 3px var(--gss-shadow-tint-medium); transition:transform .18s cubic-bezier(.2,.8,.2,1); }
.operator-status-switch.is-on .operator-status-switch-knob { transform:translateX(20px); }
.operator-status-value { color:var(--gss-text-secondary); font-size:.84rem; font-weight:600; }
.operator-status-value.is-inactive { color:#a52834; }
.operator-status-switch:disabled { cursor:wait; opacity:.65; }
@media(prefers-reduced-motion:reduce) { .operator-status-switch,.operator-status-switch-knob { transition:none; } }
</style>
