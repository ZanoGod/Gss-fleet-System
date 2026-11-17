<script setup lang="ts">
import ActionButtonLabel from '@/components/common/ActionButtonLabel.vue'
import BaseConfirmDialog from '@/components/common/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/common/BaseEmptyState.vue'
import BaseLoading from '@/components/common/BaseLoading.vue'
import BaseModal from '@/components/common/BaseModal.vue'
import BasePagination from '@/components/common/BasePagination.vue'
import BaseStatusBadge from '@/components/common/BaseStatusBadge.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import StatusSummary from '@/components/common/StatusSummary.vue'
import StatusSwitch from '@/components/common/StatusSwitch.vue'
import { usePendingAction } from '@/components/common/usePendingAction'
import { toast } from '@/services/toast'
import { computed, onMounted, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { usersAccessApi, usersAccessError } from '../api'
import { usersMetrics } from '../overview'
import type { AccessRole, PermissionCatalog, StaffUser, UserFilters, UserSummary } from '../types'

const auth = useAuthStore()
const can = auth.hasPermission
const filters = reactive<UserFilters>({ search: '', status: '', role_id: '' })
const users = ref<StaffUser[]>([])
const roles = ref<AccessRole[]>([])
const permissionCatalog = ref<PermissionCatalog>({})
const summary = ref<UserSummary | null>(null)
const loading = ref(true)
const summaryLoading = ref(true)
const rolesLoading = ref(true)
const rolesError = ref('')
const error = ref('')
const page = ref(1)
const perPage = 10
const total = ref(0)
const tab = ref<'users' | 'roles'>('users')
const userModal = ref(false)
const userFormView = ref<null | 'edit' | 'access'>(null)
const selectedUser = ref<StaffUser | null>(null)
const selectedRole = ref<AccessRole | null>(null)
const roleDraft = ref(false)
const userErrors = ref<Record<string, string[]>>({})
const accessErrors = ref<Record<string, string[]>>({})
const roleErrors = ref<Record<string, string[]>>({})
const userForm = reactive({
  name: '',
  username: '',
  email: '',
  password: '',
  role_id: '',
  is_active: true,
})
const accessForm = reactive({ role_id: '', is_active: true })
const roleForm = reactive({ name: '', permissions: [] as string[] })
const deletingUser = ref<StaffUser | null>(null)
const deletingRole = ref<AccessRole | null>(null)
const { pending, run } = usePendingAction()
const metrics = computed(() => usersMetrics(summary.value))
const userTitle = computed(() => (selectedUser.value ? 'Edit user' : 'Add user'))
const rolePermissionOptions = computed(() => Object.entries(permissionCatalog.value))
const permissionIcons: Record<string, string> = {
  'dashboard.view': 'bi-grid-1x2-fill',
  'bookings.view': 'bi-calendar-check',
  'contracts.view': 'bi-calendar3',
  'fleet.view': 'bi-car-front-fill',
  'customers.view': 'bi-people',
  'drivers.view': 'bi-person-vcard',
  'operators.view': 'bi-headset',
  'maintenance.view': 'bi-tools',
  'car_licenses.view': 'bi-card-checklist',
  'insurance.view': 'bi-shield-check',
  'payments.view': 'bi-credit-card',
  'reports.view': 'bi-bar-chart-fill',
  'users.view': 'bi-shield-lock',
}
const isAdministratorRole = computed(
  () =>
    selectedRole.value?.slug === 'administrator' ||
    (selectedRole.value?.is_system === true && selectedRole.value.slug === 'admin'),
)

async function loadUsers(nextPage = 1) {
  loading.value = true
  error.value = ''
  try {
    const result = await usersAccessApi.list({ ...filters }, nextPage)
    users.value = result.data
    page.value = result.meta.current_page
    total.value = result.meta.total
    if (nextPage > result.meta.last_page) await loadUsers(Math.max(1, result.meta.last_page))
  } catch (cause) {
    error.value = usersAccessError(cause)
  } finally {
    loading.value = false
  }
}

async function refreshSummary() {
  summaryLoading.value = true
  try {
    summary.value = await usersAccessApi.summary()
  } catch (cause) {
    toast.warning(`User summary: ${usersAccessError(cause)}`)
  } finally {
    summaryLoading.value = false
  }
}

async function loadRoles() {
  rolesLoading.value = true
  rolesError.value = ''
  try {
    const result = await usersAccessApi.roles()
    roles.value = result.data
    permissionCatalog.value = result.permissions
    if (!roleDraft.value) {
      const currentRole =
        result.data.find((role) => role.id === selectedRole.value?.id) ?? result.data[0]
      if (currentRole) selectRole(currentRole)
      else selectedRole.value = null
    }
  } catch (cause) {
    rolesError.value = usersAccessError(cause)
    toast.error(rolesError.value)
  } finally {
    rolesLoading.value = false
  }
}

async function applyFilters() {
  await loadUsers(1)
}
async function resetFilters() {
  filters.search = ''
  filters.status = ''
  filters.role_id = ''
  await loadUsers(1)
}

function openUser(user?: StaffUser) {
  selectedUser.value = user ?? null
  userErrors.value = {}
  accessErrors.value = {}
  Object.assign(userForm, {
    name: user?.name ?? '',
    username: user?.username ?? '',
    email: user?.email ?? '',
    password: '',
    role_id: String(user?.role_id ?? roles.value[0]?.id ?? ''),
    is_active: user?.is_active ?? true,
  })
  if (user) {
    userModal.value = false
    userFormView.value = 'edit'
  } else {
    userFormView.value = null
    userModal.value = true
  }
}

function backToUsers() {
  userFormView.value = null
  selectedUser.value = null
  userErrors.value = {}
  accessErrors.value = {}
}

async function saveUser() {
  userErrors.value = {}
  const data: Record<string, unknown> = { ...userForm, role_id: Number(userForm.role_id) }
  if (!data.password) delete data.password
  try {
    await run('save-user', async () => {
      if (selectedUser.value) await usersAccessApi.updateUser(selectedUser.value.id, data)
      else await usersAccessApi.createUser(data)
      userModal.value = false
      userFormView.value = null
      if (selectedUser.value?.id === auth.user?.id) await auth.getUser()
      toast.success(selectedUser.value ? 'User updated successfully.' : 'User added successfully.')
      await Promise.all([loadUsers(page.value), refreshSummary()])
    })
  } catch (cause) {
    userErrors.value = getFieldErrors(cause)
    toast.error(usersAccessError(cause))
  }
}

function openAccess(user: StaffUser) {
  selectedUser.value = user
  accessErrors.value = {}
  Object.assign(accessForm, { role_id: String(user.role_id ?? ''), is_active: user.is_active })
  userModal.value = false
  userFormView.value = 'access'
}

async function saveAccess() {
  const user = selectedUser.value
  if (!user) return
  accessErrors.value = {}
  try {
    await run('save-access', async () => {
      await usersAccessApi.updateAccess(user.id, {
        role_id: accessForm.role_id ? Number(accessForm.role_id) : null,
        is_active: accessForm.is_active,
      })
      userFormView.value = null
      if (user.id === auth.user?.id) await auth.getUser()
      toast.success('Access updated successfully.')
      await Promise.all([loadUsers(page.value), refreshSummary()])
    })
  } catch (cause) {
    accessErrors.value = getFieldErrors(cause)
    toast.error(usersAccessError(cause))
  }
}

function selectRole(role: AccessRole) {
  roleDraft.value = false
  selectedRole.value = role
  roleErrors.value = {}
  Object.assign(roleForm, {
    name: role.name,
    permissions: role.permissions.filter((permission) => permission in permissionCatalog.value),
  })
}

function startRoleCreation() {
  roleDraft.value = true
  selectedRole.value = null
  roleErrors.value = {}
  Object.assign(roleForm, { name: '', permissions: [] })
}

function cancelRoleDraft() {
  roleDraft.value = false
  if (roles.value[0]) selectRole(roles.value[0])
}

async function saveRole() {
  roleErrors.value = {}
  try {
    await run('save-role', async () => {
      const currentRole = selectedRole.value
      const data = { name: roleForm.name.trim(), permissions: [...roleForm.permissions] }
      const savedRole = currentRole
        ? await usersAccessApi.updateRole(currentRole.id, data)
        : await usersAccessApi.createRole(data)
      roleDraft.value = false
      selectedRole.value = savedRole
      toast.success(currentRole ? 'Role updated successfully.' : 'Role created successfully.')
      await Promise.all([loadRoles(), refreshSummary(), loadUsers(page.value)])
    })
  } catch (cause) {
    roleErrors.value = getFieldErrors(cause)
    toast.error(usersAccessError(cause))
  }
}

async function deleteUser() {
  if (!deletingUser.value) return
  try {
    await run('delete-user', async () => {
      await usersAccessApi.deleteUser(deletingUser.value!.id)
      deletingUser.value = null
      toast.success('User removed successfully.')
      await Promise.all([loadUsers(page.value), refreshSummary()])
    })
  } catch (cause) {
    toast.error(usersAccessError(cause))
  }
}

async function deleteRole() {
  if (!deletingRole.value) return
  try {
    await run('delete-role', async () => {
      await usersAccessApi.deleteRole(deletingRole.value!.id)
      if (selectedRole.value?.id === deletingRole.value!.id) selectedRole.value = null
      deletingRole.value = null
      toast.success('Role removed successfully.')
      await Promise.all([loadRoles(), refreshSummary()])
    })
  } catch (cause) {
    toast.error(usersAccessError(cause))
  }
}

function getFieldErrors(error: unknown): Record<string, string[]> {
  const maybe = error as { response?: { data?: { errors?: Record<string, string[]> } } }
  return maybe.response?.data?.errors ?? {}
}

function fieldError(errors: Record<string, string[]>, key: string): string | undefined {
  return errors[key]?.[0]
}
function initials(name: string) {
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}
function dateLabel(value: string) {
  return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(value))
}

onMounted(() => {
  void loadUsers()
  void refreshSummary()
  void loadRoles()
})
</script>

<template>
  <main class="fleet-module users-access-page">
    <StatusSummary
      :items="metrics"
      :loading="summaryLoading && !summary"
      label="user access summary"
    />

    <div class="users-access-tabs" role="tablist" aria-label="User administration">
      <button
        role="tab"
        :aria-selected="tab === 'users'"
        :class="{ active: tab === 'users' }"
        @click="tab = 'users'"
      >
        <i class="bi bi-people me-2" aria-hidden="true"></i>Staff users
      </button>
      <button
        v-if="can('roles.view')"
        role="tab"
        :aria-selected="tab === 'roles'"
        :class="{ active: tab === 'roles' }"
        @click="tab = 'roles'"
      >
        <i class="bi bi-shield-check me-2" aria-hidden="true"></i>Roles &amp; Permissions
      </button>
    </div>

    <section v-if="tab === 'users'" class="card-shell users-panel">
      <div class="users-panel-heading">
        <div class="users-panel-heading-main">
          <div>
            <h2>
              {{
                userFormView === 'edit'
                  ? 'Edit user'
                  : userFormView === 'access'
                    ? `Manage access · ${selectedUser?.name ?? ''}`
                    : 'Staff users'
              }}
            </h2>
            <p>
              {{
                userFormView
                  ? 'Update this staff account and its access details.'
                  : 'Search and manage accounts across your organization.'
              }}
            </p>
          </div>
          <span v-if="!userFormView" class="users-count"
            >{{ total }} {{ total === 1 ? 'user' : 'users' }}</span
          >
        </div>
        <div class="users-panel-heading-actions">
          <button
            v-if="!userFormView && can('users.create')"
            class="btn gss-button gss-button--primary"
            @click="openUser()"
          >
            <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add user
          </button>
          <button
            v-if="userFormView"
            type="button"
            class="btn gss-button gss-button--outline gss-button--neutral"
            @click="backToUsers"
          >
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Users
          </button>
        </div>
      </div>
      <template v-if="!userFormView">
        <form
          class="users-filters"
          aria-label="Filter users"
          @submit.prevent="run('filter', applyFilters)"
        >
          <div class="users-search-field">
            <label class="form-label" for="user-search">Search</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
              <input
                id="user-search"
                v-model="filters.search"
                class="form-control"
                type="search"
                maxlength="200"
                placeholder="Name, username, or email"
              />
            </div>
          </div>
          <div>
            <label class="form-label" for="user-role-filter">Role</label>
            <select id="user-role-filter" v-model="filters.role_id" class="form-select">
              <option value="">All roles</option>
              <option v-for="role in roles" :key="role.id" :value="String(role.id)">
                {{ role.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="form-label" for="user-status-filter">Status</label>
            <select id="user-status-filter" v-model="filters.status" class="form-select">
              <option value="">All statuses</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="users-filter-actions module-filter-actions">
            <button
              class="btn module-filter-apply"
              type="submit"
              :disabled="loading || pending('filter')"
            >
              <BaseLoading v-if="pending('filter')" label="Filtering…" />
              <template v-else><i class="bi bi-funnel" aria-hidden="true"></i> Filter</template>
            </button>
            <span class="module-filter-divider" aria-hidden="true"></span>
            <button
              type="button"
              class="btn module-filter-reset"
              :disabled="loading || pending('reset')"
              @click="run('reset', resetFilters)"
            >
              <BaseLoading v-if="pending('reset')" label="Resetting…" />
              <template v-else
                ><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</template
              >
            </button>
          </div>
        </form>

        <div v-if="error" class="alert alert-danger mx-3" role="alert">
          {{ error }}
          <button class="btn btn-sm btn-outline-danger ms-2" @click="loadUsers(page)">
            Try again
          </button>
        </div>
        <PageSkeleton
          v-else-if="loading && !users.length"
          label="Loading staff users"
          :columns="5"
          :rows="6"
        />
        <BaseEmptyState
          v-else-if="!users.length"
          class="m-4"
          title="No users found"
          description="Try another search or reset the filters."
          icon="bi-person-x"
        />
        <div v-else class="table-responsive users-table-wrap" :aria-busy="loading">
          <table class="table data-table users-table align-middle mb-0">
            <thead>
              <tr>
                <th scope="col">User</th>
                <th scope="col">Username</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col">Joined</th>
                <th scope="col" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" :class="{ 'users-row-pending': loading }">
                <td>
                  <div class="staff-user-cell">
                    <span class="staff-avatar" aria-hidden="true">{{ initials(user.name) }}</span
                    ><span class="staff-user-copy"
                      ><strong>{{ user.name }}</strong
                      ><small>{{ user.email }}</small></span
                    >
                  </div>
                </td>
                <td class="username-cell">{{ user.username || '—' }}</td>
                <td>
                  <span class="role-chip"
                    ><i class="bi bi-shield-check" aria-hidden="true"></i
                    >{{ user.role?.name ?? 'No role' }}</span
                  >
                </td>
                <td>
                  <BaseStatusBadge
                    v-if="user.approved_at == null && (!user.is_active || user.role_id == null)"
                    status="Pending Approval"
                  />
                  <BaseStatusBadge v-else :status="user.is_active ? 'Active' : 'Inactive'" />
                </td>
                <td class="date-cell">{{ dateLabel(user.created_at) }}</td>
                <td>
                  <div class="users-row-actions">
                    <button
                      v-if="can('users.update')"
                      class="btn btn-sm gss-button gss-button--outline gss-button--neutral users-action-edit"
                      :aria-label="`Edit ${user.name}`"
                      @click="openUser(user)"
                    >
                      <i class="bi bi-pencil-fill" aria-hidden="true"></i
                      ><span class="visually-hidden">Edit</span>
                    </button>
                    <button
                      v-if="can('access.manage')"
                      class="btn btn-sm gss-button gss-button--outline users-action-access"
                      :aria-label="`Manage access for ${user.name}`"
                      @click="openAccess(user)"
                    >
                      <i class="bi bi-key-fill" aria-hidden="true"></i
                      ><span class="visually-hidden">Access</span>
                    </button>
                    <button
                      v-if="can('users.delete') && user.id !== auth.user?.id"
                      class="btn btn-sm gss-button gss-button--destructive users-action-delete"
                      :aria-label="`Remove ${user.name}`"
                      @click="deletingUser = user"
                    >
                      <i class="bi bi-trash3-fill" aria-hidden="true"></i
                      ><span class="visually-hidden">Remove</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="users.length" class="users-pagination">
          <BasePagination
            :page="page"
            :per-page="perPage"
            :total="total"
            :disabled="loading"
            @update:page="loadUsers"
          />
        </div>
      </template>
      <div v-else class="users-inline-form">
        <form v-if="userFormView === 'edit'" class="access-form" @submit.prevent="saveUser">
          <p class="modal-intro">
            Enter the account details and assign a role for this staff member.
          </p>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="staff-name">Full name</label
              ><input
                id="staff-name"
                v-model="userForm.name"
                class="form-control"
                :class="{ 'is-invalid': fieldError(userErrors, 'name') }"
                autocomplete="name"
                required
                maxlength="255"
              />
              <div v-if="fieldError(userErrors, 'name')" class="invalid-feedback">
                {{ fieldError(userErrors, 'name') }}
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="staff-username">Username</label
              ><input
                id="staff-username"
                v-model="userForm.username"
                class="form-control"
                :class="{ 'is-invalid': fieldError(userErrors, 'username') }"
                autocomplete="username"
                required
                maxlength="255"
              />
              <div v-if="fieldError(userErrors, 'username')" class="invalid-feedback">
                {{ fieldError(userErrors, 'username') }}
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="staff-email">Email</label
              ><input
                id="staff-email"
                v-model="userForm.email"
                class="form-control"
                :class="{ 'is-invalid': fieldError(userErrors, 'email') }"
                type="email"
                autocomplete="email"
                required
                maxlength="255"
              />
              <div v-if="fieldError(userErrors, 'email')" class="invalid-feedback">
                {{ fieldError(userErrors, 'email') }}
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="staff-password">New password</label
              ><input
                id="staff-password"
                v-model="userForm.password"
                class="form-control"
                :class="{ 'is-invalid': fieldError(userErrors, 'password') }"
                type="password"
                minlength="8"
                autocomplete="new-password"
              />
              <div v-if="fieldError(userErrors, 'password')" class="invalid-feedback">
                {{ fieldError(userErrors, 'password') }}
              </div>
              <small class="form-text"
                >At least 8 characters; leave blank to keep the current password.</small
              >
            </div>
            <div class="col-md-6">
              <label class="form-label" for="staff-role">Role</label
              ><select
                id="staff-role"
                v-model="userForm.role_id"
                class="form-select"
                :class="{ 'is-invalid': fieldError(userErrors, 'role_id') }"
              >
                <option value="">No role</option>
                <option v-for="role in roles" :key="role.id" :value="String(role.id)">
                  {{ role.name }}
                </option>
              </select>
              <div v-if="fieldError(userErrors, 'role_id')" class="invalid-feedback">
                {{ fieldError(userErrors, 'role_id') }}
              </div>
            </div>
            <div class="col-12">
              <label class="form-label d-block">Account status</label
              ><StatusSwitch
                id="edit-user-status"
                v-model="userForm.is_active"
                label="Account status"
                :disabled="pending('save-user')"
              /><small class="form-text">Active users can sign in with this account.</small>
            </div>
          </div>
          <div class="users-inline-actions">
            <button
              type="button"
              class="btn gss-button gss-button--outline gss-button--neutral"
              :disabled="pending('save-user')"
              @click="backToUsers"
            >
              Cancel</button
            ><button
              type="submit"
              class="btn gss-button gss-button--primary"
              :disabled="pending('save-user')"
            >
              <ActionButtonLabel :pending="pending('save-user')" loading-label="Saving…"
                >Save changes</ActionButtonLabel
              >
            </button>
          </div>
        </form>
        <form v-else class="access-form" @submit.prevent="saveAccess">
          <p class="modal-intro">Update the role and account status for this user.</p>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="access-role">Role</label
              ><select
                id="access-role"
                v-model="accessForm.role_id"
                class="form-select"
                :class="{ 'is-invalid': fieldError(accessErrors, 'role_id') }"
              >
                <option value="">No role (remove access)</option>
                <option v-for="role in roles" :key="role.id" :value="String(role.id)">
                  {{ role.name }}
                </option>
              </select>
              <div v-if="fieldError(accessErrors, 'role_id')" class="invalid-feedback">
                {{ fieldError(accessErrors, 'role_id') }}
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label d-block">Account status</label
              ><StatusSwitch
                id="manage-access-status"
                v-model="accessForm.is_active"
                label="Account status"
                :disabled="pending('save-access')"
              /><small class="form-text">Inactive users are signed out and cannot log in.</small>
            </div>
          </div>
          <div class="users-inline-actions">
            <button
              type="button"
              class="btn gss-button gss-button--outline gss-button--neutral"
              :disabled="pending('save-access')"
              @click="backToUsers"
            >
              Cancel</button
            ><button
              type="submit"
              class="btn gss-button gss-button--primary"
              :disabled="pending('save-access')"
            >
              <ActionButtonLabel :pending="pending('save-access')" loading-label="Saving…"
                >Save access</ActionButtonLabel
              >
            </button>
          </div>
        </form>
      </div>
    </section>

    <section v-else class="card-shell users-panel roles-panel">
      <div class="users-panel-heading">
        <div class="users-panel-heading-main">
          <div>
            <h2>Roles &amp; Permissions</h2>
            <p>Choose which modules each role can view.</p>
          </div>
          <span v-if="!rolesLoading" class="users-count"
            >{{ roles.length }} {{ roles.length === 1 ? 'role' : 'roles' }}</span
          >
        </div>
        <div class="users-panel-heading-actions">
          <button
            v-if="can('roles.manage')"
            class="btn gss-button gss-button--primary"
            @click="startRoleCreation"
          >
            <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Create role
          </button>
        </div>
      </div>
      <div v-if="!can('roles.view')" class="p-4">
        <BaseEmptyState
          title="Role access required"
          description="Your account does not have permission to view roles."
          icon="bi-lock"
        />
      </div>
      <div v-else-if="rolesError" class="roles-error" role="alert">
        <span>{{ rolesError }}</span
        ><button
          class="btn gss-button gss-button--outline gss-button--neutral"
          :disabled="rolesLoading"
          @click="loadRoles"
        >
          <ActionButtonLabel :pending="rolesLoading" loading-label="Retrying…"
            >Retry roles</ActionButtonLabel
          >
        </button>
      </div>
      <div v-else class="role-workspace" :aria-busy="rolesLoading">
        <aside class="role-picker" aria-label="Roles">
          <h3>Roles</h3>
          <PageSkeleton
            v-if="rolesLoading && !roles.length"
            label="Loading roles"
            kind="detail"
            :rows="4"
          />
          <BaseEmptyState
            v-else-if="!roles.length && !roleDraft"
            title="No roles yet"
            description="Create a role to set module access."
            icon="bi-shield"
          />
          <div v-else class="role-list" role="listbox" aria-label="Choose a role">
            <button
              v-for="role in roles"
              :key="role.id"
              type="button"
              role="option"
              :aria-selected="selectedRole?.id === role.id"
              class="role-list-item"
              :class="{ selected: selectedRole?.id === role.id && !roleDraft }"
              @click="selectRole(role)"
            >
              <span class="role-list-icon" aria-hidden="true"
                ><i class="bi bi-shield-lock"></i
              ></span>
              <span class="role-list-copy"
                ><strong>{{ role.name }}</strong
                ><small
                  >{{ role.users_count ?? 0 }}
                  {{ role.users_count === 1 ? 'member' : 'members' }}</small
                ></span
              >
              <i
                v-if="role.is_system"
                class="bi bi-lock-fill role-system-icon"
                aria-label="System role"
              ></i>
            </button>
          </div>
        </aside>

        <div class="permission-workspace">
          <template v-if="selectedRole || roleDraft">
            <div class="permission-heading">
              <div class="permission-role-title">
                <span class="permission-role-icon"
                  ><i class="bi bi-shield-check" aria-hidden="true"></i
                ></span>
                <div>
                  <label v-if="can('roles.manage')" class="form-label" for="role-name"
                    >Role name</label
                  >
                  <input
                    v-if="can('roles.manage')"
                    id="role-name"
                    v-model="roleForm.name"
                    class="form-control"
                    :class="{ 'is-invalid': fieldError(roleErrors, 'name') }"
                    maxlength="80"
                    :placeholder="roleDraft ? 'Enter role name' : ''"
                    :disabled="selectedRole?.is_system"
                  />
                  <h3 v-else>{{ selectedRole?.name }}</h3>
                  <div v-if="fieldError(roleErrors, 'name')" class="invalid-feedback d-block">
                    {{ fieldError(roleErrors, 'name') }}
                  </div>
                  <p>
                    {{
                      selectedRole
                        ? `${selectedRole.users_count ?? 0} assigned ${selectedRole.users_count === 1 ? 'user' : 'users'}`
                        : 'New role'
                    }}<span v-if="selectedRole?.is_system"> · System role</span>
                  </p>
                </div>
              </div>
              <div
                v-if="selectedRole && can('roles.manage') && !selectedRole.is_system"
                class="role-delete-action"
              >
                <button
                  class="btn gss-button gss-button--destructive"
                  :disabled="!!selectedRole.users_count"
                  :title="
                    selectedRole.users_count
                      ? 'Move assigned users before deleting this role.'
                      : undefined
                  "
                  @click="deletingRole = selectedRole"
                >
                  <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Delete role
                </button>
              </div>
            </div>
            <div class="permission-matrix-heading">
              <div>
                <h4>Module access</h4>
                <p v-if="isAdministratorRole" class="permission-locked-note">
                  <i class="bi bi-lock-fill me-1" aria-hidden="true"></i>
                  Administrator access is required for every module and cannot be changed.
                </p>
                <p v-else>View only access. Changes apply to all users assigned to this role.</p>
              </div>
              <span v-if="isAdministratorRole" class="view-only-label"
                ><i class="bi bi-eye-fill me-1" aria-hidden="true"></i>View only</span
              >
              <span v-else class="view-only-label"
                ><i class="bi bi-pencil-fill me-1" aria-hidden="true"></i>Edit only</span
              >
            </div>
            <fieldset class="permission-grid-frame" aria-label="Module permissions">
              <legend class="visually-hidden">Module permissions</legend>
              <div class="permission-grid">
                <label
                  v-for="[permission, label] in rolePermissionOptions"
                  :key="permission"
                  class="permission-grid-item"
                  :class="{
                    'is-disabled':
                      !can('roles.manage') || isAdministratorRole || pending('save-role'),
                  }"
                >
                  <span class="permission-module-icon" aria-hidden="true"
                    ><i
                      class="bi"
                      :class="permissionIcons[permission] ?? 'bi-folder2-open'"
                    ></i
                  ></span>
                  <span class="permission-grid-label">{{ label }}</span>
                  <input
                    v-model="roleForm.permissions"
                    class="form-check-input permission-grid-checkbox"
                    type="checkbox"
                    :value="permission"
                    :aria-label="`View ${label}`"
                    :disabled="!can('roles.manage') || isAdministratorRole || pending('save-role')"
                  />
                </label>
              </div>
            </fieldset>
            <div v-if="fieldError(roleErrors, 'permissions')" class="text-danger small mt-2">
              {{ fieldError(roleErrors, 'permissions') }}
            </div>
            <div v-if="can('roles.manage')" class="permission-actions">
              <button
                v-if="roleDraft"
                class="btn gss-button gss-button--outline gss-button--neutral"
                :disabled="pending('save-role')"
                @click="cancelRoleDraft"
              >
                Cancel
              </button>
              <button
                class="btn gss-button gss-button--primary"
                :disabled="pending('save-role') || !roleForm.name.trim()"
                @click="saveRole"
              >
                <ActionButtonLabel :pending="pending('save-role')" loading-label="Saving…">{{
                  roleDraft ? 'Create role' : 'Save permissions'
                }}</ActionButtonLabel>
              </button>
            </div>
          </template>
          <BaseEmptyState
            v-else
            title="Select a role"
            description="Choose a role from the list to review its module access."
            icon="bi-shield-check"
          />
        </div>
      </div>
    </section>

    <BaseModal
      :open="userModal"
      :title="userTitle"
      :busy="pending('save-user')"
      @close="userModal = false"
    >
      <form class="access-form" @submit.prevent="saveUser">
        <p class="modal-intro">
          Enter the account details and assign a role for this staff member.
        </p>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="staff-name">Full name</label
            ><input
              id="staff-name"
              v-model="userForm.name"
              class="form-control"
              :class="{ 'is-invalid': fieldError(userErrors, 'name') }"
              autocomplete="name"
              required
              maxlength="255"
            />
            <div v-if="fieldError(userErrors, 'name')" class="invalid-feedback">
              {{ fieldError(userErrors, 'name') }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="staff-username">Username</label
            ><input
              id="staff-username"
              v-model="userForm.username"
              class="form-control"
              :class="{ 'is-invalid': fieldError(userErrors, 'username') }"
              autocomplete="username"
              required
              maxlength="255"
            />
            <div v-if="fieldError(userErrors, 'username')" class="invalid-feedback">
              {{ fieldError(userErrors, 'username') }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="staff-email">Email</label
            ><input
              id="staff-email"
              v-model="userForm.email"
              class="form-control"
              :class="{ 'is-invalid': fieldError(userErrors, 'email') }"
              type="email"
              autocomplete="email"
              required
              maxlength="255"
            />
            <div v-if="fieldError(userErrors, 'email')" class="invalid-feedback">
              {{ fieldError(userErrors, 'email') }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="staff-password">{{
              selectedUser ? 'New password' : 'Temporary password'
            }}</label
            ><input
              id="staff-password"
              v-model="userForm.password"
              class="form-control"
              :class="{ 'is-invalid': fieldError(userErrors, 'password') }"
              type="password"
              :required="!selectedUser"
              minlength="8"
              autocomplete="new-password"
            />
            <div v-if="fieldError(userErrors, 'password')" class="invalid-feedback">
              {{ fieldError(userErrors, 'password') }}
            </div>
            <small class="form-text"
              >At least 8 characters{{
                selectedUser ? '; leave blank to keep the current password.' : '.'
              }}</small
            >
          </div>
          <div class="col-md-6">
            <label class="form-label" for="staff-role">Role</label
            ><select
              id="staff-role"
              v-model="userForm.role_id"
              class="form-select"
              :class="{ 'is-invalid': fieldError(userErrors, 'role_id') }"
              required
            >
              <option value="" disabled>Select a role</option>
              <option v-for="role in roles" :key="role.id" :value="String(role.id)">
                {{ role.name }}
              </option>
            </select>
            <div v-if="fieldError(userErrors, 'role_id')" class="invalid-feedback">
              {{ fieldError(userErrors, 'role_id') }}
            </div>
          </div>
          <div class="col-12">
            <label class="form-label d-block">Account status</label
            ><StatusSwitch
              id="add-user-status"
              v-model="userForm.is_active"
              label="Account status"
              :disabled="pending('save-user')"
            /><small class="form-text">Active users can sign in with this account.</small>
          </div>
        </div>
      </form>
      <template #actions
        ><button
          class="btn gss-button gss-button--outline gss-button--neutral"
          :disabled="pending('save-user')"
          @click="userModal = false"
        >
          Cancel</button
        ><button
          class="btn gss-button gss-button--primary"
          :disabled="pending('save-user')"
          @click="saveUser"
        >
          <ActionButtonLabel :pending="pending('save-user')" loading-label="Saving…">{{
            selectedUser ? 'Save changes' : 'Add user'
          }}</ActionButtonLabel>
        </button></template
      >
    </BaseModal>

    <BaseConfirmDialog
      :open="!!deletingUser"
      title="Remove user?"
      :message="`Remove ${deletingUser?.name ?? 'this user'}? They will no longer be able to sign in.`"
      :busy="pending('delete-user')"
      @close="deletingUser = null"
      @confirm="deleteUser"
    />
    <BaseConfirmDialog
      :open="!!deletingRole"
      title="Delete role?"
      :message="`Delete ${deletingRole?.name ?? 'this role'}? This cannot be undone.`"
      :busy="pending('delete-role')"
      @close="deletingRole = null"
      @confirm="deleteRole"
    />
  </main>
</template>

<style scoped>
.users-access-page {
  --users-border: var(--line);
}
.users-panel-heading p {
  color: var(--muted);
  margin: 0;
  font-size: 0.92rem;
  line-height: 1.5;
}
.users-access-tabs {
  display: flex;
  gap: 0.35rem;
  border-bottom: 1px solid var(--users-border);
  margin-bottom: 1rem;
}
.users-access-tabs button {
  min-height: 46px;
  padding: 0.65rem 1rem;
  border: 0;
  border-bottom: 3px solid transparent;
  background: transparent;
  color: var(--muted);
  font: inherit;
  font-weight: 700;
  transition:
    color 160ms ease,
    border-color 160ms ease;
}
.users-access-tabs button.active {
  border-color: var(--amber);
  color: var(--cocoa);
}
.users-access-tabs button:hover {
  color: var(--cocoa);
}
.users-panel {
  min-width: 0;
  overflow: clip;
}
.users-panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.3rem 1.4rem 1rem;
}
.users-panel-heading-main {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}
.users-panel-heading-main > div {
  min-width: 0;
}
.users-panel-heading-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex: none;
}
.users-panel-heading h2 {
  font-size: 1.15rem;
  margin: 0 0 0.25rem;
}
.users-count {
  padding: 0.38rem 0.68rem;
  border-radius: 999px;
  background: var(--gss-surface-card-header);
  color: var(--muted);
  font-size: 0.8rem;
  font-weight: 700;
  white-space: nowrap;
}
.users-filters {
  display: grid;
  grid-template-columns: minmax(220px, 1.5fr) minmax(145px, 0.8fr) minmax(145px, 0.8fr) auto;
  align-items: end;
  gap: 0.85rem;
  padding: 0 1.4rem 1.2rem;
  border-bottom: 1px solid var(--users-border);
}
.users-filters .form-label,
.access-form .form-label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--gss-text-secondary);
  margin-bottom: 0.4rem;
}
.users-filters .form-control,
.users-filters .form-select,
.access-form .form-control,
.access-form .form-select {
  min-height: 42px;
  font-size: 0.9rem;
  border-color: var(--users-border);
  background-color: var(--paper-strong);
}
.users-filters .input-group-text {
  color: var(--muted);
  border-color: var(--users-border);
  background: var(--gss-surface-card-header);
}
.users-filter-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.users-table-wrap {
  min-height: 140px;
}
.users-table {
  --bs-table-bg: transparent;
  --bs-table-color: var(--ink);
  min-width: 780px;
}
.users-table thead th {
  padding: 0.8rem 1rem;
  background: var(--gss-surface-card-header);
  color: var(--muted);
  font-size: 0.73rem;
  font-weight: 700;
  letter-spacing: 0.035em;
  border-bottom-color: var(--users-border);
  white-space: nowrap;
}
.users-table tbody td {
  padding: 0.8rem 1rem;
  border-bottom-color: var(--users-border);
  font-size: 0.86rem;
}
.users-table tbody tr:last-child td {
  border-bottom: 0;
}
.users-row-pending {
  opacity: 0.6;
}
.staff-user-cell {
  display: flex;
  align-items: center;
  gap: 0.72rem;
  min-width: 200px;
}
.staff-avatar {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  flex: none;
  border-radius: 50%;
  color: var(--cocoa);
  background: var(--gss-status-warning-background);
  font-size: 0.77rem;
  font-weight: 800;
}
.staff-user-copy {
  display: grid;
  gap: 0.14rem;
  min-width: 0;
}
.staff-user-copy strong {
  font-size: 0.88rem;
}
.staff-user-copy small {
  color: var(--muted);
  overflow-wrap: anywhere;
  font-size: 0.77rem;
}
.username-cell,
.date-cell {
  color: var(--muted);
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}
.role-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.6rem;
  border: 1px solid var(--users-border);
  border-radius: 999px;
  white-space: nowrap;
  font-size: 0.78rem;
  font-weight: 700;
}
.role-chip i {
  color: var(--gss-text-secondary);
}
.users-row-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.35rem;
}
.users-row-actions .btn {
  width: 34px;
  height: 34px;
  padding: 0.35rem;
  display: grid;
  place-items: center;
}
.users-row-actions .btn.gss-button.users-action-edit {
  --gss-action-color: var(--gss-text-strong);
  --gss-action-border: var(--gss-action-color);
  --gss-action-hover-border: var(--gss-brand-secondary);
  --gss-action-hover: var(--gss-brand-secondary);
  color: var(--gss-action-color);
  border-color: var(--gss-action-border);
}
.users-row-actions .btn.gss-button.users-action-access {
  --gss-action-color: color-mix(in srgb, var(--gss-brand-secondary), var(--gss-text-strong) 48%);
  --gss-action-border: var(--gss-action-color);
  --gss-action-hover-border: var(--gss-action-color);
  --gss-action-hover: color-mix(in srgb, var(--gss-action-color), transparent 93%);
  --gss-action-press: color-mix(in srgb, var(--gss-action-color), transparent 86%);
  color: var(--gss-action-color);
  border-color: var(--gss-action-border);
}
.users-row-actions .btn.gss-button.users-action-delete {
  --gss-action-border: var(--gss-status-danger-text);
  color: var(--gss-action-color);
  border-color: var(--gss-action-border);
}
.users-row-actions .users-action-edit i,
.users-row-actions .users-action-access i,
.users-row-actions .users-action-delete i {
  color: currentColor;
}
.users-row-actions .btn.gss-button.users-action-edit:hover:not(:disabled) {
  color: var(--gss-text-strong);
  border-color: var(--gss-text-strong);
  background-color: color-mix(in srgb, var(--gss-text-secondary), transparent 88%);
}
.users-row-actions .btn.gss-button.users-action-access:hover:not(:disabled) {
  color: var(--gss-text-strong);
  border-color: var(--gss-action-color);
  background-color: color-mix(in srgb, var(--gss-action-color), transparent 86%);
}
.users-row-actions .btn.gss-button.users-action-delete:hover:not(:disabled) {
  color: var(--gss-status-danger-text);
  border-color: var(--gss-status-danger-text);
  background-color: var(--gss-status-danger-background);
}
.users-pagination {
  padding: 1rem 1.4rem;
  border-top: 1px solid var(--users-border);
}
.users-inline-form {
  padding: 1.35rem 1.4rem 1.5rem;
  animation: users-form-enter 180ms ease-out both;
}
.users-inline-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.65rem;
  margin-top: 1.35rem;
  padding-top: 1.15rem;
  border-top: 1px solid var(--users-border);
}
@keyframes users-form-enter {
  from {
    opacity: 0;
    transform: translateY(5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.roles-error {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin: 0 1.4rem 1.4rem;
  padding: 1rem;
  border: 1px solid var(--gss-status-danger-text);
  border-radius: 12px;
  color: var(--gss-status-danger-text);
}
.role-workspace {
  display: grid;
  grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
  min-height: 430px;
  border-top: 1px solid var(--users-border);
}
.role-picker {
  min-width: 0;
  padding: 1.2rem 1rem;
  border-right: 1px solid var(--users-border);
  background: var(--gss-surface-card-header);
}
.role-picker h3 {
  margin: 0 0 0.8rem;
  font-size: 0.9rem;
}
.role-list {
  display: grid;
  gap: 0.35rem;
}
.role-list-item {
  width: 100%;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.7rem;
  text-align: start;
  border: 1px solid transparent;
  border-radius: 12px;
  background: transparent;
  color: var(--ink);
  transition:
    background-color 160ms ease,
    border-color 160ms ease,
    transform 160ms ease;
}
.role-list-item:hover {
  background: var(--paper-strong);
}
.role-list-item:active {
  transform: scale(0.99);
}
.role-list-item.selected {
  border-color: var(--gss-button-outline-border);
  background: var(--paper-strong);
  box-shadow: 0 2px 8px var(--gss-shadow-tint-subtle);
}
.role-list-icon,
.permission-role-icon {
  display: grid;
  place-items: center;
  flex: none;
  width: 38px;
  height: 38px;
  border-radius: 11px;
  color: var(--cocoa);
  background: var(--gss-status-warning-background);
}
.role-list-copy {
  display: grid;
  gap: 0.16rem;
  min-width: 0;
  flex: 1;
}
.role-list-copy strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.84rem;
}
.role-list-copy small {
  color: var(--muted);
  font-size: 0.74rem;
}
.role-system-icon {
  color: var(--muted);
  font-size: 0.73rem;
}
.permission-workspace {
  min-width: 0;
  padding: 1.35rem 1.5rem 1.2rem;
}
.permission-heading,
.permission-role-title,
.permission-matrix-heading,
.permission-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.permission-heading {
  padding-bottom: 1.1rem;
  border-bottom: 1px solid var(--users-border);
}
.permission-role-title {
  min-width: 0;
  justify-content: flex-start;
}
.permission-role-icon {
  width: 44px;
  height: 44px;
  border-radius: 13px;
  font-size: 1.05rem;
}
.permission-role-title h3 {
  margin: 0 0 0.2rem;
  font-size: 1.05rem;
}
.permission-role-title .form-label {
  display: block;
  margin: 0 0 0.28rem;
  color: var(--muted);
  font-size: 0.74rem;
  font-weight: 700;
}
.permission-role-title .form-control {
  width: min(100%, 280px);
  min-height: 38px;
  font-size: 0.93rem;
  font-weight: 700;
}
.permission-role-title p {
  margin: 0;
  color: var(--muted);
  font-size: 0.77rem;
}
.role-delete-action {
  flex: none;
}
.permission-matrix-heading {
  padding: 1.1rem 0 0.75rem;
}
.permission-matrix-heading h4 {
  margin: 0 0 0.2rem;
  font-size: 0.9rem;
}
.permission-matrix-heading p {
  margin: 0;
  color: var(--muted);
  font-size: 0.77rem;
  line-height: 1.45;
}
.permission-matrix-heading .permission-locked-note {
  color: var(--gss-status-warning-text);
}
.view-only-label {
  display: inline-flex;
  align-items: center;
  padding: 0.34rem 0.58rem;
  border-radius: 999px;
  background: var(--gss-status-info-background);
  color: var(--gss-status-info-text);
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
}
.permission-grid-frame {
  min-width: 0;
  border: 1px solid var(--users-border);
  border-radius: 13px;
  margin: 0;
  padding: 0.5rem;
}
.permission-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.55rem;
}
.permission-grid-item {
  min-width: 0;
  min-height: 54px;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--users-border);
  border-radius: 11px;
  background: var(--paper-strong);
  cursor: pointer;
  transition:
    border-color 160ms ease,
    background-color 160ms ease,
    box-shadow 160ms ease;
}
.permission-grid-item:hover {
  border-color: var(--gss-button-outline-hover-border);
  background: var(--gss-button-outline-hover-bg);
}
.permission-grid-item:has(.permission-grid-checkbox:checked) {
  border-color: color-mix(in srgb, var(--gss-button-primary-bg), transparent 54%);
  background: color-mix(in srgb, var(--gss-button-primary-bg), transparent 96%);
}
.permission-grid-item.is-disabled {
  cursor: not-allowed;
  opacity: 0.74;
}
.permission-grid-label {
  min-width: 0;
  flex: 1;
  font-size: 0.82rem;
  font-weight: 600;
  line-height: 1.35;
}
.permission-grid-checkbox {
  float: none;
  flex: none;
  width: 1.1rem;
  height: 1.1rem;
  margin: 0;
  cursor: inherit;
  border-color: var(--gss-button-outline-border);
}
.permission-grid-checkbox:checked {
  background-color: var(--gss-button-primary-bg);
  border-color: var(--gss-button-primary-bg);
}
.permission-grid-checkbox:focus {
  box-shadow: 0 0 0 0.2rem var(--gss-palette-rgba-240-181-52-p2);
}
.permission-module-icon {
  display: inline-grid;
  place-items: center;
  width: 29px;
  height: 29px;
  margin-inline-end: 0.62rem;
  border-radius: 9px;
  background: var(--gss-surface-card-header);
  color: var(--gss-text-secondary);
}
.permission-toggle {
  display: inline-grid;
  place-items: center;
  min-width: 44px;
  min-height: 40px;
  cursor: pointer;
}
.permission-toggle .form-check-input {
  float: none;
  width: 2.15rem;
  height: 1.15rem;
  margin: 0;
  cursor: inherit;
  border-color: var(--gss-button-outline-border);
}
.permission-toggle .form-check-input:checked {
  background-color: var(--gss-button-primary-bg);
  border-color: var(--gss-button-primary-bg);
}
.permission-toggle .form-check-input:focus {
  box-shadow: 0 0 0 0.2rem var(--gss-palette-rgba-240-181-52-p2);
}
.permission-actions {
  justify-content: flex-end;
  margin-top: 1rem;
}
.roles-panel .empty-state {
  padding: 1.5rem;
}
.access-form {
  padding: 0.25rem 0.1rem;
}
.modal-intro {
  color: var(--muted);
  font-size: 0.88rem;
  margin: 0 0 1rem;
}
.permission-option input {
  width: 18px;
  height: 18px;
  accent-color: var(--gss-button-primary-bg);
  flex: none;
}
.permission-list {
  border: 1px solid var(--users-border);
  border-radius: 12px;
  padding: 0.85rem 1rem;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.6rem;
}
.permission-list legend {
  float: none;
  width: auto;
  padding: 0 0.3rem;
  margin: 0;
  color: var(--gss-text-secondary);
  font-size: 0.8rem;
  font-weight: 700;
}
.permission-option {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  min-height: 34px;
  color: var(--ink);
  font-size: 0.82rem;
  cursor: pointer;
}
@media (max-width: 900px) {
  .users-filters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .users-search-field {
    grid-column: 1/-1;
  }
}
@media (max-width: 760px) {
  .role-workspace {
    grid-template-columns: minmax(0, 1fr);
  }
  .role-picker {
    border-right: 0;
    border-bottom: 1px solid var(--users-border);
  }
  .role-list {
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 190px), 1fr));
  }
  .permission-workspace {
    padding: 1.1rem;
  }
}
@media (max-width: 575.98px) {
  .users-panel-heading {
    padding: 0.95rem;
    flex-wrap: wrap;
  }
  .users-panel-heading-main {
    flex: 1 1 100%;
    justify-content: space-between;
  }
  .users-panel-heading-actions {
    width: 100%;
  }
  .users-panel-heading-actions .btn {
    width: auto;
  }
  .users-filters {
    grid-template-columns: 1fr;
    padding: 0 0.95rem 1rem;
  }
  .users-search-field {
    grid-column: auto;
  }
  .users-filter-actions {
    display: flex;
    flex-wrap: nowrap;
  }
  .users-filter-actions .module-filter-apply {
    min-width: 6.4rem;
  }
  .users-pagination {
    padding: 0.9rem;
  }
  .users-inline-form {
    padding: 1rem 0.95rem 1.1rem;
  }
  .users-inline-actions {
    flex-wrap: wrap;
  }
  .users-inline-actions .btn {
    flex: 1;
  }
  .permission-workspace {
    padding: 0.9rem;
  }
  .permission-heading {
    align-items: flex-start;
    flex-direction: column;
  }
  .permission-matrix-heading {
    align-items: flex-start;
  }
  .permission-grid {
    grid-template-columns: minmax(0, 1fr);
  }
  .roles-error {
    flex-direction: column;
    align-items: stretch;
    margin: 0 0.95rem 0.95rem;
  }
}
@media (prefers-reduced-motion: reduce) {
  .users-access-tabs button,
  .role-list-item {
    transition: none;
  }
  .users-inline-form {
    animation: none;
  }
}
</style>
