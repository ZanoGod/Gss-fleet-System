<script setup lang="ts">
import { computed } from 'vue'
import BaseLoading from './BaseLoading.vue'

const props = defineProps<{
  page: number
  perPage: number
  total: number
  disabled?: boolean
  pendingPage?: (page: number) => boolean
}>()
const emit = defineEmits<{ 'update:page': [page: number] }>()
const pageCount = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)))
const currentPage = computed(() => Math.min(Math.max(props.page, 1), pageCount.value))
const range = computed(() => {
  const totalPages = pageCount.value
  const page = currentPage.value
  if (!props.total) return []
  if (totalPages <= 7) return Array.from({ length: totalPages }, (_, index) => index + 1)
  if (page <= 4) return [1, 2, 3, 4, 5, 'ellipsis' as const, totalPages]
  if (page >= totalPages - 3)
    return [1, 'ellipsis' as const, ...Array.from({ length: 5 }, (_, index) => totalPages - 4 + index)]
  return [1, 'ellipsis' as const, page - 1, page, page + 1, 'ellipsis' as const, totalPages]
})
const isLoading = (target: number) => props.pendingPage?.(target) ?? false
function goTo(target: number) {
  if (props.disabled || target === props.page || target < 1 || target > pageCount.value || isLoading(target)) return
  emit('update:page', target)
}
</script>

<template>
  <nav class="gss-pagination" aria-label="Pagination">
    <span class="pagination-summary" aria-live="polite">
      {{ total ? `${(currentPage - 1) * perPage + 1}–${Math.min(currentPage * perPage, total)} of ${total}` : 'No results' }}
    </span>
    <div class="pagination-controls">
      <button
        v-if="currentPage > 1"
        type="button"
        class="pagination-step btn-outline-warm"
        :disabled="disabled || isLoading(currentPage - 1)"
        :aria-busy="isLoading(currentPage - 1)"
        @click="goTo(currentPage - 1)"
      >
        <BaseLoading v-if="isLoading(currentPage - 1)" label="Loading previous page" :show-text="false" />
        <span v-else>Previous</span>
      </button>
      <template v-for="(item, index) in range" :key="`${item}-${index}`">
        <span v-if="item === 'ellipsis'" class="pagination-ellipsis" aria-hidden="true">…</span>
        <button
          v-else
          type="button"
          class="pagination-number btn-outline-warm"
          :class="{ 'is-current': item === currentPage }"
          :disabled="disabled || isLoading(item)"
          :aria-label="`Page ${item}`"
          :aria-current="item === currentPage ? 'page' : undefined"
          :aria-busy="isLoading(item)"
          @click="goTo(item)"
        >
          <BaseLoading v-if="isLoading(item)" :label="`Loading page ${item}`" :show-text="false" />
          <span v-else>{{ item }}</span>
        </button>
      </template>
      <button
        v-if="currentPage < pageCount"
        type="button"
        class="pagination-step btn-outline-warm"
        :disabled="disabled || isLoading(currentPage + 1)"
        :aria-busy="isLoading(currentPage + 1)"
        @click="goTo(currentPage + 1)"
      >
        <BaseLoading v-if="isLoading(currentPage + 1)" label="Loading next page" :show-text="false" />
        <span v-else>Next</span>
      </button>
    </div>
  </nav>
</template>

<style scoped>
.gss-pagination,
.pagination-controls {
  display: flex;
  align-items: center;
}
.gss-pagination {
  justify-content: space-between;
  gap: 1rem;
  color: var(--muted);
  font-size: 0.85rem;
}
.pagination-controls { gap: 0.35rem; }
.pagination-controls button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2.35rem;
  padding: 0.35rem 0.65rem;
  border-radius: 0.55rem;
  white-space: nowrap;
}
.pagination-controls .pagination-step { min-width: 5.8rem; }
.pagination-controls .pagination-number { min-width: 2.35rem; padding-inline: 0.45rem; }
.pagination-controls .pagination-number.is-current {
  color: var(--gss-button-primary-text, #fff);
  background: var(--gss-button-primary-bg, #54220f);
  border-color: var(--gss-button-primary-bg, #54220f);
}
.pagination-ellipsis { padding-inline: 0.1rem; color: var(--muted); }
.pagination-controls :deep(.gss-inline-loading) { gap: 0; }

@media (max-width: 575.98px) {
  .gss-pagination { align-items: flex-start; flex-direction: column; }
  .pagination-controls { width: 100%; justify-content: center; flex-wrap: wrap; }
  .pagination-controls .pagination-step { min-width: 4.75rem; }
}
</style>
