<script setup lang="ts">
import BaseDateInput from '@/components/forms/BaseDateInput.vue'
const disabled=true
</script>
<template><section class="card-shell section-card report-filters"><h2 class="h5">Report filters</h2><div class="report-filter-fields"><BaseDateInput label="From" disabled/><BaseDateInput label="To" disabled/><span class="small muted">Filters activate when verified report endpoints are available.</span></div></section></template>
<style scoped>.report-filters{padding:1.2rem}.report-filter-fields{display:flex;align-items:end;gap:1rem;flex-wrap:wrap}.report-filter-fields :deep(.form-field){min-width:180px}</style>