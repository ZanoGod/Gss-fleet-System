import axios from 'axios'
import api from '@/services/api'
import { getApiErrorMessage } from '@/services/apiErrors'
import type { Customer, CustomerInput, CustomerFilters, CustomerPage, CustomerSummary } from './types'

export const customerApi = {
  async list(filters: CustomerFilters, page: number): Promise<CustomerPage> {
    return (await api.get<CustomerPage>('/api/customers', { params: { ...filters, page } })).data
  },
  async summary(): Promise<CustomerSummary> {
    return (await api.get<{ data: CustomerSummary }>('/api/customers/summary')).data.data
  },
  async get(id: number, month?: string): Promise<Customer> {
    return (await api.get<{ data: Customer }>(`/api/customers/${id}`, { params: { month } })).data.data
  },
  async save(input: CustomerInput, id?: number): Promise<Customer> {
    return (await api.request<{ data: Customer }>({
      url: id ? `/api/customers/${id}` : '/api/customers',
      method: id ? 'patch' : 'post', data: input,
    })).data.data
  },
  async remove(id: number): Promise<void> { await api.delete(`/api/customers/${id}`) },
}

export function customerError(error: unknown): string {
  if (axios.isAxiosError<{ code?: string; message?: string }>(error)
    && error.response?.status === 409 && error.response.data.code === 'CUSTOMER_HAS_CONTRACTS') {
    return error.response.data.message || 'Customers linked to contracts cannot be deleted.'
  }
  return getApiErrorMessage(error, { existingSession: true })
}
export function customerFieldErrors(error: unknown): Record<string, string[]> {
  if (!axios.isAxiosError<{ errors?: Record<string, string[] | string> }>(error)
    || error.response?.status !== 422) return {}
  return Object.fromEntries(Object.entries(error.response.data.errors || {}).map(([key, value]) =>
    [key, Array.isArray(value) ? value : [value]]))
}
