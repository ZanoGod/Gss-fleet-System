<script setup lang="ts">
import { onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import CustomerForm from '../components/CustomerForm.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { customerApi, customerError } from '../api'
import type { Customer } from '../types'
import { toast } from '@/services/toast'
const route = useRoute(),
  customer = ref<Customer | null>(null),
  error = ref(''),
  loading = ref(false)
let request = 0
onUnmounted(() => {
  request++
})
async function load() {
  const version = ++request
  const id = Number(route.params.id)
  customer.value = null
  if (!Number.isInteger(id) || id < 1) {
    error.value = 'Invalid customer ID.'
    toast.error(error.value)
    return
  }
  loading.value = true
  try {
    const data = await customerApi.get(id)
    if (version === request) {
      customer.value = data
      error.value = ''
    }
  } catch (cause) {
    if (version === request) {
      error.value = customerError(cause)
      toast.error(error.value)
    }
  } finally {
    if (version === request) loading.value = false
  }
}
watch(() => route.params.id, load, { immediate: true })
</script>
<template>
  <main class="fleet-module page-content">
    <div v-if="error" class="d-flex gap-2 mb-3">
      <button class="btn-outline-warm" :disabled="loading" @click="load">
        <BaseLoading v-if="loading" label="Retrying…" /><template v-else>Retry loading customer</template>
      </button>
      <RouterLink to="/customers" class="btn-outline-warm">Back to customers</RouterLink>
    </div>
    <PageSkeleton v-else-if="loading" kind="form" label="Loading customer…" />
    <CustomerForm v-else-if="customer" :key="customer.id" :customer="customer" />
  </main>
</template>
