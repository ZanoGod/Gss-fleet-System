<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    kind?: 'table' | 'form' | 'detail'
    columns?: number
    rows?: number
    heading?: boolean
    cards?: number
  }>(),
  { kind: 'table', columns: 5, rows: 5, cards: 0 },
)
</script>

<template>
  <div class="gss-page-skeleton">
    <span class="visually-hidden" role="status">{{ label }}</span>
    <div aria-hidden="true">
      <div v-if="heading" class="d-flex justify-content-end mb-3">
        <span class="gss-placeholder" style="width: 8rem; height: 2.4rem" />
      </div>
      <div v-if="cards" class="gss-skeleton-cards mb-4">
        <div v-for="card in cards" :key="card" class="card-shell p-3">
          <span class="gss-placeholder w-75" />
          <span class="gss-placeholder w-25 mt-3" style="height: 2rem" />
        </div>
      </div>
      <div class="card-shell overflow-hidden">
        <div v-if="kind === 'table'" class="table-responsive">
          <table class="table data-table mb-0">
            <thead>
              <tr>
                <th v-for="column in columns" :key="column">
                  <span class="gss-placeholder w-75" />
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row">
                <td v-for="column in columns" :key="column">
                  <span class="gss-placeholder w-75" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="row g-3 p-4">
          <div v-for="row in rows" :key="row" :class="kind === 'form' ? 'col-sm-6' : 'col-12'">
            <span class="gss-placeholder w-25 mb-2" />
            <span
              class="gss-placeholder"
              :class="kind === 'form' ? 'gss-skeleton-field w-100' : 'w-50'"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
