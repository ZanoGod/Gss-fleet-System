<script setup lang="ts">
defineProps<{ columns: Array<{key:string;label:string}>; rows?: Array<Record<string,unknown>> }>()
</script>
<template><div class="table-responsive"><table class="table data-table"><thead><tr><th v-for="col in columns" :key="col.key">{{ col.label }}</th></tr></thead><tbody><tr v-for="(row,index) in (rows||[])" :key="String(row.id??index)"><td v-for="col in columns" :key="col.key">{{ row[col.key] ?? '—' }}</td></tr><slot name="empty" /></tbody></table></div></template>