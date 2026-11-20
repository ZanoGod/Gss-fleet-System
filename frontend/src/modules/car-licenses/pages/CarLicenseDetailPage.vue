<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import { carLicenseApi } from '../api'
import type { CarLicense } from '../types'
import { toast } from '@/services/toast'

const route = useRoute(), router = useRouter(), license = ref<CarLicense | null>(null), loading = ref(true), error = ref(''), deleting = ref(false)
const date = (value: string | null) => value ? new Date(`${value}T00:00:00`).toLocaleDateString() : '—'
async function load() { try { license.value = await carLicenseApi.get(Number(route.params.id)) } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Unable to load license details.' } finally { loading.value = false } }
async function remove() {
  if (!license.value || !window.confirm(`Delete license record for ${license.value.vehicle?.plate_no || 'this vehicle'}?`)) return
  deleting.value = true
  try { await carLicenseApi.remove(license.value.id); toast.success('Car license deleted.'); await router.push('/car-licenses') }
  catch (cause) { toast.error(cause instanceof Error ? cause.message : 'Unable to delete this license.') }
  finally { deleting.value = false }
}
onMounted(load)
</script>

<template>
  <section class="fleet-module page-content resource-workspace car-license-detail" aria-label="Car license details">
    <PageHeader title="Car License Details" description="Registration details, documents, and renewal history." icon="bi-card-checklist"><RouterLink to="/car-licenses" class="btn btn-primary">All Car Licenses</RouterLink></PageHeader>
    <PageSkeleton v-if="loading" label="Loading car license" kind="detail" />
    <div v-else-if="error" class="alert alert-warning" role="alert">{{ error }}</div>
    <template v-else-if="license">
      <section class="card-shell p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><p class="text-muted mb-1">{{ license.vehicle?.fleet }} fleet vehicle</p><h2 class="h3 mb-1">{{ license.vehicle?.plate_no }} · {{ license.vehicle?.model_name }}</h2><div v-if="license.license_number" class="text-muted">License {{ license.license_number }}</div></div><BaseStatusBadge :status="license.status" /></div>
        <dl class="license-facts"><div><dt>License period</dt><dd>{{ license.license_period }} year{{ license.license_period > 1 ? 's' : '' }}</dd></div><div><dt>Issue date</dt><dd>{{ date(license.issue_date) }}</dd></div><div><dt>Expiry date</dt><dd>{{ date(license.expiry_date) }}</dd></div><div><dt>Expiry countdown</dt><dd :class="license.days_until_expiry <= 30 ? 'text-danger fw-semibold' : ''">{{ license.days_until_expiry < 0 ? `${Math.abs(license.days_until_expiry)} days overdue` : `${license.days_until_expiry} days remaining` }}</dd></div><div><dt>Last renewal date</dt><dd>{{ date(license.renewal_date) }}</dd></div><div><dt>Renewal fee</dt><dd>MMK {{ Number(license.renewal_fee).toLocaleString() }}</dd></div><div><dt>License office</dt><dd>{{ license.office_region }} · {{ license.office_branch }}</dd></div><div><dt>Remarks</dt><dd>{{ license.remarks || '—' }}</dd></div></dl>
        <div class="d-flex flex-wrap gap-2 mt-3"><RouterLink v-if="license.is_current" :to="`/car-licenses/${license.id}/edit`" class="btn btn-outline-secondary">Edit</RouterLink><RouterLink v-if="license.is_current" :to="`/car-licenses/${license.id}/renew`" class="btn btn-primary"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i> Renew License</RouterLink><button v-if="!license.renewal_history?.length || license.renewal_history.length === 1" class="btn btn-outline-danger" :disabled="deleting" @click="remove"><BaseLoading v-if="deleting" label="Deleting…" /><template v-else>Delete</template></button></div>
      </section>
      <section class="card-shell p-4 mb-4"><h2 class="h5">License documents</h2><p v-if="!license.documents?.length" class="text-muted mb-0">No documents attached.</p><ul v-else class="list-group list-group-flush"> <li v-for="doc in license.documents" :key="doc.id" class="list-group-item d-flex justify-content-between"><span><i class="bi bi-file-earmark-text me-2" aria-hidden="true"></i>{{ doc.original_name }}</span><a :href="doc.download_url">Download</a></li></ul></section>
      <section class="card-shell p-4"><h2 class="h5">Renewal history</h2><div v-if="license.renewal_history?.length" class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>License number</th><th>Issue date</th><th>Expiry date</th><th>Renewal recorded</th><th>Fee (MMK)</th><th>Status</th></tr></thead><tbody><tr v-for="item in license.renewal_history" :key="item.id"><td><RouterLink :to="`/car-licenses/${item.id}`">{{ item.license_number }}</RouterLink></td><td>{{ date(item.issue_date) }}</td><td>{{ date(item.expiry_date) }}</td><td>{{ date(item.renewal_date) }}</td><td>{{ Number(item.renewal_fee).toLocaleString() }}</td><td><BaseStatusBadge :status="item.status" /></td></tr></tbody></table></div><p v-else class="text-muted mb-0">No renewal records yet.</p></section>
    </template>
  </section>
</template>

<style scoped>
.license-facts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin:0}
.license-facts dt{color:var(--gss-text-secondary);font-size:.82rem;font-weight:600}
.license-facts dd{margin:.25rem 0 0;color:var(--gss-text-strong)}
@media(max-width:760px){.license-facts{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:420px){.license-facts{grid-template-columns:1fr}}
</style>
