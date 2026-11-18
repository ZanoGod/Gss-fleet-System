<script setup lang="ts">
import { inject } from 'vue'
import { financeContextKey, type FinancePageContext } from '../financeContext'
import BaseModal from '@/components/common/BaseModal.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
const finance = inject<FinancePageContext>(financeContextKey)!
</script>

<template>
  <BaseModal
    :open="Boolean(finance.modal)"
    :title="
      finance.modal === 'entry'
        ? `${finance.editingEntry ? 'Edit' : 'Add'} ${finance.form.type === 'income' ? 'income' : 'expense'}`
        : finance.modal === 'invoice'
          ? `${finance.editingInvoice ? 'Edit' : 'Create'} invoice`
          : `${finance.editingPayment ? 'Edit' : 'Record'} payment`
    "
    :busy="finance.saving"
    @close="finance.closeModal"
  >
    <form id="finance-record-form" class="finance-form row g-3" @submit.prevent="finance.save">
      <div
        v-if="Object.keys(finance.errors).length"
        :ref="(element) => finance.setErrorSummary(element)"
        class="col-12 alert alert-danger py-2"
        role="alert"
        tabindex="-1"
        aria-labelledby="finance-error-title"
      >
        <strong id="finance-error-title">There is a problem</strong>
        <ul class="mb-0 mt-1">
          <li v-for="(messages, key) in finance.errors" :key="key">
            <a
              class="link-danger"
              :href="`#${finance.errorTarget(String(key))}`"
              @click.prevent="finance.focusErrorField(String(key))"
              >{{ finance.errorLabels[String(key)] || 'Finance record' }}: {{ messages[0] }}</a
            >
          </li>
        </ul>
      </div>
      <template v-if="finance.modal === 'entry'">
        <div class="col-12 col-md-6">
          <label class="form-label" for="entry-description">Description</label
          ><input
            id="entry-description"
            v-model="finance.form.description"
            class="form-control"
            maxlength="180"
            required
          /><small v-if="finance.fieldError('description')" class="text-danger">{{
            finance.fieldError('description')
          }}</small>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="entry-category">Category</label
          ><select id="entry-category" v-model="finance.form.category" class="form-select" required>
            <option
              v-for="category in finance.form.type === 'income'
                ? finance.options.income_categories
                : finance.options.expense_categories"
              :key="category"
            >
              {{ category }}
            </option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="entry-amount">Amount</label
          ><input
            id="entry-amount"
            v-model="finance.form.amount"
            class="form-control"
            type="number"
            min="0.01"
            step="0.01"
            required
          /><small v-if="finance.fieldError('amount')" class="text-danger">{{
            finance.fieldError('amount')
          }}</small>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="entry-currency">Currency</label
          ><select id="entry-currency" v-model="finance.form.currency" class="form-select">
            <option v-for="currency in finance.options.currencies" :key="currency">
              {{ currency }}
            </option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="entry-date">Date</label
          ><input
            id="entry-date"
            v-model="finance.form.entry_date"
            class="form-control"
            type="date"
            required
          />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="entry-status">Status</label
          ><select id="entry-status" v-model="finance.form.status" class="form-select">
            <option value="posted">Posted</option>
            <option value="draft">Draft</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="entry-method">Payment method</label
          ><select id="entry-method" v-model="finance.form.payment_method" class="form-select">
            <option value="">Not recorded</option>
            <option v-for="method in finance.options.payment_methods" :key="method">
              {{ method }}
            </option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="entry-customer">Customer</label
          ><select id="entry-customer" v-model="finance.form.customer_id" class="form-select">
            <option value="">None</option>
            <option
              v-for="customer in finance.options.customers"
              :key="customer.id"
              :value="customer.id"
            >
              {{ finance.customerName(customer) }}
            </option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="entry-booking">Daily booking</label
          ><select id="entry-booking" v-model="finance.form.daily_booking_id" class="form-select">
            <option value="">None</option>
            <option v-for="item in finance.options.bookings" :key="item.id" :value="item.id">
              #{{ item.id }} · {{ item.guest_company_name }} · {{ item.start_date }}
            </option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="entry-contract">Monthly contract</label
          ><select
            id="entry-contract"
            v-model="finance.form.monthly_contract_id"
            class="form-select"
            @change="finance.selectContract"
          >
            <option value="">None</option>
            <option v-for="item in finance.options.contracts" :key="item.id" :value="item.id">
              {{ item.contract_no }} · {{ item.customer_name }}
            </option>
          </select>
        </div>
        <div v-if="finance.form.type === 'expense'" class="col-12 col-md-6">
          <label class="form-label" for="entry-maintenance">Maintenance work order</label
          ><select
            id="entry-maintenance"
            v-model="finance.form.maintenance_id"
            class="form-select"
            @change="finance.selectMaintenance"
          >
            <option value="">None</option>
            <option v-for="item in finance.options.maintenance" :key="item.id" :value="item.id">
              {{ item.title }} · {{ item.currency }} {{ item.actual_cost }}
            </option>
          </select>
        </div>
        <div v-if="finance.form.type === 'expense'" class="col-12 col-md-6">
          <label class="form-label" for="entry-insurance">Insurance policy</label
          ><select
            id="entry-insurance"
            v-model="finance.form.insurance_policy_id"
            class="form-select"
            @change="finance.selectInsurance"
          >
            <option value="">None</option>
            <option v-for="item in finance.options.insurance" :key="item.id" :value="item.id">
              {{ item.policy_number }} · {{ item.currency }} {{ item.premium_amount }}
            </option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="entry-vehicle">Vehicle</label
          ><select
            id="entry-vehicle"
            v-model="finance.form.vehicle_id"
            class="form-select"
            @change="finance.setVehicleFleet"
          >
            <option value="">None</option>
            <option
              v-for="item in finance.options.vehicles"
              :key="`${item.fleet}-${item.id}`"
              :value="item.id"
            >
              {{ item.plate_no }} · {{ item.model_name }} ({{ item.fleet }})
            </option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="entry-reference">Reference</label
          ><input
            id="entry-reference"
            v-model="finance.form.reference"
            class="form-control"
            maxlength="100"
          />
        </div>
        <div class="col-12">
          <label class="form-label" for="entry-notes">Notes</label
          ><textarea
            id="entry-notes"
            v-model="finance.form.notes"
            class="form-control"
            rows="2"
            maxlength="5000"
          />
        </div>
        <div
          v-if="finance.form.type === 'income' && !finance.editingEntry?.invoice"
          class="col-12 form-check ms-2"
        >
          <input
            id="create-invoice"
            v-model="finance.form.create_invoice"
            class="form-check-input"
            type="checkbox"
            @change="!finance.form.create_invoice && (finance.form.invoice_due_date = '')"
          /><label class="form-check-label" for="create-invoice"
            >Create invoice for this income</label
          ><input
            v-if="finance.form.create_invoice"
            id="invoice-due-date"
            v-model="finance.form.invoice_due_date"
            class="form-control mt-2"
            type="date"
            :min="finance.form.entry_date"
            aria-label="Invoice due date"
          /><small v-if="finance.fieldError('invoice_due_date')" class="d-block text-danger">{{
            finance.fieldError('invoice_due_date')
          }}</small>
        </div>
      </template>
      <template v-else-if="finance.modal === 'invoice'">
        <div class="col-12">
          <label class="form-label" for="invoice-description-form">Description</label
          ><input
            id="invoice-description-form"
            v-model="finance.form.description"
            class="form-control"
            maxlength="180"
            required
          />
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="invoice-customer-form">Customer</label
          ><select
            id="invoice-customer-form"
            v-model="finance.form.customer_id"
            class="form-select"
          >
            <option value="">None</option>
            <option
              v-for="customer in finance.options.customers"
              :key="customer.id"
              :value="customer.id"
            >
              {{ finance.customerName(customer) }}
            </option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="invoice-booking-form">Daily booking</label
          ><select
            id="invoice-booking-form"
            v-model="finance.form.daily_booking_id"
            class="form-select"
          >
            <option value="">None</option>
            <option v-for="item in finance.options.bookings" :key="item.id" :value="item.id">
              #{{ item.id }} · {{ item.guest_company_name }}
            </option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="invoice-contract-form">Monthly contract</label
          ><select
            id="invoice-contract-form"
            v-model="finance.form.monthly_contract_id"
            class="form-select"
          >
            <option value="">None</option>
            <option v-for="item in finance.options.contracts" :key="item.id" :value="item.id">
              {{ item.contract_no }} · {{ item.customer_name }}
            </option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="invoice-issue">Issue date</label
          ><input
            id="invoice-issue"
            v-model="finance.form.issue_date"
            class="form-control"
            type="date"
            required
          />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="invoice-due">Due date</label
          ><input
            id="invoice-due"
            v-model="finance.form.due_date"
            class="form-control"
            type="date"
            :min="finance.form.issue_date"
            required
          />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="invoice-subtotal">Subtotal</label
          ><input
            id="invoice-subtotal"
            v-model="finance.form.subtotal"
            class="form-control"
            type="number"
            min="0.01"
            step="0.01"
            required
            @input="finance.updateInvoiceTotal"
          />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="invoice-tax">Tax</label
          ><input
            id="invoice-tax"
            v-model="finance.form.tax_amount"
            class="form-control"
            type="number"
            min="0"
            step="0.01"
            required
            @input="finance.updateInvoiceTotal"
          />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="invoice-total">Total</label
          ><input
            id="invoice-total"
            v-model="finance.form.total"
            class="form-control"
            type="number"
            min="0.01"
            step="0.01"
            required
          />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="invoice-currency-form">Currency</label
          ><select id="invoice-currency-form" v-model="finance.form.currency" class="form-select">
            <option v-for="currency in finance.options.currencies" :key="currency">
              {{ currency }}
            </option>
          </select>
        </div>
        <div v-if="finance.editingInvoice" class="col-12">
          <label class="form-label" for="invoice-status-form">Invoice status</label
          ><select id="invoice-status-form" v-model="finance.form.status" class="form-select">
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="void">Void</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label" for="invoice-notes">Notes</label
          ><textarea
            id="invoice-notes"
            v-model="finance.form.notes"
            class="form-control"
            rows="2"
            maxlength="5000"
          />
        </div>
      </template>
      <template v-else>
        <div class="col-12">
          <p class="text-muted mb-0">
            {{ finance.selectedInvoice?.invoice_number }} · balance
            {{
              finance.money(
                finance.selectedInvoice?.balance ||
                  Number(finance.selectedInvoice?.total) -
                    Number(finance.selectedInvoice?.payments_sum_amount || 0),
                finance.selectedInvoice?.currency,
              )
            }}
          </p>
        </div>
        <div class="col-6">
          <label class="form-label" for="payment-date">Payment date</label
          ><input
            id="payment-date"
            v-model="finance.form.payment_date"
            class="form-control"
            type="date"
            required
          />
        </div>
        <div class="col-6">
          <label class="form-label" for="payment-amount">Amount</label
          ><input
            id="payment-amount"
            v-model="finance.form.amount"
            class="form-control"
            type="number"
            min="0.01"
            step="0.01"
            required
          /><small v-if="finance.fieldError('amount')" class="text-danger">{{
            finance.fieldError('amount')
          }}</small>
        </div>
        <div class="col-6">
          <label class="form-label" for="payment-method">Method</label
          ><select
            id="payment-method"
            v-model="finance.form.payment_method"
            class="form-select"
            required
          >
            <option v-for="method in finance.options.payment_methods" :key="method">
              {{ method }}
            </option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label" for="payment-reference">Reference</label
          ><input
            id="payment-reference"
            v-model="finance.form.reference"
            class="form-control"
            maxlength="100"
          />
        </div>
        <div class="col-12">
          <label class="form-label" for="payment-notes">Notes</label
          ><textarea
            id="payment-notes"
            v-model="finance.form.notes"
            class="form-control"
            rows="2"
            maxlength="5000"
          />
        </div>
      </template>
    </form>
    <template #actions
      ><button
        class="btn btn-outline-secondary"
        :disabled="finance.saving"
        @click="finance.closeModal"
      >
        Cancel</button
      ><button
        class="btn btn-primary"
        type="submit"
        form="finance-record-form"
        :disabled="finance.saving"
      >
        <BaseLoading v-if="finance.saving" label="Saving…" /><template v-else>Save</template>
      </button></template
    >
  </BaseModal>
</template>
