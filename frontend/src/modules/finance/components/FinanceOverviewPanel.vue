<script setup lang="ts">
import { inject } from 'vue'
import { financeContextKey, type FinancePageContext } from '../financeContext'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
const finance = inject<FinancePageContext>(financeContextKey)!
</script>

<template>
  <section class="card-shell p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div>
        <h2 class="h5 mb-1">This month</h2>
        <p class="text-muted mb-0">Posted income and expenses, grouped by currency.</p>
      </div>
      <div class="finance-toolbar-actions">
        <button class="btn btn-outline-primary" type="button" @click="finance.exportCsv">
          <i class="bi bi-download me-1" aria-hidden="true" /> Export CSV
        </button>
        <select
          v-model="finance.filter.currency"
          class="form-select finance-currency-filter"
          aria-label="Finance currency"
          @change="finance.loadAll()"
        >
          <option value="">All currencies</option>
          <option v-for="currency in finance.options.currencies" :key="currency">
            {{ currency }}
          </option>
        </select>
      </div>
    </div>
    <PageSkeleton v-if="finance.loading" label="Loading monthly finance" :columns="3" />
    <div v-else class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Currency</th>
            <th>Income</th>
            <th>Expenses</th>
            <th>Net income</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in finance.summary?.month_totals || []" :key="row.currency">
            <td>{{ row.currency }}</td>
            <td class="finance-number">{{ finance.money(row.income, row.currency) }}</td>
            <td class="finance-number">{{ finance.money(row.expenses, row.currency) }}</td>
            <td class="finance-number">
              {{ finance.money(Number(row.income) - Number(row.expenses), row.currency) }}
            </td>
          </tr>
          <tr v-if="!finance.summary?.month_totals?.length">
            <td colspan="4" class="text-muted">No posted activity this month.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
  <section class="card-shell">
    <div class="p-4"><h2 class="h5 mb-0">Recent activity</h2></div>
    <PageSkeleton v-if="finance.loading" label="Loading recent activity" :columns="5" />
    <div v-else-if="finance.summary?.recent_entries?.length" class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Description</th>
            <th>Category</th>
            <th class="text-end">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in finance.summary.recent_entries" :key="row.id">
            <td>{{ finance.formatDate(row.entry_date) }}</td>
            <td><BaseStatusBadge :status="row.type === 'income' ? 'Income' : 'Expense'" /></td>
            <td>{{ row.description }}</td>
            <td>{{ row.category }}</td>
            <td class="text-end finance-number">{{ finance.money(row.amount, row.currency) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="p-4 text-muted mb-0">
      No finance records yet. Add income or an expense to start tracking fleet performance.
    </p>
  </section>
</template>
