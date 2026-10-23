<script setup lang="ts">
withDefaults(defineProps<{ label:string; modelValue?:string; options:Array<{label:string;value:string}>; disabled?:boolean }>(),{modelValue:'',disabled:false})
const emit=defineEmits<{ 'update:modelValue':[value:string] }>()
</script>
<template><label class="form-field"><span>{{ label }}</span><select class="form-select" :value="modelValue" :disabled="disabled" @change="emit('update:modelValue',($event.target as HTMLSelectElement).value)"><option value="">Choose…</option><option v-for="item in options" :key="item.value" :value="item.value">{{ item.label }}</option></select></label></template>
<style scoped>.form-field{display:grid;gap:.4rem;font-weight:700;color:var(--cocoa-soft)}</style>