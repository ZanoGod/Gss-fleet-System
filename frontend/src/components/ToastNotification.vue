<script setup lang="ts">
import type { ToastItem } from '@/services/toast'
import { toast } from '@/services/toast'

defineProps<{ item: ToastItem }>()

const icons = {
  success: 'bi-check-circle-fill',
  error: 'bi-x-circle-fill',
  warning: 'bi-exclamation-triangle-fill',
  info: 'bi-info-circle-fill',
} as const
</script>

<template>
  <article
    class="toast-notification"
    :class="[`toast-notification--${item.type}`, { 'is-paused': item.paused }]"
    :data-toast-id="item.id"
    :role="item.type === 'error' ? 'alert' : 'status'"
    :aria-live="item.type === 'error' ? 'assertive' : 'polite'"
    aria-atomic="true"
    @mouseenter="toast.pause(item.id)"
    @mouseleave="!($event.currentTarget as HTMLElement).matches(':focus-within') && toast.resume(item.id)"
    @focusin="toast.pause(item.id)"
    @focusout="!($event.currentTarget as HTMLElement).matches(':hover') && toast.resume(item.id)"
  >
    <div class="toast-progress" aria-hidden="true"><span /></div>
    <i class="toast-status-icon bi" :class="icons[item.type]" aria-hidden="true"></i>
    <p class="toast-message">{{ item.message }}</p>
    <button
      type="button"
      class="toast-close"
      aria-label="Dismiss notification"
      @click="toast.dismiss(item.id)"
    >
      <i class="bi bi-x" aria-hidden="true"></i>
    </button>
  </article>
</template>
