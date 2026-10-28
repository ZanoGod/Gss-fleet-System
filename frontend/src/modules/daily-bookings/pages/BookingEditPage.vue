<script setup lang="ts">import DailyBookingForm from'../components/DailyBookingForm.vue';import{useRoute}from'vue-router';const route=useRoute()</script>
<template><main class="fleet-module page-content"><DailyBookingForm :booking-id="Number(route.params.id)"/></main></template>
