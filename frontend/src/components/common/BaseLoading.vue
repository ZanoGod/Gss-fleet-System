<script setup lang="ts">
defineProps<{ label?: string }>()
</script>
<template><div class="gss-loading" role="status"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>{{ label || 'Loading…' }}</span></div></template>
<style scoped>.gss-loading{display:flex;align-items:center;gap:.65rem;padding:1.5rem;color:var(--muted)}</style>