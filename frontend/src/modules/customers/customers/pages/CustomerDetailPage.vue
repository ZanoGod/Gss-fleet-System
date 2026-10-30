<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CustomerDetails from '../components/CustomerDetails.vue'
const route = useRoute(), router = useRouter()
const id = computed(() => Number(route.params.id))
const valid = computed(() => Number.isInteger(id.value) && id.value > 0)
</script>
<template><main class="fleet-module page-content"><RouterLink to="/customers" class="btn-outline-warm d-inline-block mb-3">← Back to Customers</RouterLink><section class="card-shell p-4"><CustomerDetails v-if="valid" :key="id" :id="id" @deleted="router.push({ path: '/customers', query: { notice: 'deleted' } })"/><div v-else class="alert alert-danger" role="alert">Invalid customer ID.</div></section></main></template>
