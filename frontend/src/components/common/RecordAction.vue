<script setup lang="ts">
import { type RouteLocationRaw } from 'vue-router'

const props = withDefaults(
  defineProps<{
    action: 'view' | 'edit' | 'delete' | 'back'
    to?: RouteLocationRaw
    label?: string
    ariaLabel?: string
    iconOnly?: boolean
    disabled?: boolean
    loading?: boolean
    type?: 'button' | 'submit' | 'reset'
    title?: string
  }>(),
  { iconOnly: false, disabled: false, loading: false, type: 'button' },
)
const emit = defineEmits<{ click: [event: MouseEvent] }>()
const actionMeta = {
  view: { icon: 'bi-eye', label: 'View' },
  edit: { icon: 'bi-pencil-square', label: 'Edit' },
  delete: { icon: 'bi-trash3', label: 'Delete' },
  back: { icon: 'bi-arrow-left', label: 'Back' },
} as const
function isBlocked(event: MouseEvent) {
  if (props.disabled || props.loading) {
    event.preventDefault()
    event.stopPropagation()
    return true
  }
  return false
}
function handleClick(event: MouseEvent) {
  if (isBlocked(event)) return
  emit('click', event)
}
function handleLinkClick(event: MouseEvent, navigate: (event: MouseEvent) => void) {
  if (isBlocked(event)) return
  emit('click', event)
  navigate(event)
}
</script>

<template>
  <RouterLink v-if="to" :to="to" custom v-slot="{ href, navigate }">
    <a
      :href="href"
      :aria-disabled="disabled || loading || undefined"
      :aria-busy="loading || undefined"
      :aria-label="iconOnly ? ariaLabel || label || actionMeta[action].label : undefined"
      :title="title || (iconOnly ? ariaLabel || label || actionMeta[action].label : undefined)"
      :tabindex="disabled || loading ? -1 : undefined"
      class="record-action"
      :class="[
        `record-action--${action}`,
        { 'record-action--icon-only': iconOnly, 'record-action--loading': loading },
      ]"
      @click="handleLinkClick($event, navigate)"
    >
      <i
        class="record-action__icon"
        :class="loading ? 'bi bi-arrow-repeat record-action__spinner' : `bi ${actionMeta[action].icon}`"
        aria-hidden="true"
      ></i>
      <span v-if="!iconOnly">{{ label || actionMeta[action].label }}</span>
    </a>
  </RouterLink>
  <button
    v-else
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading || undefined"
    :aria-label="iconOnly ? ariaLabel || label || actionMeta[action].label : undefined"
    :title="title || (iconOnly ? ariaLabel || label || actionMeta[action].label : undefined)"
    class="record-action"
    :class="[
      `record-action--${action}`,
      { 'record-action--icon-only': iconOnly, 'record-action--loading': loading },
    ]"
    @click="handleClick"
  >
    <i
      class="record-action__icon"
      :class="loading ? 'bi bi-arrow-repeat record-action__spinner' : `bi ${actionMeta[action].icon}`"
      aria-hidden="true"
    ></i>
    <span v-if="!iconOnly">{{ label || actionMeta[action].label }}</span>
  </button>
</template>
