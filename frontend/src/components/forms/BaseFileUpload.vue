<script setup lang="ts">
defineProps<{ label:string; accept?:string; disabled?:boolean }>()
const emit=defineEmits<{ change:[file:File|null] }>()
</script>
<template><label class="form-field"><span>{{ label }}</span><input class="form-control" type="file" :accept="accept" :disabled="disabled" @change="emit('change',($event.target as HTMLInputElement).files?.[0]||null)"></label></template>
<style scoped>.form-field{display:grid;gap:.4rem;font-weight:700;color:var(--cocoa-soft)}</style>