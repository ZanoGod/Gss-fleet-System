<script setup lang="ts">
withDefaults(defineProps<{ modelValue?: string; placeholder?: string; disabled?: boolean }>(),{modelValue:'',placeholder:'Search…',disabled:false})
const emit=defineEmits<{ 'update:modelValue':[value:string] }>()
</script>
<template><label class="gss-search"><i class="bi bi-search" aria-hidden="true"></i><input :value="modelValue" :placeholder="placeholder" :disabled="disabled" type="search" @input="emit('update:modelValue',($event.target as HTMLInputElement).value)"></label></template>
<style scoped>.gss-search{display:flex;align-items:center;gap:.55rem;padding:.45rem .75rem;border:1px solid var(--line);border-radius:12px;background:var(--paper-strong);color:var(--muted)}input{border:0;outline:0;background:transparent;min-width:10rem;color:var(--ink)}</style>