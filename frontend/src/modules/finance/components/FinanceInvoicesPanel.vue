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
    status: '',
    currency: '',
    date_from: '',
    date_to: '',
  })
  finance.loadAll()
}

function closePayments() {
  finance.selectedInvoice = null
  finance.payments = []
}
</script>

<template>
  <section class="card-shell p-4 mb-4">
    <form class="row g-3" @submit.prevent="finance.loadAll()">
      <div class="col-12 col-md-4">
        <label class="form-label" for="invoice-search">Search invoices</label
        ><input
          id="invoice-search"
          v-model="finance.filter.search"
          class="form-control"
          type="search"
          placeholder="Invoice number or description"
        />
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="invoice-status">Status</label
        ><select id="invoice-status" v-model="finance.filter.status" class="form-select">
          <option value="">All</option>
          <option value="draft">Draft</option>
          <option value="sent">Sent</option>
          <option value="void">Void</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="invoice-currency">Currency</label
        ><select id="invoice-currency" v-model="finance.filter.currency" class="form-select">
          <option value="">All</option>
          <option v-for="currency in finance.options.currencies" :key="currency">
            {{ currency }}
          </option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="invoice-from">From</label
        ><input
          id="invoice-from"
          v-model="finance.filter.date_from"
          class="form-control"
          type="date"
        />
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label" for="invoice-to">To</label
        ><input id="invoice-to" v-model="finance.filter.date_to" class="form-control" type="date" />
      </div>
      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" :disabled="finance.refreshing">Filter</button
        ><button
          type="button"
          class="btn btn-outline-secondary"
          :disabled="finance.refreshing"
          @click="resetFilters"
        >
          Reset
        </button>
      </div>
    </form>
  </section>
  <section class="card-shell">
    <div class="finance-section-header p-4">
      <h2 class="h5 mb-0">
        Invoices <span class="text-muted">({{ finance.total }})</span>
      </h2>
      <div class="finance-toolbar-actions">
        <button class="btn btn-outline-primary" type="button" @click="finance.exportCsv">
          <i class="bi bi-download me-1" aria-hidden="true" /> Export CSV
        </button>
        <button
          v-if="finance.canManage"
          class="btn btn-primary"
          type="button"
          @click="finance.openInvoice()"
        >
          <i class="bi bi-receipt me-1" aria-hidden="true" /> Create invoice
        </button>
      </div>
    </div>
    <PageSkeleton v-if="finance.loading" label="Loading invoices" :columns="7" />
    <div v-else-if="finance.invoices.length" class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Invoice</th>
            <th>Customer</th>
            <th>Due</th>
            <th>Status</th>
            <th class="text-end">Total</th>
            <th class="text-end">Balance</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="row in finance.invoices" :key="row.id"
            ><tr>
              <td>
                <strong>{{ row.invoice_number }}</strong
                ><small class="d-block text-muted">{{ row.description }}</small>
              </td>
              <td>{{ finance.customerName(row.customer) }}</td>
              <td>{{ finance.formatDate(row.due_date) }}</td>
              <td><BaseStatusBadge :status="row.payment_status || row.status" /></td>
              <td class="text-end finance-number">{{ finance.money(row.total, row.currency) }}</td>
              <td class="text-end finance-number">
                {{
                  finance.money(
                    row.balance ?? Number(row.total) - Number(row.payments_sum_amount || 0),
                    row.currency,
                  )
                }}
              </td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <button
                    class="btn btn-sm btn-outline-secondary"
                    @click="finance.loadPayments(row)"
                  >
                    Payments</button
                  ><button
                    v-if="finance.canManage"
                    class="btn btn-sm btn-outline-success"
                    :disabled="row.payment_status === 'Paid' || row.status === 'void'"
                    @click="finance.openPayment(row)"
                  >
                    Record payment</button
                  ><button
                    v-if="finance.canManage"
                    class="btn btn-sm btn-outline-primary"
                    @click="finance.openInvoice(row)"
                  >
                    Edit</button
                  ><button
                    v-if="finance.canManage"
                    class="btn btn-sm btn-outline-danger"
                    @click="finance.removeInvoice(row)"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="finance.selectedInvoice?.id === row.id" class="finance-payment-row">
              <td :colspan="finance.canManage ? 7 : 6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong>Payments for {{ row.invoice_number }}</strong
                  ><button class="btn btn-sm btn-outline-secondary" @click="closePayments">
                    Close
                  </button>
                </div>
                <BaseLoading v-if="finance.paymentsLoading" label="Loading payments…" />
                <div v-else-if="finance.payments.length" class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="text-end">Amount</th>
                        <th v-if="finance.canManage">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="payment in finance.payments" :key="payment.id">
                        <td>{{ finance.formatDate(payment.payment_date) }}</td>
                        <td>{{ payment.payment_method }}</td>
                        <td>{{ payment.reference || '—' }}</td>
                        <td class="text-end finance-number">
                          {{ finance.money(payment.amount, row.currency) }}
                        </td>
                        <td v-if="finance.canManage">
                          <button
                            class="btn btn-sm btn-outline-primary me-2"
                            @click="finance.openPayment(row, payment)"
                          >
                            Edit</button
                          ><button
                            class="btn btn-sm btn-outline-danger"
                            @click="finance.removePayment(payment)"
                          >
                            Delete
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <p v-else class="text-muted mb-0">No payments recorded.</p>
              </td>
            </tr></template
          >
        </tbody>
      </table>
    </div>
    <p v-else-if="!finance.loading" class="p-4 text-muted mb-0">No invoices match these filters.</p>
    <div class="p-3">
      <BasePagination
        :page="finance.page"
        :per-page="finance.perPage"
        :total="finance.total"
        :disabled="finance.refreshing"
        @update:page="finance.goToPage(finance.loadInvoices, $event)"
      />
    </div>
  </section>
</template>
