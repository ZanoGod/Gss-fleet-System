<script setup lang="ts">
import { inject } from 'vue'
import { financeContextKey, type FinancePageContext } from '../financeContext'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import BasePagination from '@/components/common/BasePagination.vue'
const finance = inject<FinancePageContext>(financeContextKey)!

function resetFilters() {
  Object.assign(finance.filter, {
    search: '',
    currency: '',
    date_from: '',
    date_to: '',
    status: '',
  })
  finance.loadAll()
}
</script>

<template>
  <section class="card-shell p-4 mb-4">
    <form class="row g-3" @submit.prevent="finance.loadAll()">
      <div class="col-12 col-md-3">
        <label class="form-label" for="finance-search">Search</label
        ><input
          id="finance-search"
          v-model="finance.filter.search"
          class="form-control"
          type="search"
          placeholder="Description, category, reference"
        />
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="entry-status-finance.filter">Status</label
        ><select
          id="entry-status-finance.filter"
          v-model="finance.filter.status"
          class="form-select"
        >
          <option value="">All</option>
          <option value="posted">Posted</option>
          <option value="draft">Draft</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="finance-currency">Currency</label
        ><select id="finance-currency" v-model="finance.filter.currency" class="form-select">
          <option value="">All</option>
          <option v-for="currency in finance.options.currencies" :key="currency">
            {{ currency }}
          </option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="finance-from">From</label
        ><input
          id="finance-from"
          v-model="finance.filter.date_from"
          class="form-control"
          type="date"
        />
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="finance-to">To</label
        ><input id="finance-to" v-model="finance.filter.date_to" class="form-control" type="date" />
      </div>
      <div class="col-12 col-md-1 d-flex align-items-end gap-2">
        <button class="btn btn-primary" :disabled="finance.refreshing">Filter</button>
      </div>
      <div class="col-12">
        <button
          type="button"
          class="btn btn-outline-secondary"
          :disabled="finance.refreshing"
          @click="resetFilters"
        >
          Reset filters
        </button>
      </div>
    </form>
  </section>
  <section class="card-shell">
    <div class="finance-section-header p-4">
      <h2 class="h5 mb-0">
        {{ finance.tab === 'income' ? 'Income records' : 'Expense records' }}
        <span class="text-muted">({{ finance.total }})</span>
      </h2>
      <div class="finance-toolbar-actions">
        <button class="btn btn-outline-primary" type="button" @click="finance.exportCsv">
          <i class="bi bi-download me-1" aria-hidden="true" /> Export CSV
        </button>
        <button
          v-if="finance.canManage"
          class="btn btn-primary"
          type="button"
          @click="finance.openEntry(finance.tab === 'income' ? 'income' : 'expense')"
        >
          <i
            :class="`bi ${finance.tab === 'income' ? 'bi-plus-circle' : 'bi-plus-circle'} me-1`"
            aria-hidden="true"
          />
          Add {{ finance.tab === 'income' ? 'income' : 'expense' }}
        </button>
      </div>
    </div>
    <BaseLoading
      v-if="finance.refreshing"
      class="d-block px-4 pb-2"
      label="Updating finance records…"
    /><PageSkeleton v-if="finance.loading" label="Loading finance records" :columns="7" />
    <div v-else-if="finance.visibleEntries.length" class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Category</th>
            <th>Customer / source</th>
            <th>Status</th>
            <th class="text-end">Amount</th>
            <th v-if="finance.canManage">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in finance.visibleEntries" :key="row.id">
            <td>{{ finance.formatDate(row.entry_date) }}</td>
            <td>
              {{ row.description
              }}<small v-if="row.reference" class="d-block text-muted">{{ row.reference }}</small>
            </td>
            <td>{{ row.category }}</td>
            <td>{{ finance.customerName(row.customer) }}</td>
            <td><BaseStatusBadge :status="row.status" /></td>
            <td class="text-end finance-number">{{ finance.money(row.amount, row.currency) }}</td>
            <td v-if="finance.canManage">
              <div class="d-flex gap-2">
                <button
                  class="btn btn-sm btn-outline-primary"
                  @click="finance.openEntry(row.type, row)"
                >
                  Edit</button
                ><button class="btn btn-sm btn-outline-danger" @click="finance.removeEntry(row)">
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else-if="!finance.loading" class="p-4 text-muted mb-0">No records match these filters.</p>
    <div class="p-3">
      <BasePagination
        :page="finance.page"
        :per-page="finance.perPage"
        :total="finance.total"
        :disabled="finance.refreshing"
        @update:page="finance.goToPage(finance.loadEntries, $event)"
      />
    </div>
  </section>
</template>
