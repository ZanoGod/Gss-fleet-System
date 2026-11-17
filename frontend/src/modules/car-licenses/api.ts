import api from '@/services/api'
import type { AxiosResponse } from 'axios'
import type { CarLicense, LicenseDashboard, LicenseVehicleOption } from './types'

const data = <T>(response: AxiosResponse<{ data: T }>): T => response.data.data
export const carLicenseApi = {
  async list(params: Record<string, string | number | undefined> = {}) {
    return api.get<{ data: CarLicense[]; meta: { total: number; current_page: number } }>(
      '/api/car-licenses',
      { params },
    )
  },
  async summary() {
    return data(await api.get<{ data: LicenseDashboard }>('/api/car-licenses/summary'))
  },
  async options() {
    return data(
      await api.get<{
        data: {
          vehicles: LicenseVehicleOption[]
          offices: Record<string, string[]>
          periods: number[]
        }
      }>('/api/car-licenses/options'),
    )
  },
  async get(id: number) {
    return data(await api.get<{ data: CarLicense }>(`/api/car-licenses/${id}`))
  },
  async save(input: Record<string, string | number | null>, id?: number) {
    return data(
      await api.request<{ data: CarLicense }>({
        method: id ? 'put' : 'post',
        url: id ? `/api/car-licenses/${id}` : '/api/car-licenses',
        data: input,
      }),
    )
  },
  async renew(id: number, input: Record<string, string | number | null>) {
    return data(await api.post<{ data: CarLicense }>(`/api/car-licenses/${id}/renew`, input))
  },
  async upload(id: number, file: File) {
    const form = new FormData()
    form.append('document', file)
    return data(await api.post(`/api/car-licenses/${id}/documents`, form))
  },
  async remove(id: number) {
    await api.delete(`/api/car-licenses/${id}`)
  },
}
