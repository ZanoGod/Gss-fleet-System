<script setup lang="ts">
import { inject } from 'vue'
import { financeContextKey, type FinancePageContext } from '../financeContext'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
const finance = inject<FinancePageContext>(financeContextKey)!
</script>

<template>
  <section class="card-shell p-4 mb-4">
    <form class="row g-3 align-items-end" @submit.prevent="finance.loadAll()">
      <div class="col-6 col-md-3">
        <label class="form-label" for="report-from">From</label
        ><input
          id="report-from"
          v-model="finance.filter.date_from"
          class="form-control"
          type="date"
        />
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="report-to">To</label
        ><input id="report-to" v-model="finance.filter.date_to" class="form-control" type="date" />
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label" for="report-currency">Currency</label
        ><select id="report-currency" v-model="finance.filter.currency" class="form-select">
          <option value="">All currencies</option>
          <option v-for="currency in finance.options.currencies" :key="currency">
            {{ currency }}
          </option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <button class="btn btn-primary" :disabled="finance.refreshing">Run report</button
        ><button class="btn btn-outline-secondary ms-2" type="button" @click="finance.exportCsv">
          Export CSV
        </button>
      </div>
    </form>
  </section>
  <div class="row g-4">
    <div class="col-12 col-xl-6">
      <section class="card-shell h-100">
        <div class="p-4"><h2 class="h5 mb-0">Income and expenses by category</h2></div>
        <PageSkeleton v-if="finance.loading" label="Loading category report" :columns="4" />
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Type</th>
                <th>Category</th>
                <th>Currency</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in finance.report.by_category"
                :key="`${row.type}-${row.category}-${row.currency}`"
              >
                <td>{{ row.type === 'income' ? 'Income' : 'Expense' }}</td>
                <td>{{ row.category }}</td>
                <td>{{ row.currency }}</td>
                <td class="text-end finance-number">
                  {{ finance.money(row.total, row.currency) }}
                </td>
              </tr>
              <tr v-if="!finance.report.by_category.length">
                <td colspan="4" class="text-muted">No activity in this period.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
    <div class="col-12 col-xl-6">
      <section class="card-shell h-100">
        <div class="p-4"><h2 class="h5 mb-0">Performance by vehicle</h2></div>
        <PageSkeleton v-if="finance.loading" label="Loading vehicle report" :columns="5" />
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Vehicle</th>
                <th>Fleet</th>
                <th>Currency</th>
                <th class="text-end">Income</th>
                <th class="text-end">Expenses</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in finance.report.by_vehicle"
                :key="`${row.fleet}-${row.vehicle_id}-${row.currency}`"
              >
                <td>{{ row.vehicle || `Vehicle #${row.vehicle_id}` }}</td>
                <td>{{ row.fleet }}</td>
                <td>{{ row.currency }}</td>
                <td class="text-end finance-number">
                  {{ finance.money(row.income, row.currency) }}
                </td>
                <td class="text-end finance-number">
                  {{ finance.money(row.expenses, row.currency) }}
                </td>
              </tr>
              <tr v-if="!finance.report.by_vehicle.length">
                <td colspan="5" class="text-muted">No vehicle-linked activity in this period.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
  <section class="card-shell mt-4">
    <div class="p-4"><h2 class="h5 mb-0">Monthly trend</h2></div>
    <PageSkeleton v-if="finance.loading" label="Loading monthly trend" :columns="4" />
    <div v-else class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Month</th>
            <th>Type</th>
            <th>Currency</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in finance.report.by_month"
            :key="`${row.month}-${row.type}-${row.currency}`"
          >
            <td>{{ row.month }}</td>
            <td>{{ row.type === 'income' ? 'Income' : 'Expense' }}</td>
            <td>{{ row.currency }}</td>
            <td class="text-end finance-number">{{ finance.money(row.total, row.currency) }}</td>
          </tr>
          <tr v-if="!finance.report.by_month.length">
            <td colspan="4" class="text-muted">No monthly activity to show.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
