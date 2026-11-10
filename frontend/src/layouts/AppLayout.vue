<script setup lang="ts">
import { RouterView } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
</script>
<template>
  <AppSidebar>
    <RouterView v-slot="{ Component, route }">
      <component :is="Component" :key="route.path" />
    </RouterView>
  </AppSidebar>
</template>
