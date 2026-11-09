<script setup lang="ts">
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { pendingPage } from '@/router'
import { RouterView } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
</script>
<template>
  <AppSidebar>
    <PageSkeleton
      v-if="pendingPage"
      :label="`Loading ${String(pendingPage.meta.title).toLowerCase()}`"
      :kind="
        /(create|edit|renew)$/.test(pendingPage.path)
          ? 'form'
          : pendingPage.params.id
            ? 'detail'
            : 'table'
      "
      :cards="pendingPage.path === '/dashboard' ? 5 : 0"
      heading
    />
    <div v-show="!pendingPage">
      <RouterView v-slot="{ Component, route }">
        <component :is="Component" :key="route.path" />
      </RouterView>
    </div>
  </AppSidebar>
</template>
