<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
const route = useRoute()
const title = computed(() => String(route.meta.title || 'Dashboard'))
</script>
<template><nav aria-label="Breadcrumb" class="gss-breadcrumb"><ol><li><RouterLink to="/dashboard">Home</RouterLink></li><li aria-current="page">{{ title }}</li></ol></nav></template>
<style scoped>.gss-breadcrumb ol{display:flex;gap:.55rem;list-style:none;padding:0;margin:.35rem 0 0;color:var(--muted);font-size:.82rem}.gss-breadcrumb li+li:before{content:'/';margin-right:.55rem;color:#b8a797}.gss-breadcrumb a{color:var(--cocoa-soft);font-weight:700}</style>