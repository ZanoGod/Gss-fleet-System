export interface AccessRole {
  id: number
  name: string
  slug: string
  permissions: string[]
  is_system: boolean
  users_count?: number
}

export interface StaffUser {
  id: number
  name: string
  username: string | null
  email: string
  role_id: number | null
  role: Pick<AccessRole, 'id' | 'name' | 'slug'> | null
  is_active: boolean
  approved_at: string | null
  created_at: string
  updated_at: string
}

export interface UserSummary {
  total: number
  active: number
  inactive: number
  roles: number
}

export interface UserFilters {
  search: string
  status: string
  role_id: string
}

export interface PermissionCatalog {
  [permission: string]: string
}
