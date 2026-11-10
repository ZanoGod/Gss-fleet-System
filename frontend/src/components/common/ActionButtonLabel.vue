<script setup lang="ts">
import BaseLoading from './BaseLoading.vue'

defineProps<{ pending: boolean; loadingLabel: string }>()
</script>

<template>
  <span class="action-label">
    <span class="action-label__text" :class="{ 'action-label__hidden': pending }"><slot /></span>
    <span class="action-label__progress" :class="{ 'action-label__hidden': !pending }">
      <BaseLoading :label="loadingLabel" />
    </span>
  </span>
</template>

<style scoped>
.action-label {
  display: inline-grid;
  place-items: center;
}

.action-label > span {
  grid-area: 1 / 1;
}

.action-label__hidden {
  visibility: hidden;
}

.action-label__hidden :deep(.classic-loader) {
  animation-play-state: paused;
}
</style>
