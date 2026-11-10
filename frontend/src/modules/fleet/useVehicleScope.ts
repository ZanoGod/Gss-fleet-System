import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { fleetConfig } from './config'

export function useVehicleScope() {
  const route = useRoute()
  const scope = computed(() => String(route.query.resource_type || 'daily'))
  const query = () => ({ resource_type: scope.value })
  const assignmentPath = computed(() =>
    scope.value === 'monthly' ? '/monthly-contracts' : '/daily-bookings',
  )
  const statuses = computed(() =>
    scope.value === 'monthly' ? [...fleetConfig.statuses, 'Inactive'] : fleetConfig.statuses,
  )
  return { scope, query, assignmentPath, statuses }
}
