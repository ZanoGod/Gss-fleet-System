<script setup lang="ts">
import { ref } from 'vue'
import BaseLoading from './BaseLoading.vue'

defineProps<{ singular: string; saving: boolean }>()
const emit = defineEmits<{ confirm: [] }>()
const confirmDialog = ref<HTMLDialogElement | null>(null)
defineExpose({
  open: () => confirmDialog.value?.showModal(),
  close: () => confirmDialog.value?.close(),
})
</script>

<template>
  <dialog
    ref="confirmDialog"
    class="resource-confirm card-shell p-4"
    aria-labelledby="delete-title"
  >
    <h2 id="delete-title" class="h5">Delete {{ singular }}?</h2>
    <p>Referenced records and maintenance service history are protected.</p>
    <div class="d-flex gap-2">
      <button autofocus class="btn-outline-warm" @click="confirmDialog?.close()">Keep record</button
      ><button class="btn-gold" :disabled="saving" @click="emit('confirm')">
        <BaseLoading v-if="saving" label="Deleting…" /><template v-else>Delete record</template>
      </button>
    </div>
  </dialog>
</template>
