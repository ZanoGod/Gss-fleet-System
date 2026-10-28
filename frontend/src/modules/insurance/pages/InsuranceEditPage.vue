<script setup lang="ts">import InsurancePolicyForm from '../components/InsurancePolicyForm.vue'; import { useRoute } from 'vue-router'; const route=useRoute()</script>
<template><main class="fleet-module page-content"><InsurancePolicyForm :policy-id="Number(route.params.id)" /></main></template>
