<script setup lang="ts">
withDefaults(defineProps<{ label:string; modelValue?:string; rows?:number; disabled?:boolean }>(),{modelValue:'',rows:4,disabled:false})
const emit=defineEmits<{ 'update:modelValue':[value:string] }>()
</script>
<template><label class="form-field"><span>{{ label }}</span><textarea class="form-control" :rows="rows" :value="modelValue" :disabled="disabled" @input="emit('update:modelValue',($event.target as HTMLTextAreaElement).value)"></textarea></label></template>
<style scoped>.form-field{display:grid;gap:.4rem;font-weight:700;color:var(--cocoa-soft)}</style>