<script setup lang="ts">
import { usePendingAction } from '@/components/common/usePendingAction'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import RecordAction from '@/components/common/RecordAction.vue'
import '@/components/common/moduleGlass.css'
import { navigateSegments } from '@/components/common/navigateSegments'
import StatusSummary from '@/components/common/StatusSummary.vue'
import { insuranceMetrics, insuranceHeader } from '../overview'
import { onMounted, ref } from 'vue'
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
    <header class="module-heading">
      <div>
        <h1>Insurance Management</h1>
        <p>{{ insuranceHeader.description }}</p>
      </div>
    </header>
    <StatusSummary
      :items="insuranceMetrics(store.dashboard)"
      :loading="initial"
      label="Insurance status summary"
    />
    <PageSkeleton v-if="initial" label="Loading insurance policies" :columns="7" />
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
            ><button class="btn module-filter-apply" :disabled="refreshing" type="submit">
              <BaseLoading v-if="refreshing" label="Searching…" /><template v-else
                ><i class="bi bi-funnel" aria-hidden="true"></i> Search</template
              >
            </button>
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
                <th>Premium</th>
                <th>Status</th>
                <th><span class="visually-hidden">Actions</span></th>
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
                <td>{{ p.currency }} {{ p.premium_amount }}</td>
                <td><BaseStatusBadge :status="p.status" /></td>
                <td>
                  <RecordAction action="view" icon-only :aria-label="`View policy ${p.policy_number}`" :to="`/insurance/${p.id}`" />
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
                <th />
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in claims" :key="c.id">
                <td>{{ c.reference_number }}</td>
                <td>{{ c.policy?.policy_number || c.policy_id }}</td>
                <td>{{ c.incident_date }}</td>
                <td>{{ c.policy?.currency }} {{ c.amount }}</td>
                <td><BaseStatusBadge :status="c.status" /></td>
                <td>
                  <RecordAction action="view" icon-only aria-label="View policy" :to="`/insurance/${c.policy_id}`" />
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
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in store.providers" :key="p.id">
                <td class="fw-semibold">{{ p.name }}</td>
                <td>{{ p.contact_name || p.email || '—' }}</td>
                <td>{{ p.phone || '—' }}</td>
                <td>{{ p.policies_count ?? '—' }}</td>
                <td>
                  <div class="record-actions">
                    <RecordAction
                      action="edit"
                      icon-only
                      :aria-label="`View or edit provider ${p.name}`"
                      :disabled="pending(`edit:${p.id}`)"
                      :loading="pending(`edit:${p.id}`)"
                      @click="run(`edit:${p.id}`, () => editProvider(p.id))"
                    /><RecordAction
                      action="delete"
                      icon-only
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
              {{ p.policy_number }} Â· {{ p.vehicle?.plate_no }}
            </option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="claim-ref">Reference</label
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
