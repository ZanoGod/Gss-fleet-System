<script setup lang="ts">import MonthlyContractForm from'../components/MonthlyContractForm.vue';import{useRoute}from'vue-router';const route=useRoute()</script>
<template><main class="fleet-module page-content"><MonthlyContractForm :renewal-from-id="Number(route.params.id)"/></main></template>
