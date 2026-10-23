<script setup lang="ts">
withDefaults(defineProps<{ label: string; modelValue?: string; options: Array<{label:string;value:string}>; disabled?: boolean }>(),{modelValue:'',disabled:false})
const emit=defineEmits<{ 'update:modelValue':[value:string] }>()
</script>
<template><label class="gss-filter"><span>{{ label }}</span><select :value="modelValue" :disabled="disabled" @change="emit('update:modelValue',($event.target as HTMLSelectElement).value)"><option value="">All</option><option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option></select></label></template>
<style scoped>.gss-filter{display:flex;align-items:center;gap:.5rem;color:var(--muted);font-size:.85rem}select{border:1px solid var(--line);border-radius:10px;padding:.55rem;background:var(--paper-strong);color:var(--ink)}</style>