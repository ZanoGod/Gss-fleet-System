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
    <CustomerDetails
      v-if="valid"
      :key="id"
      :id="id"
      back-to="/customers"
      @deleted="router.push('/customers')"
    />
  </main>
</template>
