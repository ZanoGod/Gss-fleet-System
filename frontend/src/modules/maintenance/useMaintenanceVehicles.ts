import { ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'
import type { ResourceRow } from '@/components/common/resourceTypes'
import { maintenanceConfig } from './config'

export function useMaintenanceVehicles() {
  const route = useRoute()
  const options = ref<{ value: string; label: string }[]>([])

  async function loadVehicles(isCurrent: () => boolean) {
    const response = await api.get(maintenanceConfig.optionsEndpoint)
    if (!isCurrent()) return
    options.value = response.data.data.vehicles.map((vehicle: ResourceRow) => ({
      value: `${vehicle.resource_type}:${vehicle.id}`,
      label: `${vehicle.plate_no} · ${vehicle.model_name} (${vehicle.resource_type})`,
    }))
  }

  async function initializeForm(
    form: Record<string, string | number>,
    record: ResourceRow | null,
    isCurrent: () => boolean,
  ) {
    form.vehicle_key = record
      ? record.car_id
        ? `daily:${record.car_id}`
        : `monthly:${record.monthly_car_id}`
      : String(route.query.vehicle || '')
    await loadVehicles(isCurrent)
  }

  return { options, loadVehicles, initializeForm }
}

export function serializeVehicle(
  payload: Record<string, string | number | null>,
  form: Record<string, string | number>,
) {
  const [type, id] = String(form.vehicle_key).split(':')
  payload.car_id = type === 'daily' ? Number(id) : null
  payload.monthly_car_id = type === 'monthly' ? Number(id) : null
  delete payload.vehicle_key
}

export function mapVehicleErrors(errors: Record<string, string[]>) {
  if (errors.car_id || errors.monthly_car_id)
    errors.vehicle_key = [...(errors.car_id || []), ...(errors.monthly_car_id || [])]
}
