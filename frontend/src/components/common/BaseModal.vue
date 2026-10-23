<script setup lang="ts">
defineProps<{ open:boolean; title:string }>()
const emit=defineEmits<{ close:[] }>()
</script>
<template><div v-if="open" class="gss-modal-backdrop" @click.self="emit('close')"><section class="gss-modal card-shell" role="dialog" aria-modal="true" :aria-label="title"><header><h2>{{ title }}</h2><button class="btn-outline-warm" aria-label="Close" @click="emit('close')">×</button></header><div><slot /></div><footer><slot name="actions" /></footer></section></div></template>
<style scoped>.gss-modal-backdrop{position:fixed;inset:0;z-index:2000;background:#24160eb3;display:grid;place-items:center;padding:1rem}.gss-modal{width:min(100%,600px);padding:1.25rem}.gss-modal header,.gss-modal footer{display:flex;justify-content:space-between;align-items:center;gap:1rem}.gss-modal h2{font-size:1.2rem}</style>