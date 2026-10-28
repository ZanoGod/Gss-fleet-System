<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";

defineProps<{ mobileOpen: boolean }>();
const emit = defineEmits<{ toggleMenu: [] }>();
const route = useRoute();
const auth = useAuthStore();
const title = computed(() => String(route.meta.title || "Dashboard"));
const description = computed(() =>
  String(route.meta.description || "Manage fleet operations from one place."),
);
</script>
<template>
  <header class="shell-header">
    <div>
      <button
        type="button"
        class="btn-outline-warm mobile-menu mb-3"
        aria-controls="gss-sidebar"
        :aria-expanded="mobileOpen"
        @click="emit('toggleMenu')"
      >
        <i class="bi bi-list" aria-hidden="true"></i><span>Menu</span>
      </button>
      <div class="eyebrow">Web-Based Fleet Management System</div>
      <h1>{{ title }}</h1>
      <p>{{ description }}</p>

    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <span class="btn-outline-warm user-chip"
        ><i class="bi bi-person-circle me-2" aria-hidden="true"></i
        >{{ auth.user?.username || auth.user?.name || "Administrator" }}</span
      >
    </div>
  </header>
</template>
