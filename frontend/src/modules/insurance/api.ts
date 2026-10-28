import api from '@/services/api'
import type { AxiosResponse } from 'axios'
import type { InsuranceDashboard, InsurancePolicy, InsurancePolicyInput, InsuranceProvider, InsuranceVehicle, InsuranceClaim } from './types'

const data = <T>(value: AxiosResponse<{ data: T }>): T => value.data.data
export const insuranceApi = {
  async dashboard() { return data(await api.get<{ data: InsuranceDashboard }>('/api/insurance/dashboard')) },
  async policies(params: Record<string, string | number | undefined> = {}) { return data(await api.get<{ data: InsurancePolicy[] }>('/api/insurance/policies', { params })) },
  async policy(id: number) { return data(await api.get<{ data: InsurancePolicy }>(`/api/insurance/policies/${id}`)) },
  async vehicles() { return data(await api.get<{ data: InsuranceVehicle[] }>('/api/insurance/vehicles')) },
  async providers() { return data(await api.get<{ data: InsuranceProvider[] }>('/api/insurance/providers', { params: { per_page: 100 } })) },
  async savePolicy(input: InsurancePolicyInput, id?: number) { return data(await api.request<{ data: InsurancePolicy }>({ method: id ? 'put' : 'post', url: id ? `/api/insurance/policies/${id}` : '/api/insurance/policies', data: input })) },
  async renewPolicy(id: number, input: InsurancePolicyInput) { return data(await api.post<{ data: InsurancePolicy }>(`/api/insurance/policies/${id}/renew`, input)) },
  async transition(id: number, action: 'cancel' | 'archive') { return data(await api.post<{ data: InsurancePolicy }>(`/api/insurance/policies/${id}/${action}`)) },
  async claims(params: Record<string, string | number | undefined> = {}) { return data(await api.get<{ data: InsuranceClaim[] }>('/api/insurance/claims', { params })) },
  async createProvider(input: Partial<InsuranceProvider>) { return data(await api.post('/api/insurance/providers', input)) },
  async provider(id: number) { return data(await api.get<{ data: InsuranceProvider }>(`/api/insurance/providers/${id}`)) },
  async updateProvider(id: number, input: Partial<InsuranceProvider>) { return data(await api.put(`/api/insurance/providers/${id}`, input)) },
  async deleteProvider(id: number) { await api.delete(`/api/insurance/providers/${id}`) },
  async createClaim(input: { policy_id: number; incident_date: string; reference_number: string; amount: string; status: string; notes: string }) { return data(await api.post('/api/insurance/claims', input)) },
  async updateClaim(id: number, input: { policy_id: number; incident_date: string; reference_number: string; amount: string; status: string; notes: string }) { return data(await api.put(`/api/insurance/claims/${id}`, input)) },
  async archiveDocument(id: number) { return data(await api.post(`/api/insurance/documents/${id}/archive`)) },
}
