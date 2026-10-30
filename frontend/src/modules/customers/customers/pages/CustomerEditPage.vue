<script setup lang="ts">
import { onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import CustomerForm from '../components/CustomerForm.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import { customerApi, customerError } from '../api'
import type { Customer } from '../types'
const route = useRoute(), customer = ref<Customer | null>(null), error = ref(''), loading = ref(false)
let request = 0
onUnmounted(() => { request++ })
async function load() {
  const version = ++request
  const id = Number(route.params.id)
  customer.value = null; error.value = ''
  if (!Number.isInteger(id) || id < 1) { error.value = 'Invalid customer ID.'; return }
  loading.value = true
  try { const data = await customerApi.get(id); if (version === request) customer.value = data }
  catch (cause) { if (version === request) error.value = customerError(cause) }
  finally { if (version === request) loading.value = false }
}
watch(() => route.params.id, load, { immediate: true })
</script>
<template><main class="fleet-module page-content"><BaseLoading v-if="loading" label="Loading customer…"/><div v-else-if="error" class="alert alert-danger" role="alert">{{ error }} <button class="btn-outline-warm" @click="load">Retry</button><RouterLink to="/customers" class="ms-3">Back to customers</RouterLink></div><CustomerForm v-else-if="customer" :key="customer.id" :customer="customer"/></main></template>
