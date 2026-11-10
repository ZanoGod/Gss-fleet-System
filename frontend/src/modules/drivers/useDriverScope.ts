import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { driverConfig } from './config'
import api from '@/services/api'
export function useDriverScope() {
  const route = useRoute()
  const scope = computed(() => String(route.query.resource_type || 'daily'))
  const query = () => ({ resource_type: scope.value })
  const assignmentPath = computed(() =>
    scope.value === 'monthly' ? '/monthly-contracts' : '/daily-bookings',
  )
  const driverStatuses = ref<string[]>([])
  const statuses = computed(() =>
    driverStatuses.value.length ? driverStatuses.value : driverConfig.statuses,
  )
  async function prepare(isCurrent: () => boolean) {
    const response = await api.get(driverConfig.optionsEndpoint, { params: query() })
    if (isCurrent()) driverStatuses.value = response.data.data.statuses
  }
  return { scope, query, assignmentPath, statuses, prepare }
}
