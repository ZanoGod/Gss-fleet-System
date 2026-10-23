<script setup lang="ts">
defineProps<{ title: string; description?: string; icon?: string }>()
</script>
<template><div class="empty-state"><i class="bi" :class="icon || 'bi-inbox'" aria-hidden="true"></i><h2 class="h5 mt-3">{{ title }}</h2><p v-if="description" class="mb-0">{{ description }}</p><div class="mt-3"><slot /></div></div></template>