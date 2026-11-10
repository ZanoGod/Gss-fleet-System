<script setup lang="ts">
import { computed, ref } from 'vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PageHeader from '@/components/common/PageHeader.vue'
import DeleteRecordDialog from '@/components/common/DeleteRecordDialog.vue'
import { displayValue as text, useModuleRecord } from '@/modules/shared/moduleRequests'
import { operatorConfig as config } from '../config'

const assignmentPath = '/daily-bookings'
const { record, initial, error, saving, pending, run, load, link, remove } = useModuleRecord(
  config,
  () => 'detail',
)

const assignments = computed(() => record.value?.assignments || [])
const confirmDialog = ref<InstanceType<typeof DeleteRecordDialog> | null>(null)

async function deleteRecord() {
  await remove()
  confirmDialog.value?.close()
}
const details = config.fields
</script>

<template>
  <section class="fleet-module page-content resource-workspace" aria-label="Module management">
    <div class="d-flex flex-wrap justify-content-end">
      <PageHeader :title="config.singular" :description="config.description" :icon="config.icon">
        <RouterLink :to="link(config.path)" class="btn btn-primary">{{
          `All ${config.title}`
        }}</RouterLink>
      </PageHeader>
    </div>
    <button
      v-if="error && !saving"
      class="btn-outline-warm mb-3"
      :disabled="pending('reload')"
      @click="run('reload', load)"
    >
      <BaseLoading v-if="pending('reload')" label="Reloading…" /><template v-else
        >Retry loading</template
      >
    </button>
    <PageSkeleton
      v-if="initial"
      kind="detail"
      :label="`Loading ${config.singular.toLowerCase()}`"
    />
    <section v-else-if="record" class="card-shell p-4">
      <h2 class="h5">Record details</h2>
      <dl class="row">
        <template v-for="field in details" :key="field.key"
          ><dt class="col-sm-4">{{ field.label }}</dt>
          <dd class="col-sm-8 preserve-lines">{{ text(record[field.key]) }}</dd></template
        >
      </dl>
      <h2 class="h5 mt-4">Assignment history</h2>
      <p v-if="!assignments.length" class="text-muted">No assignment history.</p>
      <ul class="list-group list-group-flush">
        <li v-for="a in assignments" :key="`${a.resource_type}-${a.id}`" class="list-group-item">
          <RouterLink
            :to="`${a.resource_type === 'monthly' ? '/monthly-contracts' : assignmentPath}/${a.id}`"
            >{{ a.name }}</RouterLink
          >
          · {{ a.start_date }} – {{ a.end_date }} · {{ a.status }}
          <div v-if="a.vehicle || a.driver" class="small text-muted">
            {{ a.vehicle }}{{ a.secondary_vehicle ? ` / ${a.secondary_vehicle}` : ''
            }}{{ a.driver ? ` · Driver: ${a.driver}` : '' }}
          </div>
        </li>
      </ul>
      <div class="d-flex gap-2 mt-4">
        <RouterLink :to="link(`${config.path}/${record.id}/edit`)" class="btn-gold"
          >Edit {{ config.singular }}</RouterLink
        ><button class="btn-outline-warm" @click="confirmDialog?.open()">Delete</button>
      </div>
    </section>
    <DeleteRecordDialog
      ref="confirmDialog"
      :singular="config.singular"
      :saving="saving"
      @confirm="deleteRecord"
    />
  </section>
</template>
