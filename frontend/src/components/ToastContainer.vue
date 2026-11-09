<script setup lang="ts">
import { TransitionGroup } from 'vue'
import ToastNotification from './ToastNotification.vue'
import { toast, toastItems } from '@/services/toast'
</script>

<template>
  <div class="toast-container" aria-label="Notifications">
    <TransitionGroup name="toast" tag="div" class="toast-stack" @after-leave="toast.afterLeave">
      <ToastNotification v-for="item in toastItems" :key="item.id" :item="item" />
    </TransitionGroup>
  </div>
</template>
