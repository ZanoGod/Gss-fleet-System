<script setup lang="ts">
import CustomerForm from '../components/CustomerForm.vue'
import { useCreateFormSkeleton } from '@/components/common/useCreateFormSkeleton'

const loading = useCreateFormSkeleton()
</script>
<template>
  <main class="fleet-module page-content"><CustomerForm :loading="loading" /></main>
</template>
