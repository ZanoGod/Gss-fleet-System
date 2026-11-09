<script setup lang="ts">
import BaseModal from './BaseModal.vue'
import BaseLoading from './BaseLoading.vue'
defineProps<{ open: boolean; title?: string; message: string; busy?: boolean }>()
const emit = defineEmits<{ close: []; confirm: [] }>()
</script>
<template>
  <BaseModal :open="open" :title="title || 'Please confirm'" :busy="busy" @close="emit('close')"
    ><p>{{ message }}</p>
    <template #actions
      ><button class="btn-outline-warm" :disabled="busy" @click="emit('close')">Cancel</button
      ><button class="btn-gold" :disabled="busy" @click="emit('confirm')">
        <BaseLoading v-if="busy" label="Confirming…" /><template v-else>Confirm</template>
      </button></template
    ></BaseModal
  >
</template>
