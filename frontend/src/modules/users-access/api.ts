import api from '@/services/api'
import { getApiErrorMessage } from '@/services/apiErrors'
import type { AccessRole, PermissionCatalog, StaffUser, UserSummary } from './types'

export const usersAccessApi = {
  async list(filters: Record<string, string>, page = 1) {
    const response = await api.get<UserPagePayload>('/api/users', { params: { ...filters, page } })
    return normalizeUserPage(response.data, page)
  },
  async summary() {
    const response = await api.get<{ data: UserSummary }>('/api/users/summary')
    return response.data.data
  },
  async roles() {
    const response = await api.get<{ data: AccessRole[]; permissions: PermissionCatalog }>('/api/users/roles')
    return response.data
  },
  async createUser(data: Record<string, unknown>) { return (await api.post<{ data: StaffUser }>('/api/users', data)).data.data },
  async updateUser(id: number, data: Record<string, unknown>) { return (await api.put<{ data: StaffUser }>(`/api/users/${id}`, data)).data.data },
  async updateAccess(id: number, data: { role_id: number | null; is_active: boolean }) { return (await api.patch<{ data: StaffUser }>(`/api/users/${id}/access`, data)).data.data },
  async deleteUser(id: number) { await api.delete(`/api/users/${id}`) },
  async createRole(data: { name: string; permissions: string[] }) { return (await api.post<{ data: AccessRole }>('/api/users/roles', data)).data.data },
  async updateRole(id: number, data: { name: string; permissions: string[] }) { return (await api.put<{ data: AccessRole }>(`/api/users/roles/${id}`, data)).data.data },
  async deleteRole(id: number) { await api.delete(`/api/users/roles/${id}`) },
}

export const usersAccessError = (error: unknown) => getApiErrorMessage(error)

interface PageMeta {
  current_page?: number
  last_page?: number
  per_page?: number
  total?: number
}

interface UserPagePayload {
  data?: StaffUser[] | { data?: StaffUser[]; meta?: PageMeta; current_page?: number; last_page?: number; per_page?: number; total?: number }
  meta?: PageMeta
  current_page?: number
  last_page?: number
  per_page?: number
  total?: number
}

export function normalizeUserPage(payload: UserPagePayload, requestedPage = 1) {
  const resource = Array.isArray(payload.data)
    ? payload
    : payload.data && typeof payload.data === 'object' ? payload.data : {}
  const rows = Array.isArray(resource.data) ? resource.data : []
  const meta = resource.meta ?? payload.meta

  return {
    data: rows,
    meta: {
      current_page: meta?.current_page ?? resource.current_page ?? payload.current_page ?? requestedPage,
      last_page: meta?.last_page ?? resource.last_page ?? payload.last_page ?? 1,
      per_page: meta?.per_page ?? resource.per_page ?? payload.per_page ?? rows.length,
      total: meta?.total ?? resource.total ?? payload.total ?? rows.length,
    },
  }
}
