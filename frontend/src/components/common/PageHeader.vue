<script setup lang="ts">
defineProps<{ title: string; description: string; icon: string }>()
</script>
<template>
  <header class="page-heading">
    <div class="fleet-intro">
      <span class="fleet-intro-icon"><i class="bi" :class="icon" aria-hidden="true" /></span>
      <div>
        <h2 class="h4 mb-1">{{ title }}</h2>
        <p>{{ description }}</p>
      </div>
    </div>
    <slot />
  </header>
</template>
