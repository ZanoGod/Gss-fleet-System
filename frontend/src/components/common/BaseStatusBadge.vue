<script setup lang="ts">
import { computed } from 'vue'
const props=defineProps<{ status: string }>()
const tone=computed(()=>{const s=props.status.toLowerCase();return /cancel|expired|inactive|failed/.test(s)?'danger':/active|available|completed|paid/.test(s)?'success':/pending|scheduled|upcoming/.test(s)?'warning':'neutral'})
</script>
<template><span class="gss-status" :class="'tone-'+tone">{{ status }}</span></template>
<style scoped>.gss-status{display:inline-block;padding:.28rem .65rem;border-radius:999px;font-size:.78rem;font-weight:700;background:var(--gss-palette-eee);color:var(--gss-palette-57483f)}.tone-success{background:var(--gss-status-success-background);color:var(--gss-status-success-text)}.tone-warning{background:var(--gss-status-warning-background);color:var(--gss-status-warning-text)}.tone-danger{background:var(--gss-status-danger-background);color:var(--gss-status-danger-text)}</style>
