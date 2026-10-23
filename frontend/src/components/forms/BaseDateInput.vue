<script setup lang="ts">
withDefaults(defineProps<{ label:string; modelValue?:string; disabled?:boolean }>(),{modelValue:'',disabled:false})
const emit=defineEmits<{ 'update:modelValue':[value:string] }>()
</script>
<template><label class="form-field"><span>{{ label }}</span><input class="form-control" type="date" :value="modelValue" :disabled="disabled" @input="emit('update:modelValue',($event.target as HTMLInputElement).value)"></label></template>
<style scoped>.form-field{display:grid;gap:.4rem;font-weight:700;color:var(--cocoa-soft)}</style>