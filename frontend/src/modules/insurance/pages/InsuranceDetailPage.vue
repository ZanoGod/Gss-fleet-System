<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import { insuranceApi } from '../api'
import type { InsuranceClaim, InsurancePolicy } from '../types'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import InsurancePolicyForm from '../components/InsurancePolicyForm.vue'

const route = useRoute()
const policy = ref<InsurancePolicy | null>(null)
const loading = ref(true)
const error = ref('')
const notice = ref('')
const showRenewal = ref(false)
const uploading = ref(false)
const file = ref<File | null>(null)
const id = computed(() => Number(route.params.id))

async function load() {
  loading.value = true
  error.value = ''
  try { policy.value = await insuranceApi.policy(id.value) }
  catch (e) { error.value = e instanceof Error ? e.message : 'Unable to load policy.' }
  finally { loading.value = false }
}
async function transition(action: 'cancel' | 'archive') {
  if (!policy.value || !window.confirm(`Are you sure you want to ${action} this policy?`)) return
  try { policy.value = await insuranceApi.transition(id.value, action); notice.value = `Policy ${action}d.` }
  catch (e: any) { error.value = e?.response?.data?.message || `Unable to ${action} policy.` }
}
async function upload() {
  if (!file.value) return
  uploading.value = true; error.value = ''
  const body = new FormData(); body.append('document', file.value)
  try { await api.post(`/api/insurance/policies/${id.value}/documents`, body); notice.value = 'Document uploaded securely.'; file.value = null; await load() }
  catch (e: any) { error.value = e?.response?.data?.message || 'Document upload failed.' }
  finally { uploading.value = false }
}
async function archiveDocument(documentId: number) {
  if (!window.confirm('Archive this document? It will remain available in the policy record.')) return
  try { await insuranceApi.archiveDocument(documentId); notice.value = 'Document archived.'; await load() }
  catch (e: any) { error.value = e?.response?.data?.message || 'Unable to archive document.' }
}
async function changeClaimStatus(claim: InsuranceClaim, event: Event) {
  const status = (event.target as HTMLSelectElement).value
  try {
    await insuranceApi.updateClaim(claim.id, { policy_id: claim.policy_id, incident_date: claim.incident_date, reference_number: claim.reference_number, amount: claim.amount, status, notes: claim.notes || '' })
    notice.value = 'Claim status updated and history recorded.'; await load()
  } catch (e: any) { error.value = e?.response?.data?.message || 'Unable to update claim.' }
}
onMounted(load)
</script>

<template>
  <main class="fleet-module page-content">
    <div class="page-heading"><div v-if="policy" class="d-flex flex-wrap gap-2">
      <RouterLink v-if="!policy.cancelled_at && !policy.archived_at && !policy.renewal" :to="`/insurance/${id}/edit`" class="btn btn-outline-primary">Edit policy</RouterLink>
      <button v-if="!policy.cancelled_at && !policy.archived_at && !policy.renewal" class="btn btn-outline-warning" @click="showRenewal = !showRenewal">Renew</button>
      <button v-if="!policy.cancelled_at && !policy.archived_at" class="btn btn-outline-secondary" @click="transition('cancel')">Cancel</button>
      <button v-if="!policy.archived_at" class="btn btn-outline-danger" @click="transition('archive')">Archive</button>
      <RouterLink to="/insurance" class="btn btn-outline-secondary">Back to insurance</RouterLink>
    </div></div>
    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div><div v-if="notice" class="alert alert-success" role="status">{{ notice }}</div>
    <div v-if="loading" class="alert alert-light border">Loading policy…</div>
    <template v-else-if="policy">
      <div v-if="showRenewal" class="mb-4"><h2 class="h4">Renew policy</h2><InsurancePolicyForm :renewal-from-id="id" /></div>
      <div class="row g-3 mb-4"><div class="col-lg-8"><section class="card shadow-sm h-100"><div class="card-header bg-white d-flex justify-content-between"><h2 class="h5 mb-0">{{ policy.policy_number }}</h2><BaseStatusBadge :status="policy.status" /></div><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-4">Vehicle</dt><dd class="col-sm-8">{{ policy.vehicle?.plate_no }} — {{ policy.vehicle?.model_name }} <span class="text-muted">({{ policy.vehicle?.fleet }} fleet)</span></dd>
        <dt class="col-sm-4">Provider</dt><dd class="col-sm-8">{{ policy.provider?.name || '—' }}</dd><dt class="col-sm-4">Insurance type</dt><dd class="col-sm-8 text-capitalize">{{ policy.insurance_type }}</dd>
        <dt class="col-sm-4">Coverage dates</dt><dd class="col-sm-8">{{ policy.start_date }} to {{ policy.expiration_date }}</dd><dt class="col-sm-4">Premium</dt><dd class="col-sm-8">{{ policy.currency }} {{ policy.premium_amount }}</dd>
        <dt class="col-sm-4">Coverage amount</dt><dd class="col-sm-8">{{ policy.currency }} {{ policy.coverage_amount }}</dd><dt class="col-sm-4">Coverage details</dt><dd class="col-sm-8">{{ policy.coverage_details || '—' }}</dd><dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ policy.notes || '—' }}</dd>
      </dl></div></section></div><div class="col-lg-4"><section class="card shadow-sm h-100"><div class="card-header bg-white"><h2 class="h5 mb-0">Renewal history</h2></div><div class="card-body"><p v-if="policy.renewal_parent_id" class="mb-2">Renewed from policy #{{ policy.renewal_parent_id }}</p><p v-else>This is an original policy term.</p><RouterLink v-if="policy.renewal" :to="`/insurance/${policy.renewal.id}`">Next term: {{ policy.renewal.policy_number }}</RouterLink><p v-else-if="policy.renewal_parent_id" class="text-muted mb-0">This is the latest term.</p></div></section></div></div>

      <section class="card shadow-sm mb-4"><div class="card-header bg-white"><h2 class="h5 mb-0">Private policy documents</h2></div><div class="card-body"><form class="d-flex flex-wrap gap-2 align-items-center mb-3" @submit.prevent="upload"><input class="form-control" type="file" accept=".pdf,.jpg,.jpeg,.png" aria-label="Choose policy document" @change="file = ($event.target as HTMLInputElement).files?.[0] || null"><button class="btn btn-primary" :disabled="!file || uploading">{{ uploading ? 'Uploading…' : 'Upload document' }}</button><span class="small text-muted">PDF, JPEG, or PNG up to 10 MB</span></form>
        <div v-if="policy.documents?.length" class="list-group list-group-flush"><div v-for="doc in policy.documents" :key="doc.id" class="list-group-item d-flex justify-content-between align-items-center"><div><div>{{ doc.original_name }}</div><small class="text-muted">{{ Math.ceil(doc.size / 1024) }} KB<span v-if="doc.archived_at"> · Archived</span></small></div><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-secondary" :href="doc.download_url">Download</a><button v-if="!doc.archived_at" class="btn btn-sm btn-outline-danger" @click="archiveDocument(doc.id)">Archive</button></div></div></div><div v-else class="text-muted">No documents attached.</div>
      </div></section>

      <section class="card shadow-sm"><div class="card-header bg-white"><h2 class="h5 mb-0">Claims and status history</h2></div><div v-if="policy.claims?.length" class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Reference</th><th>Incident date</th><th>Amount</th><th>Current status</th><th>Update status</th><th>Status history</th></tr></thead><tbody>
        <tr v-for="claim in policy.claims" :key="claim.id"><td>{{ claim.reference_number }}</td><td>{{ claim.incident_date }}</td><td>{{ policy.currency }} {{ claim.amount }}</td><td><BaseStatusBadge :status="claim.status" /></td><td><select class="form-select form-select-sm" :value="claim.status" aria-label="Update claim status" @change="changeClaimStatus(claim, $event)"><option v-for="status in ['submitted','under_review','approved','rejected','paid','closed']" :key="status">{{ status }}</option></select></td><td><ul class="small mb-0 ps-3"><li v-for="(event, index) in claim.events || []" :key="index">{{ event.created_at }}: {{ event.status }} · {{ policy.currency }} {{ event.amount }}</li></ul></td></tr>
      </tbody></table></div><div v-else class="p-3 text-muted">No claims have been recorded for this policy.</div></section>
    </template>
    <div v-else-if="!loading" class="alert alert-warning">Policy is unavailable.</div>
  </main>
</template>
