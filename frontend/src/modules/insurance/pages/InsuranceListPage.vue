<script setup lang="ts">
import { usePendingAction } from '@/components/common/usePendingAction'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import RecordAction from '@/components/common/RecordAction.vue'
import '@/components/common/moduleGlass.css'
import { navigateSegments } from '@/components/common/navigateSegments'
import StatusSummary from '@/components/common/StatusSummary.vue'
import { insuranceMetrics } from '../overview'
import { computed, onMounted, ref } from 'vue'
import { useInsuranceStore } from '../stores/insurance'
import { insuranceApi } from '../api'
import type { InsuranceClaim } from '../types'
import BaseModal from '@/components/common/BaseModal.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import { toast } from '@/services/toast'

const store = useInsuranceStore(),
  search = ref(''),
  status = ref(''),
  activeTab = ref('policies'),
  claims = ref<InsuranceClaim[]>([]),
  initial = ref(true),
  claimsReady = ref(false)
const expiryAlerts = computed(() => [
  ...(store.dashboard?.expiring_policies ?? []),
  ...(store.dashboard?.expired_policies_alerts ?? []),
])
function expiryLabel(days: number, alert = false) {
  if (days === 0) return 'Expires today'
  const count = Math.abs(days)
  const duration = `${count} ${count === 1 ? 'day' : 'days'}`
  if (days < 0) return alert ? `Expired ${duration} ago` : `${duration} overdue`
  return `${duration} left`
}
let request = 0
const loadError = ref('')
const refreshing = ref(false)
const appliedFilters = ref(false)
const error = ref(''),
  providerModal = ref(false),
  providerId = ref<number | null>(null),
  providerForm = ref({ name: '', contact_name: '', email: '', phone: '', address: '', notes: '' }),
  claimModal = ref(false)
const claim = ref({
  policy_id: 0,
  incident_date: '',
  reference_number: '',
  amount: '',
  status: 'submitted',
  notes: '',
})
function providerErrorMessage(error: unknown, fallback: string) {
  if (typeof error !== 'object' || error === null) return fallback
  const response = Reflect.get(error, 'response')
  if (typeof response !== 'object' || response === null) return fallback
  const data = Reflect.get(response, 'data')
  if (typeof data !== 'object' || data === null) return fallback
  const message = Reflect.get(data, 'message')
  return typeof message === 'string' ? message : fallback
}
const { pending, run } = usePendingAction()
async function load() {
  const version = ++request
  loadError.value = ''
  refreshing.value = true
  claimsReady.value = false
  const filtered = Boolean(search.value.trim() || status.value)
  try {
    await store.load({ search: search.value || undefined, status: status.value || undefined })
    if (version !== request) return
    appliedFilters.value = filtered
    initial.value = false
    const result = await insuranceApi.claims({ per_page: 10 })
    if (version === request) claims.value = result
  } catch (e) {
    if (version === request) {
      loadError.value = e instanceof Error ? e.message : 'Unable to load insurance data.'
    }
  } finally {
    if (version === request) {
      initial.value = false
      claimsReady.value = true
      refreshing.value = false
    }
  }
}
async function clearFilters() {
  search.value = ''
  status.value = ''
  await load()
}
function newProvider() {
  providerId.value = null
  providerForm.value = { name: '', contact_name: '', email: '', phone: '', address: '', notes: '' }
  providerModal.value = true
}
async function editProvider(id: number) {
  try {
    const p = await insuranceApi.provider(id)
    providerId.value = p.id
    providerForm.value = {
      name: p.name,
      contact_name: p.contact_name || '',
      email: p.email || '',
      phone: p.phone || '',
      address: p.address || '',
      notes: p.notes || '',
    }
    providerModal.value = true
  } catch (e: unknown) {
    error.value = providerErrorMessage(e, 'Unable to load provider.')
    toast.error(error.value)
  }
}
async function saveProvider() {
  try {
    if (providerId.value) await insuranceApi.updateProvider(providerId.value, providerForm.value)
    else await insuranceApi.createProvider(providerForm.value)
    providerModal.value = false
    toast.success(providerId.value ? 'Provider updated.' : 'Provider created.')
    await load()
  } catch (e: unknown) {
    error.value = providerErrorMessage(e, 'Unable to save provider.')
    toast.error(error.value)
  }
}
async function deleteProvider(id: number, name: string, count: number) {
  if (count > 0) {
    error.value = 'This provider has policies and cannot be deleted.'
    toast.error(error.value)
    return
  }
  if (!window.confirm(`Delete provider ${name}?`)) return
  try {
    await insuranceApi.deleteProvider(id)
    toast.success('Provider deleted.')
    await load()
  } catch (e: unknown) {
    error.value = providerErrorMessage(e, 'Unable to delete provider.')
    toast.error(error.value)
  }
}
function newClaim() {
  claim.value.policy_id = store.policies[0]?.id || 0
  claimModal.value = true
}
async function createClaim() {
  try {
    await insuranceApi.createClaim(claim.value)
    claimModal.value = false
    toast.success('Claim created and its status recorded.')
    await load()
  } catch (e: unknown) {
    error.value = providerErrorMessage(e, 'Unable to create claim.')
    toast.error(error.value)
  }
}
onMounted(load)
</script>
<template>
  <main class="fleet-module page-content glass-module">
    <!-- <header>
      <div>
        <div class="eyebrow">Web-Based Fleet Management System</div>
        <h1>{{ insuranceHeader.title }}</h1>
        <p>{{ insuranceHeader.description }}</p>
      </div>
    </header> -->
    <StatusSummary
      :items="insuranceMetrics(store.dashboard)"
      :loading="initial"
      label="Insurance status summary"
    />
    <PageSkeleton v-if="initial" label="Loading insurance policies" :columns="8" />
    <template v-else>
      <div
        class="glass-segments glass-segments--three"
        role="group"
        aria-label="Insurance sections"
        :data-active-index="activeTab === 'claims' ? 1 : activeTab === 'providers' ? 2 : 0"
        @keydown="navigateSegments"
      >
        <button
          v-for="tab in [
            { id: 'policies', label: 'Policies' },
            { id: 'claims', label: 'Claims' },
            { id: 'providers', label: 'Providers' },
          ]"
          :key="tab.id"
          type="button"
          :aria-pressed="activeTab === tab.id"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>
      <section
        v-if="activeTab === 'policies' && expiryAlerts.length"
        class="policy-alerts"
        aria-label="Insurance expiry alerts"
      >
        <h2><i class="bi bi-bell" aria-hidden="true"></i> Expiry alerts</h2>
        <RouterLink
          v-for="policy in expiryAlerts"
          :key="policy.id"
          :to="`/insurance/${policy.id}`"
          class="policy-alert"
          :class="{ 'is-expired': policy.days_until_expiry < 0 }"
        >
          <span>{{ [policy.vehicle?.plate_no, policy.policy_number].filter(Boolean).join(' · ') }}</span>
          <strong>{{ expiryLabel(policy.days_until_expiry, true) }}</strong>
        </RouterLink>
      </section>
      <div v-if="loadError" class="module-feedback" role="alert">
        <h2>Unable to load insurance records</h2>
        <p>{{ loadError }}</p>
        <button class="btn btn-outline-primary" :disabled="refreshing" @click="run('retry', load)">
          <BaseLoading v-if="refreshing" label="Retrying…" /><template v-else>Retry</template>
        </button>
      </div>
      <section v-if="activeTab === 'policies'" class="card shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between">
          <div class="module-section-heading">
            <h2 class="h5 mb-0">Policies</h2>
            <RouterLink to="/insurance/create" class="btn btn-primary"
              ><i class="bi bi-shield-check me-1" aria-hidden="true" /> Add policy</RouterLink
            >
          </div>
          <form class="policy-toolbar" @submit.prevent="!refreshing && run('search', load)">
            <input
              v-model="search"
              class="form-control"
              placeholder="Search policy or provider"
              aria-label="Search policies"
              type="search"
            /><select
              v-model="status"
              class="form-select"
              aria-label="Filter by status"
              :disabled="refreshing"
              @change="run('search', load)"
            >
              <option value="">All statuses</option>
              <option
                v-for="s in ['active', 'expiring', 'expired', 'scheduled', 'cancelled', 'archived']"
                :key="s"
                :value="s"
              >
                {{ s }}
              </option></select
            ><div class="module-filter-actions">
              <button class="btn module-filter-apply" :disabled="refreshing" type="submit">
              <BaseLoading v-if="refreshing" label="Searching…" /><template v-else
                ><i class="bi bi-funnel" aria-hidden="true"></i> Filter</template
              >
              </button>
              <span class="module-filter-divider" aria-hidden="true"></span>
              <button class="btn module-filter-reset" :disabled="refreshing" type="button" @click="run('reset', clearFilters)">
                <BaseLoading v-if="pending('reset')" label="Resetting…" />
                <template v-else><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</template>
              </button>
            </div>
          </form>
        </div>
        <BaseLoading v-if="refreshing" class="d-block p-4" label="Loading policies…" />
        <div v-else-if="loadError" class="p-4 text-muted">
          Policy results are unavailable. Retry the request above.
        </div>
        <div v-else-if="store.policies.length" class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Policy</th>
                <th>Fleet vehicle</th>
                <th>Provider</th>
                <th>Coverage dates</th>
                <th>Countdown</th>
                <th>Premium</th>
                <th>Status</th>
                <th class="record-actions-cell"><span class="visually-hidden">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in store.policies" :key="p.id">
                <td>
                  <RouterLink :to="`/insurance/${p.id}`" class="fw-semibold">{{
                    p.policy_number
                  }}</RouterLink>
                  <div class="text-muted small text-capitalize">{{ p.insurance_type }}</div>
                </td>
                <td>
                  {{ p.vehicle?.plate_no || '—' }}
                  <div class="text-muted small text-capitalize">{{ p.vehicle?.fleet }} fleet</div>
                </td>
                <td>{{ p.provider?.name || '—' }}</td>
                <td>
                  {{ p.start_date }}
                  <div class="text-muted small">to {{ p.expiration_date }}</div>
                </td>
                <td
                  class="text-nowrap"
                  :class="{ 'text-danger fw-semibold': p.status === 'expiring' || p.status === 'expired' }"
                >
                  {{ p.status === 'cancelled' || p.status === 'archived' ? '—' : expiryLabel(p.days_until_expiry) }}
                </td>
                <td>{{ p.currency }} {{ Number(p.premium_amount).toLocaleString('en-US', { maximumFractionDigits: 2 }) }}</td>
                <td><BaseStatusBadge :status="p.status" /></td>
                <td class="record-actions-cell">
                  <RecordAction action="view" label="View" :to="`/insurance/${p.id}`" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-4 text-center text-muted">
          <template v-if="appliedFilters">
            <h3 class="h5">No matching policies</h3>
            <p>Try a different search or status.</p>
            <button
              class="btn module-filter-clear"
              :disabled="refreshing"
              @click="run('search', clearFilters)"
            >
              <i class="bi bi-x-lg" aria-hidden="true"></i> Clear filters
            </button>
          </template>
          <template v-else
            ><h3 class="h5">No policies yet</h3>
            <p class="mb-0">Use Add policy above to register your first policy.</p></template
          >
        </div>
      </section>
      <PageSkeleton
        v-else-if="activeTab === 'claims' && !claimsReady"
        label="Loading insurance claims"
        :columns="6"
      />
      <section v-else-if="activeTab === 'claims'" class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between">
          <h2 class="h5 mb-0">Recent claims</h2>
          <button class="btn btn-primary" :disabled="!store.policies.length" @click="newClaim">
            <i class="bi bi-clipboard2-check me-1" aria-hidden="true" /> New claim
          </button>
        </div>
        <div v-if="claims.length" class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Policy</th>
                <th>Incident</th>
                <th>Amount</th>
                <th>Status</th>
                <th class="record-actions-cell"><span class="visually-hidden">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in claims" :key="c.id">
                <td>{{ c.reference_number }}</td>
                <td>{{ c.policy?.policy_number || c.policy_id }}</td>
                <td>{{ c.incident_date }}</td>
                <td>{{ c.policy?.currency }} {{ c.amount }}</td>
                <td><BaseStatusBadge :status="c.status" /></td>
                <td class="record-actions-cell">
                  <RecordAction action="view" label="View" :to="`/insurance/${c.policy_id}`" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-4 text-center text-muted">No claims have been recorded.</div>
      </section>
      <section v-else class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between">
          <h2 class="h5 mb-0">Insurance providers</h2>
          <button class="btn btn-primary" @click="newProvider">
            <i class="bi bi-building me-1" aria-hidden="true" /> Add provider
          </button>
        </div>
        <div v-if="store.providers.length" class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Provider</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>Policies</th>
                <th class="record-actions-cell">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in store.providers" :key="p.id">
                <td class="fw-semibold">{{ p.name }}</td>
                <td>{{ p.contact_name || p.email || '—' }}</td>
                <td>{{ p.phone || '—' }}</td>
                <td>{{ p.policies_count ?? '—' }}</td>
                <td class="record-actions-cell">
                  <div class="record-actions">
                    <RecordAction
                      action="edit"
                      label="Edit"
                      :aria-label="`Edit provider ${p.name}`"
                      :disabled="pending(`edit:${p.id}`)"
                      :loading="pending(`edit:${p.id}`)"
                      @click="run(`edit:${p.id}`, () => editProvider(p.id))"
                    /><RecordAction
                      action="delete"
                      label="Delete"
                      :aria-label="`Delete provider ${p.name}`"
                      :disabled="pending(`delete:${p.id}`) || (p.policies_count || 0) > 0"
                      :loading="pending(`delete:${p.id}`)"
                      @click="run(`delete:${p.id}`, () => deleteProvider(p.id, p.name, p.policies_count || 0))"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-4 text-center text-muted">No providers yet.</div>
      </section>
    </template>
    <BaseModal
      :open="providerModal"
      :title="providerId ? 'Insurance provider details' : 'Add insurance provider'"
      :busy="pending('provider')"
      @close="providerModal = false"
      ><div class="row g-3">
        <div class="col-12">
          <label class="form-label" for="provider-name">Provider name</label
          ><input
            id="provider-name"
            v-model="providerForm.name"
            class="form-control"
            maxlength="255"
            required
          />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="provider-contact">Contact name</label
          ><input id="provider-contact" v-model="providerForm.contact_name" class="form-control" />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="provider-phone">Phone</label
          ><input id="provider-phone" v-model="providerForm.phone" class="form-control" />
        </div>
        <div class="col-12">
          <label class="form-label" for="provider-email">Email</label
          ><input
            id="provider-email"
            v-model="providerForm.email"
            class="form-control"
            type="email"
          />
        </div>
        <div class="col-12">
          <label class="form-label" for="provider-address">Address</label
          ><textarea
            id="provider-address"
            v-model="providerForm.address"
            class="form-control"
            rows="2"
          />
        </div>
        <div class="col-12">
          <label class="form-label" for="provider-notes">Notes</label
          ><textarea
            id="provider-notes"
            v-model="providerForm.notes"
            class="form-control"
            rows="2"
          />
        </div>
      </div>
      <template #actions
        ><button
          class="btn btn-outline-secondary"
          :disabled="pending('provider')"
          @click="providerModal = false"
        >
          Cancel</button
        ><button
          class="btn btn-primary"
          :disabled="pending('provider') || !providerForm.name.trim()"
          @click="run('provider', saveProvider)"
        >
          <BaseLoading v-if="pending('provider')" label="Saving provider…" /><template v-else
            >Save provider</template
          >
        </button></template
      ></BaseModal
    >
    <BaseModal
      :open="claimModal"
      title="Create insurance claim"
      :busy="pending('claim')"
      @close="claimModal = false"
      ><div class="row g-3">
        <div class="col-12">
          <label class="form-label" for="claim-policy">Policy</label
          ><select id="claim-policy" v-model.number="claim.policy_id" class="form-select">
            <option v-for="p in store.policies" :key="p.id" :value="p.id">
              {{ p.vehicle?.car_type || 'N/A' }} &middot; {{ p.vehicle?.plate_no || 'N/A' }}
            </option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="claim-ref">Driver Name (Reference)</label
          ><input id="claim-ref" v-model="claim.reference_number" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="claim-date">Incident date</label
          ><input
            id="claim-date"
            v-model="claim.incident_date"
            class="form-control"
            type="date"
            required
          />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="claim-amount">Amount</label
          ><input
            id="claim-amount"
            v-model="claim.amount"
            class="form-control"
            inputmode="decimal"
            required
          />
        </div>
        <div class="col-md-6">
          <label class="form-label" for="claim-status">Status</label
          ><select id="claim-status" v-model="claim.status" class="form-select">
            <option
              v-for="s in ['submitted', 'under_review', 'approved', 'rejected', 'paid', 'closed']"
              :key="s"
            >
              {{ s }}
            </option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label" for="claim-notes">Notes</label
          ><textarea id="claim-notes" v-model="claim.notes" class="form-control" />
        </div>
      </div>
      <template #actions
        ><button
          class="btn btn-outline-secondary"
          :disabled="pending('claim')"
          @click="claimModal = false"
        >
          Cancel</button
        ><button
          class="btn btn-primary"
          :disabled="pending('claim')"
          @click="run('claim', createClaim)"
        >
          <BaseLoading v-if="pending('claim')" label="Saving claim…" /><template v-else
            >Save claim</template
          >
        </button></template
      ></BaseModal
    >
  </main>
</template>

<style scoped>
.policy-alerts {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  margin-bottom: 1.25rem;
  padding: 1rem 1.1rem;
  border: 1px solid #edc76b;
  border-radius: 14px;
  background: color-mix(in srgb, #fff7df 78%, var(--gss-surface-card));
}
.policy-alerts h2 {
  width: 100%;
  margin: 0 0 .25rem;
  font-size: .95rem;
  color: var(--gss-text-strong);
}
.policy-alert {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: .25rem 1rem;
  min-width: min(100%, 260px);
  padding: .55rem .75rem;
  border-radius: 9px;
  color: #352d20;
  background: #fffdf6;
  font-size: .8rem;
  text-decoration: none;
}
.policy-alert:hover { text-decoration: underline; }
.policy-alert strong { color: #805709; }
.policy-alert.is-expired { background: #fff0ef; }
.policy-alert.is-expired strong { color: #a52834; }
@media (max-width: 640px) {
  .policy-alert { width: 100%; overflow-wrap: anywhere; }
}
</style>
