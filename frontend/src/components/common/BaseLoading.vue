<script setup lang="ts">
import { Classic } from '@/components/loading-ui/classic'
withDefaults(defineProps<{ label: string; showText?: boolean }>(), { showText: true })
</script>

<template>
  <span class="gss-inline-loading" role="status" aria-live="polite">
    <Classic />
    <span :class="{ 'visually-hidden': !showText }">{{ label }}</span>
  </span>
</template>
