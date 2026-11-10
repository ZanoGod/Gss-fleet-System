<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CustomerDetails from '../components/CustomerDetails.vue'
import { toast } from '@/services/toast'
const route = useRoute(),
  router = useRouter()
const id = computed(() => Number(route.params.id))
const valid = computed(() => Number.isInteger(id.value) && id.value > 0)
if (!valid.value) toast.error('Invalid customer ID.')
</script>
<template>
  <main class="fleet-module page-content">
    <RouterLink
      to="/customers"
      class="btn gss-button gss-button--outline gss-button--neutral d-inline-block mb-3"
      >← Back to Customers</RouterLink
    >
    <section class="card-shell p-4">
      <CustomerDetails v-if="valid" :key="id" :id="id" @deleted="router.push('/customers')" />
    </section>
  </main>
</template>
