<script setup lang="ts">
import { usePendingAction } from '@/components/common/usePendingAction'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { insuranceApi } from '../api'
import type { InsuranceClaim, InsurancePolicy } from '../types'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import InsurancePolicyForm from '../components/InsurancePolicyForm.vue'
import { toast } from '@/services/toast'
import RecordAction from '@/components/common/RecordAction.vue'

const route = useRoute()
const policy = ref<InsurancePolicy | null>(null)
const loading = ref(true)
const error = ref('')
const showRenewal = ref(false)
const uploading = ref(false)
const file = ref<File | null>(null)
const id = computed(() => Number(route.params.id))

const { pending, run } = usePendingAction()
let request = 0
async function load() {
  const version = ++request
  loading.value = true
  error.value = ''
  try {
    const result = await insuranceApi.policy(id.value)
    if (version === request) policy.value = result
  } catch (e) {
    if (version === request) {
      error.value = e instanceof Error ? e.message : 'Unable to load policy.'
      toast.error(error.value)
    }
  } finally {
    if (version === request) loading.value = false
  }
}
async function transition(action: 'cancel' | 'archive') {
  if (!policy.value || !window.confirm(`Are you sure you want to ${action} this policy?`)) return
  try {
    await insuranceApi.transition(id.value, action)
    await load()
    toast.success(`Policy ${action}d.`)
  } catch (e: any) {
    error.value = e?.response?.data?.message || `Unable to ${action} policy.`
    toast.error(error.value)
  }
}
async function upload() {
  if (!file.value || uploading.value) return
  uploading.value = true
  error.value = ''
  const body = new FormData()
  body.append('document', file.value)
  try {
    await api.post(`/api/insurance/policies/${id.value}/documents`, body)
    toast.success('Document uploaded securely.')
    file.value = null
    await load()
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Document upload failed.'
    toast.error(error.value)
  } finally {
    uploading.value = false
  }
}
async function archiveDocument(documentId: number) {
  if (!window.confirm('Archive this document? It will remain available in the policy record.'))
    return
  try {
    await insuranceApi.archiveDocument(documentId)
    toast.success('Document archived.')
    await load()
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to archive document.'
    toast.error(error.value)
  }
}
async function downloadDocument(downloadUrl: string, filename: string) {
  try {
    const response = await api.get(downloadUrl, { responseType: 'blob' })
    const href = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = href
    link.download = filename
    link.hidden = true
    document.body.append(link)
    link.click()
    link.remove()
    window.setTimeout(() => URL.revokeObjectURL(href), 0)
    toast.success('Document downloaded.')
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to download document.'
    toast.error(error.value)
  }
}
async function changeClaimStatus(claim: InsuranceClaim, event: Event) {
  const select = event.target as HTMLSelectElement
  const status = select.value
  try {
    await insuranceApi.updateClaim(claim.id, {
      policy_id: claim.policy_id,
      incident_date: claim.incident_date,
      reference_number: claim.reference_number,
      amount: claim.amount,
      status,
      notes: claim.notes || '',
    })
    toast.success('Claim status updated and history recorded.')
    await load()
  } catch (e: any) {
    select.value = claim.status
    error.value = e?.response?.data?.message || 'Unable to update claim.'
    toast.error(error.value)
  }
}
onMounted(load)
</script>

<template>
  <main class="fleet-module page-content">
    <div class="page-heading">
      <div v-if="policy" class="d-flex flex-wrap gap-2">
        <RecordAction
          v-if="!policy.cancelled_at && !policy.archived_at && !policy.renewal"
          :to="`/insurance/${id}/edit`"
          action="edit"
          label="Edit policy"
        />
        <button
          v-if="!policy.cancelled_at && !policy.archived_at && !policy.renewal"
          class="btn btn-outline-warning"
          @click="showRenewal = !showRenewal"
        >
          Renew
        </button>
        <button
          v-if="!policy.cancelled_at && !policy.archived_at"
          class="btn btn-outline-secondary"
          :disabled="pending('cancel')"
          @click="run('cancel', () => transition('cancel'))"
        >
          <BaseLoading v-if="pending('cancel')" label="Cancelling…" /><template v-else
            >Cancel</template
          >
        </button>
        <button
          v-if="!policy.archived_at"
          class="btn btn-outline-danger"
          :disabled="pending('archive')"
          @click="run('archive', () => transition('archive'))"
        >
          <BaseLoading v-if="pending('archive')" label="Archiving…" /><template v-else
            >Archive</template
          >
        </button>
        <RecordAction action="back" label="Back to insurance" to="/insurance" />
      </div>
    </div>
    <PageSkeleton v-if="loading && !policy" kind="detail" label="Loading policy…" />
    <template v-else-if="policy">
      <div v-if="showRenewal" class="mb-4">
        <h2 class="h4">Renew policy</h2>
        <InsurancePolicyForm :renewal-from-id="id" />
      </div>
      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <section class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between">
              <h2 class="h5 mb-0">{{ policy.policy_number }}</h2>
              <BaseStatusBadge :status="policy.status" />
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-4">Vehicle</dt>
                <dd class="col-sm-8">
                  {{ policy.vehicle?.plate_no }} — {{ policy.vehicle?.model_name }}
                  <span class="text-muted">({{ policy.vehicle?.fleet }} fleet)</span>
                </dd>
                <dt class="col-sm-4">Provider</dt>
                <dd class="col-sm-8">{{ policy.provider?.name || '—' }}</dd>
                <dt class="col-sm-4">Insurance type</dt>
                <dd class="col-sm-8 text-capitalize">{{ policy.insurance_type }}</dd>
                <dt class="col-sm-4">Coverage dates</dt>
                <dd class="col-sm-8">{{ policy.start_date }} to {{ policy.expiration_date }}</dd>
                <dt class="col-sm-4">Premium</dt>
                <dd class="col-sm-8">{{ policy.currency }} {{ policy.premium_amount }}</dd>
                <dt class="col-sm-4">Coverage amount</dt>
                <dd class="col-sm-8">{{ policy.currency }} {{ policy.coverage_amount }}</dd>
                <dt class="col-sm-4">Coverage details</dt>
                <dd class="col-sm-8">{{ policy.coverage_details || '—' }}</dd>
                <dt class="col-sm-4">Notes</dt>
                <dd class="col-sm-8">{{ policy.notes || '—' }}</dd>
              </dl>
            </div>
          </section>
        </div>
        <div class="col-lg-4">
          <section class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h5 mb-0">Renewal history</h2></div>
            <div class="card-body">
              <p v-if="policy.renewal_parent_id" class="mb-2">
                Renewed from policy #{{ policy.renewal_parent_id }}
              </p>
              <p v-else>This is an original policy term.</p>
              <RouterLink v-if="policy.renewal" :to="`/insurance/${policy.renewal.id}`"
                >Next term: {{ policy.renewal.policy_number }}</RouterLink
              >
              <p v-else-if="policy.renewal_parent_id" class="text-muted mb-0">
                This is the latest term.
              </p>
            </div>
          </section>
        </div>
      </div>

      <section class="card shadow-sm mb-4">
        <div class="card-header bg-white"><h2 class="h5 mb-0">Private policy documents</h2></div>
        <div class="card-body">
          <form class="d-flex flex-wrap gap-2 align-items-center mb-3" @submit.prevent="upload">
            <input
              class="form-control"
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              aria-label="Choose policy document"
              @change="file = ($event.target as HTMLInputElement).files?.[0] || null"
            /><button class="btn btn-primary" :disabled="!file || uploading">
              <BaseLoading v-if="uploading" label="Uploading…" /><template v-else
                >Upload document</template
              ></button
            ><span class="small text-muted">PDF, JPEG, or PNG up to 10 MB</span>
          </form>
          <div v-if="policy.documents?.length" class="list-group list-group-flush">
            <div
              v-for="doc in policy.documents"
              :key="doc.id"
              class="list-group-item d-flex justify-content-between align-items-center"
            >
              <div>
                <div>{{ doc.original_name }}</div>
                <small class="text-muted"
                  >{{ Math.ceil(doc.size / 1024) }} KB<span v-if="doc.archived_at">
                    · Archived</span
                  ></small
                >
              </div>
              <div class="d-flex gap-2">
                <button
                  class="btn btn-sm btn-outline-secondary"
                  :disabled="pending(`download:${doc.id}`)"
                  @click="run(`download:${doc.id}`, () => downloadDocument(doc.download_url, doc.original_name))"
                >
                  <BaseLoading v-if="pending(`download:${doc.id}`)" label="Downloading…" /><template
                    v-else
                    >Download</template
                  ></button
                ><button
                  v-if="!doc.archived_at"
                  class="btn btn-sm btn-outline-danger"
                  :disabled="pending(`document:${doc.id}`)"
                  @click="run(`document:${doc.id}`, () => archiveDocument(doc.id))"
                >
                  <BaseLoading v-if="pending(`document:${doc.id}`)" label="Archiving…" /><template
                    v-else
                    >Archive</template
                  >
                </button>
              </div>
            </div>
          </div>
          <div v-else class="text-muted">No documents attached.</div>
        </div>
      </section>

      <section class="card shadow-sm">
        <div class="card-header bg-white"><h2 class="h5 mb-0">Claims and status history</h2></div>
        <div v-if="policy.claims?.length" class="table-responsive">
          <table class="table align-middle mb-0">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Incident date</th>
                <th>Amount</th>
                <th>Current status</th>
                <th>Update status</th>
                <th>Status history</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="claim in policy.claims" :key="claim.id">
                <td>{{ claim.reference_number }}</td>
                <td>{{ claim.incident_date }}</td>
                <td>{{ policy.currency }} {{ claim.amount }}</td>
                <td><BaseStatusBadge :status="claim.status" /></td>
                <td>
                  <select
                    class="form-select form-select-sm"
                    :value="claim.status"
                    aria-label="Update claim status"
                    :disabled="pending(`claim:${claim.id}`)"
                    @change="run(`claim:${claim.id}`, () => changeClaimStatus(claim, $event))"
                  >
                    <option
                      v-for="status in [
                        'submitted',
                        'under_review',
                        'approved',
                        'rejected',
                        'paid',
                        'closed',
                      ]"
                      :key="status"
                    >
                      {{ status }}
                    </option>
                  </select>
                  <BaseLoading
                    v-if="pending(`claim:${claim.id}`)"
                    label="Updating claim status…"
                  />
                </td>
                <td>
                  <ul class="small mb-0 ps-3">
                    <li v-for="(event, index) in claim.events || []" :key="index">
                      {{ event.created_at }}: {{ event.status }} · {{ policy.currency }}
                      {{ event.amount }}
                    </li>
                  </ul>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="p-3 text-muted">No claims have been recorded for this policy.</div>
      </section>
    </template>
    <div v-else-if="!loading" class="empty-state">Policy is unavailable.</div>
  </main>
</template>
