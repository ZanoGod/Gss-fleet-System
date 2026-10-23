<script setup lang="ts">
withDefaults(defineProps<{ label:string; modelValue?:string|number; type?:string; error?:string; disabled?:boolean; name?:string }>(),{modelValue:'',type:'text',disabled:false})
const emit=defineEmits<{ 'update:modelValue':[value:string] }>()
</script>
<template><label class="form-field"><span>{{ label }}</span><input :name="name" :type="type" :value="modelValue" :disabled="disabled" class="form-control" :aria-invalid="Boolean(error)" @input="emit('update:modelValue',($event.target as HTMLInputElement).value)"><small v-if="error" class="text-danger">{{ error }}</small></label></template>
<style scoped>.form-field{display:grid;gap:.4rem;font-weight:700;color:var(--cocoa-soft)}</style>