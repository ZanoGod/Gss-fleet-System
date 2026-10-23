<script setup lang="ts">
import { computed } from 'vue'
const props=defineProps<{ status: string }>()
const tone=computed(()=>{const s=props.status.toLowerCase();return /active|available|completed|paid/.test(s)?'success':/pending|scheduled|upcoming/.test(s)?'warning':/cancel|expired|inactive|failed/.test(s)?'danger':'neutral'})
</script>
<template><span class="gss-status" :class="'tone-'+tone">{{ status }}</span></template>
<style scoped>.gss-status{display:inline-block;padding:.28rem .65rem;border-radius:999px;font-size:.78rem;font-weight:700;background:#eee;color:#57483f}.tone-success{background:#e4f4e8;color:#246338}.tone-warning{background:#fff1d0;color:#80540d}.tone-danger{background:#fde5e1;color:#8d3024}</style>