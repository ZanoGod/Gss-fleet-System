<script setup lang="ts">
import { useRoute, RouterLink } from 'vue-router'
const route = useRoute()
</script>
<template><section class="card-shell section-card"><div class="empty-state"><i class="bi bi-window-stack"></i><h2 class="mt-3">{{ route.meta.title }}</h2><p>This module is planned for the new Laravel/Vue application. No data or actions are connected here yet.</p><RouterLink to="/dashboard" class="btn-outline-warm d-inline-block mt-2">Back to Dashboard</RouterLink></div></section></template>
