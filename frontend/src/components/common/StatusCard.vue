<script setup lang="ts">
import type { StatusMetric } from './statusMetrics'
withDefaults(defineProps<Omit<StatusMetric, 'key'> & { loading?: boolean }>(), {
  loading: false,
  tone: 'info',
})
</script>

<template>
  <article class="card-shell status-card" :class="`status-card--${tone}`" :aria-busy="loading">
    <div class="status-card__heading">
      <h3>{{ label }}</h3>
      <span class="status-card__icon" aria-hidden="true"><i class="bi" :class="icon" /></span>
    </div>
    <strong class="status-card__value">{{ loading ? '…' : (value ?? '—') }}</strong>
  </article>
</template>

<style scoped>
.status-card {
  height: 100%;
  min-height: 140px;
  padding: 18px;
  border-radius: 14px;
  box-shadow: 0 1px 2px var(--gss-shadow-tint-subtle);
  --metric-text: var(--gss-status-info-text);
  --metric-background: var(--gss-status-info-background);
}
.status-card--success {
  --metric-text: var(--gss-status-success-text);
  --metric-background: var(--gss-status-success-background);
}
.status-card--warning {
  --metric-text: var(--gss-status-warning-text);
  --metric-background: var(--gss-status-warning-background);
}
.status-card--danger {
  --metric-text: var(--gss-status-danger-text);
  --metric-background: var(--gss-status-danger-background);
}
.status-card__heading {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}
.status-card h3 {
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
  color: var(--metric-text);
  background: var(--metric-background);
}
.status-card__value {
  display: block;
  color: var(--ink);
  font-size: 1.85rem;
  line-height: 1.15;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}
.status-card p {
  margin: 10px 0 0;
  color: var(--muted);
  font-size: 0.8125rem;
  line-height: 1.45;
  overflow-wrap: anywhere;
}
</style>
