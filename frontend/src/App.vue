<script setup lang="ts">
import { RouterView, useRoute } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
const route = useRoute()
</script>
<template><RouterView v-if="route.meta.public" /><AppSidebar v-else><RouterView /></AppSidebar></template>
