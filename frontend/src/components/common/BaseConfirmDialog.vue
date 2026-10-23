<script setup lang="ts">
import BaseModal from './BaseModal.vue'
defineProps<{ open:boolean; title?:string; message:string; busy?:boolean }>()
const emit=defineEmits<{ close:[]; confirm:[] }>()
</script>
<template><BaseModal :open="open" :title="title||'Please confirm'" @close="emit('close')"><p>{{ message }}</p><template #actions><button class="btn-outline-warm" :disabled="busy" @click="emit('close')">Cancel</button><button class="btn-gold" :disabled="busy" @click="emit('confirm')">{{ busy?'Please wait…':'Confirm' }}</button></template></BaseModal></template>