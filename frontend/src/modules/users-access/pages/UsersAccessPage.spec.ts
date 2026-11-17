import { flushPromises, mount } from '@vue/test-utils'
import type { DOMWrapper, VueWrapper } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import UsersAccessPage from './UsersAccessPage.vue'

const mocks = vi.hoisted(() => ({
  list: vi.fn(), summary: vi.fn(), roles: vi.fn(), createUser: vi.fn(), updateUser: vi.fn(),
  updateAccess: vi.fn(), deleteUser: vi.fn(), createRole: vi.fn(), updateRole: vi.fn(), deleteRole: vi.fn(),
}))

vi.mock('../api', () => ({
  usersAccessApi: mocks,
  usersAccessError: (error: unknown) => error instanceof Error ? error.message : 'Request failed.',
}))
vi.mock('@/stores/auth', () => ({ useAuthStore: () => ({ user: { id: 1 }, hasPermission: () => true, getUser: vi.fn() }) }))

const role = { id: 2, name: 'Staff', slug: 'staff', permissions: [], is_system: true, users_count: 1 }
const user = {
  id: 1, name: 'Aung Min', username: 'aung-min', email: 'aung@example.test', role_id: 2,
  role: { id: 2, name: 'Staff', slug: 'staff' }, is_active: true, approved_at: '2026-11-01T00:00:00.000Z',
  created_at: '2026-11-01T00:00:00.000Z', updated_at: '2026-11-01T00:00:00.000Z',
}

function page() {
  return mount(UsersAccessPage, { attachTo: document.body })
}

async function clickText(wrapper: VueWrapper | DOMWrapper<Element>, label: string) {
  const button = wrapper.findAll('button').find((candidate) => candidate.text().includes(label))
  if (!button) throw new Error(`Button not found: ${label}`)
  await button.trigger('click')
}

describe('Users & Access workflows', () => {
  beforeEach(() => {
    vi.resetAllMocks()
    mocks.list.mockImplementation(async (_filters, requestedPage = 1) => ({
      data: [user], meta: { current_page: requestedPage, last_page: 3, per_page: 10, total: 25 },
    }))
    mocks.summary.mockResolvedValue({ total: 1, active: 1, inactive: 0, roles: 2 })
    mocks.roles.mockResolvedValue({ data: [role], permissions: { 'users.view': 'Users & Access', 'dashboard.view': 'Dashboard' } })
    mocks.createUser.mockResolvedValue(user)
    mocks.updateUser.mockResolvedValue(user)
    mocks.updateAccess.mockResolvedValue(user)
    mocks.createRole.mockResolvedValue(role)
  })

  it('filters users and supports add, edit, and access updates', async () => {
    const wrapper = page()
    await flushPromises()
    expect(wrapper.text()).toContain('Aung Min')

    await wrapper.get('#user-search').setValue('Aung')
    await wrapper.get('#user-role-filter').setValue('2')
    await wrapper.get('#user-status-filter').setValue('Active')
    await clickText(wrapper, 'Filter')
    await flushPromises()
    expect(mocks.list).toHaveBeenLastCalledWith({ search: 'Aung', status: 'Active', role_id: '2' }, 1)
    await wrapper.get('[aria-label="Page 2"]').trigger('click')
    await flushPromises()
    expect(mocks.list).toHaveBeenLastCalledWith({ search: 'Aung', status: 'Active', role_id: '2' }, 2)
    await clickText(wrapper, 'Reset')
    await flushPromises()
    expect(mocks.list).toHaveBeenLastCalledWith({ search: '', status: '', role_id: '' }, 1)

    await clickText(wrapper, 'Add user')
    const addDialog = wrapper.find('[role="dialog"]')
    await addDialog.get('#staff-name').setValue('New User')
    await addDialog.get('#staff-username').setValue('new-user')
    await addDialog.get('#staff-email').setValue('new@example.test')
    await addDialog.get('#staff-password').setValue('temporary-password')
    await addDialog.get('#staff-role').setValue('2')
    await clickText(addDialog, 'Add user')
    await flushPromises()
    expect(mocks.createUser).toHaveBeenCalledWith(expect.objectContaining({ name: 'New User', role_id: 2 }))

    await wrapper.get('[aria-label="Edit Aung Min"]').trigger('click')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(wrapper.get('.users-panel').text()).toContain('Back to Users')
    await wrapper.get('#staff-name').setValue('Aung Min Tun')
    await wrapper.get('#edit-user-status').trigger('click')
    await clickText(wrapper.find('.users-inline-form'), 'Save changes')
    await flushPromises()
    expect(mocks.updateUser).toHaveBeenCalledWith(1, expect.objectContaining({ name: 'Aung Min Tun', is_active: false }))
    expect(wrapper.find('.users-inline-form').exists()).toBe(false)

    await wrapper.get('[aria-label="Manage access for Aung Min"]').trigger('click')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    expect(wrapper.get('.users-panel').text()).toContain('Manage access · Aung Min')
    await wrapper.get('#access-role').setValue('2')
    await wrapper.get('#manage-access-status').trigger('click')
    await clickText(wrapper.find('.users-inline-form'), 'Save access')
    await flushPromises()
    expect(mocks.updateAccess).toHaveBeenCalledWith(1, { role_id: 2, is_active: false })
    expect(wrapper.find('.users-inline-form').exists()).toBe(false)
    wrapper.unmount()
  })

  it('marks newly registered inactive users as pending administrator approval', async () => {
    mocks.list.mockResolvedValueOnce({
      data: [{ ...user, role_id: null, role: null, is_active: false, approved_at: null }],
      meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 },
    })
    const wrapper = page()
    await flushPromises()
    expect(wrapper.text()).toContain('Pending Approval')
    expect(wrapper.text()).toContain('No role')
    wrapper.unmount()
  })

  it('creates a role with selected permissions', async () => {
    const wrapper = page()
    await flushPromises()
    await clickText(wrapper, 'Roles & Permissions')
    await clickText(wrapper, 'Create role')
    await wrapper.get('#role-name').setValue('Dispatch')
    expect((wrapper.get('#role-name').element as HTMLInputElement).value).toBe('Dispatch')
    await wrapper.get('input[value="users.view"]').setValue(true)
    expect((wrapper.get('#role-name').element as HTMLInputElement).value).toBe('Dispatch')
    await clickText(wrapper.find('.permission-actions'), 'Create role')
    await flushPromises()
    expect(mocks.createRole).toHaveBeenCalledWith({ name: 'Dispatch', permissions: ['users.view'] })
    wrapper.unmount()
  })

  it('shows one view-only permission toggle for each module', async () => {
    const wrapper = page()
    await flushPromises()
    await clickText(wrapper, 'Roles & Permissions')
    expect(wrapper.get('.role-workspace').text()).toContain('Staff')
    expect(wrapper.get('.permission-grid').classes()).toContain('permission-grid')
    expect(wrapper.findAll('.permission-grid-item')).toHaveLength(2)
    expect(wrapper.findAll('.permission-grid-item input[type="checkbox"]')).toHaveLength(2)
    expect(wrapper.get('.permission-grid-frame').attributes('aria-label')).toBe('Module permissions')
    expect(wrapper.get('.permission-grid-frame').attributes('tabindex')).toBeUndefined()
    expect(wrapper.get('.permission-grid').text()).not.toMatch(/Create|Edit|Delete/)
    wrapper.unmount()
  })

  it('uses the sidebar icon for every module permission row', async () => {
    const iconByPermission = {
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
    mocks.roles.mockResolvedValueOnce({ data: [role], permissions: Object.fromEntries(
      Object.entries(iconByPermission).map(([key]) => [key, key]),
    ) })
    const wrapper = page()
    await flushPromises()
    await clickText(wrapper, 'Roles & Permissions')

    const rows = wrapper.findAll('.permission-grid-item')
    expect(rows).toHaveLength(Object.keys(iconByPermission).length)
    Object.values(iconByPermission).forEach((icon, index) => {
      expect(rows[index]!.get('.permission-module-icon i').classes()).toContain(icon)
    })
    wrapper.unmount()
  })

  it('lets an editable role toggle a module by clicking its row', async () => {
    const wrapper = page()
    await flushPromises()
    await clickText(wrapper, 'Roles & Permissions')

    const usersAccessRow = wrapper
      .findAll('.permission-grid-item')
      .find((row) => row.text().includes('Users & Access'))
    expect(usersAccessRow).toBeDefined()
    const checkbox = usersAccessRow!.get('input[type="checkbox"]')
    expect((checkbox.element as HTMLInputElement).checked).toBe(false)
    await usersAccessRow!.trigger('click')
    await flushPromises()
    expect((checkbox.element as HTMLInputElement).checked).toBe(true)
    wrapper.unmount()
  })

  it('locks module toggles for Administrator and explains why', async () => {
    mocks.roles.mockResolvedValueOnce({
      data: [{ ...role, name: 'Administrator', slug: 'administrator' }],
      permissions: { 'users.view': 'Users & Access', 'dashboard.view': 'Dashboard' },
    })
    const wrapper = page()
    await flushPromises()
    await clickText(wrapper, 'Roles & Permissions')

    expect(wrapper.text()).toContain('Administrator access is required for every module')
    const usersAccessRow = wrapper
      .findAll('.permission-grid-item')
      .find((row) => row.text().includes('Users & Access'))
    expect((usersAccessRow!.get('input').element as HTMLInputElement).disabled).toBe(true)
    wrapper.unmount()
  })

  it('returns to the staff table from edit and access forms without opening a dialog', async () => {
    const wrapper = page()
    await flushPromises()

    await wrapper.get('[aria-label="Edit Aung Min"]').trigger('click')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    await clickText(wrapper, 'Back to Users')
    expect(wrapper.find('.users-inline-form').exists()).toBe(false)
    expect(wrapper.text()).toContain('Aung Min')
    expect(wrapper.find('.users-action-edit').exists()).toBe(true)
    expect(wrapper.find('.users-action-access').exists()).toBe(true)
    expect(wrapper.find('.users-action-delete').exists()).toBe(false)

    await wrapper.get('[aria-label="Manage access for Aung Min"]').trigger('click')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
    await clickText(wrapper, 'Cancel')
    expect(wrapper.find('.users-inline-form').exists()).toBe(false)
    expect(wrapper.text()).toContain('Aung Min')
    expect(mocks.updateUser).not.toHaveBeenCalled()
    expect(mocks.updateAccess).not.toHaveBeenCalled()
    wrapper.unmount()
  })

  it('shows retry feedback when loading users fails', async () => {
    mocks.list.mockRejectedValueOnce(new Error('Users could not be loaded.'))
    const wrapper = page()
    await flushPromises()
    expect(wrapper.text()).toContain('Users could not be loaded.')
    await clickText(wrapper, 'Try again')
    await flushPromises()
    expect(wrapper.text()).toContain('Aung Min')
    wrapper.unmount()
  })
})
