<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    value?: string | number | null
    icon?: string
    description?: string
    currency?: string
    loading?: boolean
  }>(),
  { icon: 'bi-bar-chart', loading: false },
)
</script>

<template>
  <article class="card-shell status-card" :aria-busy="loading">
    <div class="status-card__heading">
      <h3>{{ label }}</h3>
      <span class="status-card__icon" aria-hidden="true"><i class="bi" :class="icon" /></span>
    </div>
    <strong class="status-card__value"
      >{{ loading ? '…' : (value ?? '—')
      }}<small v-if="currency && !loading && value != null"> {{ currency }}</small></strong
    >
    <p class="status-card__description">
      {{ loading ? 'Loading summary…' : value == null ? 'Data unavailable' : description
      }}<slot v-if="!loading && value != null" />
    </p>
  </article>
</template>

<style scoped>
.status-card {
  height: 100%;
  min-height: 140px;
  padding: 18px;
  border-radius: 14px;
  box-shadow: 0 1px 2px var(--gss-shadow-tint-subtle);
}
.status-card__heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}
.status-card__heading h3 {
  margin: 0;
  color: var(--muted);
  font-size: 0.8rem;
  font-weight: 600;
  line-height: 1.4;
}
.status-card__icon {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  flex: 0 0 36px;
  border-radius: 9px;
  color: var(--cocoa);
  background: var(--gss-palette-fff3cd);
}
.status-card__value {
  display: block;
  color: var(--ink);
  font-size: 1.85rem;
  line-height: 1.15;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}
.status-card__value small {
  font-size: 0.8rem;
  font-weight: 400;
}
.status-card__description {
  margin: 10px 0 0;
  color: var(--muted);
  font-size: 0.8125rem;
  line-height: 1.45;
  font-weight: 400;
  overflow-wrap: anywhere;
}
</style>
