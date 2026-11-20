<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'

export interface SearchableVehicleOption {
  value: string | number
  label: string
  description?: string
  disabled?: boolean
}

const props = withDefaults(
  defineProps<{
    id: string
    modelValue: string | number | null
    options: SearchableVehicleOption[]
    placeholder?: string
    required?: boolean
    invalid?: boolean
    disabled?: boolean
    emptyOptionLabel?: string
  }>(),
  { placeholder: 'Type a plate number to search', required: false, invalid: false, disabled: false },
)
const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
}>()

const query = ref('')
const open = ref(false)
const listbox = ref<HTMLElement | null>(null)
const matchingOptions = computed(() => {
  const search = query.value.trim().toLocaleLowerCase()
  return props.options
    .filter((option) => !search || option.label.toLocaleLowerCase().includes(search))
    .sort((a, b) => {
      const aPrefix = search && a.label.toLocaleLowerCase().startsWith(search) ? 0 : 1
      const bPrefix = search && b.label.toLocaleLowerCase().startsWith(search) ? 0 : 1
      return aPrefix - bPrefix || a.label.localeCompare(b.label)
    })
})
const visibleOptions = computed(() => matchingOptions.value.slice(0, 8))

watch(
  () => [props.modelValue, props.options] as const,
  () => {
    if (props.modelValue === null) return
    const selected = props.options.find((option) => option.value === props.modelValue)
    if (selected) query.value = selected.label
  },
  { deep: true, immediate: true },
)

function onInput(event: Event) {
  query.value = (event.target as HTMLInputElement).value
  const exact = props.options.find(
    (option) => option.label.toLocaleLowerCase() === query.value.trim().toLocaleLowerCase(),
  )
  emit('update:modelValue', exact && !exact.disabled ? exact.value : null)
  open.value = true
}

function choose(option: SearchableVehicleOption) {
  if (option.disabled) return
  query.value = option.label
  emit('update:modelValue', option.value)
  open.value = false
}

function chooseEmpty() {
  query.value = ''
  emit('update:modelValue', null)
  open.value = false
}

async function focusFirstOption() {
  if (!visibleOptions.value.some((option) => !option.disabled)) return
  open.value = true
  await nextTick()
  listbox.value?.querySelector<HTMLButtonElement>('button:not(:disabled)')?.focus()
}
</script>

<template>
  <div class="searchable-vehicle-select position-relative" :class="{ 'is-open': open && !disabled }">
    <input
      :id="id"
      :value="query"
      class="form-control"
      :class="{ 'is-invalid': invalid }"
      :required="required"
      :disabled="disabled"
      :placeholder="placeholder"
      autocomplete="off"
      role="combobox"
      aria-autocomplete="list"
      :aria-expanded="open && !disabled"
      :aria-controls="matchingOptions.length || (emptyOptionLabel && !query.trim()) ? id + '-options' : undefined"
      :aria-invalid="invalid || undefined"
      @input="onInput"
      @focus="open = true"
      @keydown.esc="open = false"
      @keydown.down.prevent="focusFirstOption"
    />
    <div v-if="open && !disabled" class="searchable-vehicle-menu position-absolute start-0 end-0">
      <div v-if="matchingOptions.length" class="searchable-vehicle-menu-heading" aria-hidden="true">
        {{ matchingOptions.length > 8 ? `Top 8 of ${matchingOptions.length} matches` : `${matchingOptions.length} ${matchingOptions.length === 1 ? 'match' : 'matches'}` }}
      </div>
      <div
        v-if="matchingOptions.length || (emptyOptionLabel && !query.trim())"
        :id="id + '-options'"
        ref="listbox"
        class="searchable-vehicle-options-list"
        role="listbox"
        aria-label="Matching fleet vehicles"
      >
        <button
          v-if="emptyOptionLabel && !query.trim()"
          type="button"
          class="searchable-vehicle-option-button"
          role="option"
          :aria-selected="modelValue === null"
          @mousedown.prevent="chooseEmpty"
          @click="chooseEmpty"
        >
          {{ emptyOptionLabel }}
        </button>
        <button
          v-for="option in visibleOptions"
          :key="String(option.value)"
          type="button"
          class="searchable-vehicle-option-button"
          role="option"
          :disabled="option.disabled"
          :aria-selected="modelValue === option.value"
          @mousedown.prevent="choose(option)"
          @click="choose(option)"
        >
          <span class="fw-semibold">{{ option.label }}</span>
          <span v-if="option.description" class="searchable-vehicle-description">{{ option.description }}</span>
        </button>
      </div>
      <div v-else-if="query.trim()" class="searchable-vehicle-empty" role="status">
        No registered fleet plates match “{{ query }}”. Keep typing or check the plate number.
      </div>
    </div>
  </div>
</template>

<style scoped>
.searchable-vehicle-select.is-open {
  z-index: 30;
}

.searchable-vehicle-menu {
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

.searchable-vehicle-menu-heading {
  position: sticky;
  top: 0;
  padding: 0.45rem 0.8rem;
  border-bottom: 1px solid var(--bs-border-color, #d9dee5);
  background: var(--bs-body-bg, #fff);
  color: var(--bs-secondary-color, #667085);
  font-size: 0.78rem;
  font-weight: 600;
}

.searchable-vehicle-option-button + .searchable-vehicle-option-button {
  border-top: 1px solid var(--bs-border-color, #edf0f3);
}

.searchable-vehicle-option-button {
  display: flex;
  width: 100%;
  min-height: 2.8rem;
  align-items: center;
  justify-content: space-between;
  gap: 0.25rem 0.75rem;
  padding: 0.55rem 0.8rem;
  border: 0;
  background: var(--bs-body-bg, #fff);
  color: inherit;
  text-align: left;
  cursor: pointer;
}

.searchable-vehicle-option-button:hover:not(:disabled),
.searchable-vehicle-option-button:focus-visible {
  background: var(--bs-tertiary-bg, #f1f4f7);
}

.searchable-vehicle-option-button:focus-visible {
  outline: 2px solid var(--bs-primary, #0d6efd);
  outline-offset: -2px;
}

.searchable-vehicle-option-button:disabled {
  color: var(--bs-secondary-color, #667085);
  cursor: not-allowed;
  opacity: 0.65;
}

.searchable-vehicle-description {
  color: var(--bs-secondary-color, #667085);
  font-size: 0.85rem;
  text-align: right;
}

.searchable-vehicle-empty {
  padding: 0.8rem;
  color: var(--bs-secondary-color, #667085);
  font-size: 0.9rem;
}

@media (max-width: 575.98px) {
  .searchable-vehicle-option-button {
    align-items: flex-start;
    flex-direction: column;
    gap: 0.1rem;
  }

  .searchable-vehicle-description {
    text-align: left;
  }
}
</style>
