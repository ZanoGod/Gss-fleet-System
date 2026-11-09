<script setup lang="ts">
import StatusCard from './StatusCard.vue'
import type { StatusMetric } from './statusMetrics'
withDefaults(defineProps<{ items: StatusMetric[]; loading?: boolean; label: string }>(), {
  loading: false,
})
</script>
<template>
  <span v-if="loading" class="visually-hidden" role="status"
    >Loading {{ label.toLowerCase() }}</span
  >
  <section class="status-summary mb-4" :aria-label="label" :aria-busy="loading">
    <StatusCard v-for="{ key, ...item } in items" :key="key" v-bind="item" :loading="loading" />
  </section>
</template>
<style scoped>
.status-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
  gap: 1rem;
}
</style>
