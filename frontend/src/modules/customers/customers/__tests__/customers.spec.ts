import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import CustomerForm from '../components/CustomerForm.vue'
import CustomerDetails from '../components/CustomerDetails.vue'
import CustomerListPage from '../pages/CustomerListPage.vue'
import { customerApi } from '../api'
import type { Customer } from '../types'

vi.mock('../api', async importOriginal => {
  const actual = await importOriginal<typeof import('../api')>()
  return { ...actual, customerApi: { list: vi.fn(), summary: vi.fn(), get: vi.fn(), save: vi.fn(), remove: vi.fn() } }
})
const customer: Customer = {
  id: 1, company_name: 'Golden Support', guest_name: null, contact_person: 'Contact', contact_phone: '09-123',
  contact_email: null, customer_address: null, off_day: 'Sunday', service_hour: '8 hours', remark: null,
  customer_status: 'Inactive', contract_count: 0, active_contract_count: 0, latest_expiry: null, can_delete: true, contracts: [],
}
async function router() {
  const value = createRouter({ history: createMemoryHistory(), routes: [
    { path: '/customers', component: { template: '<div />' } },
    { path: '/customers/create', component: { template: '<div />' } },
    { path: '/customers/:id/edit', component: { template: '<div />' } },
    { path: '/monthly-contracts/:id', component: { template: '<div />' } },
    { path: '/monthly-contracts', component: { template: '<div />' } },
  ] })
  await value.push('/customers'); await value.isReady()
  return value
}
const wrappers: { unmount: () => void }[] = []
afterEach(() => { wrappers.splice(0).forEach(wrapper => wrapper.unmount()) })
beforeEach(() => {
  vi.clearAllMocks()
  vi.mocked(customerApi.get).mockResolvedValue({ ...customer })
  vi.mocked(customerApi.save).mockResolvedValue({ ...customer })
  vi.mocked(customerApi.remove).mockResolvedValue(undefined)
  vi.mocked(customerApi.list).mockResolvedValue({ data: [customer], meta: { current_page: 1, last_page: 1, per_page: 10, total: 1 } })
  vi.mocked(customerApi.summary).mockResolvedValue({ total: 1, active: 0, with_contracts: 0 })
})

describe('customer screens', () => {
  it('requires company and service hour before submitting', async () => {
    const wrapper = mount(CustomerForm, { global: { plugins: [await router()] } }); wrappers.push(wrapper)
    await wrapper.get('form').trigger('submit')
    expect(customerApi.save).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Company Name is required.')
    expect(wrapper.text()).toContain('Service Hour is required.')
  })

  it('submits trimmed legacy fields without a status and returns a success notice', async () => {
    const navigation = await router()
    const wrapper = mount(CustomerForm, { global: { plugins: [navigation] } }); wrappers.push(wrapper)
    await wrapper.get('#customer-company_name').setValue('  New Company  ')
    await wrapper.get('#customer-service_hour').setValue(' 8 hours ')
    await wrapper.get('form').trigger('submit'); await flushPromises()
    const input = vi.mocked(customerApi.save).mock.calls[0]?.[0]
    expect(input?.company_name).toBe('New Company')
    expect(input?.service_hour).toBe('8 hours')
    expect(input).not.toHaveProperty('customer_status')
    expect(navigation.currentRoute.value.query.notice).toBe('created')
  })

  it('keeps entered data and displays backend field validation errors', async () => {
    vi.mocked(customerApi.save).mockRejectedValueOnce({ isAxiosError: true, response: { status: 422, data: { errors: { company_name: ['Company already exists.'] } } } })
    const wrapper = mount(CustomerForm, { props: { customer }, global: { plugins: [await router()] } }); wrappers.push(wrapper)
    await wrapper.get('form').trigger('submit'); await flushPromises()
    expect(wrapper.text()).toContain('Company already exists.')
    expect((wrapper.get('#customer-company_name').element as HTMLInputElement).value).toBe('Golden Support')
    expect(wrapper.get('#customer-offday').text()).toContain('Sunday')
  })

  it('changes contract month and requires confirmation before delete', async () => {
    const wrapper = mount(CustomerDetails, { props: { id: 1, month: '2026-11' }, global: { plugins: [await router()] } }); wrappers.push(wrapper)
    await flushPromises()
    await wrapper.get('[aria-label="Next contract month"]').trigger('click'); await flushPromises()
    expect(customerApi.get).toHaveBeenLastCalledWith(1, '2026-12')
    const deleteButton = wrapper.findAll('button').find(button => button.text() === 'Delete Customer')!
    await deleteButton.trigger('click')
    expect(customerApi.remove).not.toHaveBeenCalled()
    await wrapper.get('.btn-danger').trigger('click'); await flushPromises()
    expect(customerApi.remove).toHaveBeenCalledWith(1)
    expect(wrapper.emitted('deleted')).toHaveLength(1)
  })

  it('blocks linked-customer deletion and shows month-specific empty text', async () => {
    vi.mocked(customerApi.get).mockResolvedValue({ ...customer, contract_count: 2, can_delete: false })
    const wrapper = mount(CustomerDetails, { props: { id: 1 }, global: { plugins: [await router()] } }); wrappers.push(wrapper)
    await flushPromises()
    expect(wrapper.text()).toContain('No contracts for the selected month.')
    const button = wrapper.findAll('button').find(item => item.text() === 'Delete Customer')!
    expect(button.attributes('disabled')).toBeDefined()
  })

  it('shows delete conflicts when a contract was assigned after the profile loaded', async () => {
    vi.mocked(customerApi.remove).mockRejectedValueOnce({ isAxiosError: true, response: { status: 409, data: { code: 'CUSTOMER_HAS_CONTRACTS', message: 'Linked contract prevents deletion.' } } })
    const wrapper = mount(CustomerDetails, { props: { id: 1 }, global: { plugins: [await router()] } }); wrappers.push(wrapper)
    await flushPromises()
    await wrapper.findAll('button').find(button => button.text() === 'Delete Customer')!.trigger('click')
    await wrapper.get('.btn-danger').trigger('click'); await flushPromises()
    expect(wrapper.text()).toContain('Linked contract prevents deletion.')
    expect(wrapper.emitted('deleted')).toBeUndefined()
  })

  it('sends filters, displays global summary, and leaves directory membership unchanged on month navigation', async () => {
    const wrapper = mount(CustomerListPage, { global: { plugins: [await router()] } }); wrappers.push(wrapper)
    await flushPromises()
    expect(wrapper.text()).toContain('Golden Support')
    expect(wrapper.text()).toContain('Total Customers')
    await wrapper.get('#customer-search').setValue('Support')
    await wrapper.get('#customer-assignment').setValue('Driver Only')
    await wrapper.get('form').trigger('submit'); await flushPromises()
    expect(customerApi.list).toHaveBeenLastCalledWith(expect.objectContaining({ search: 'Support', assignment_type: 'Driver Only' }), 1)
    const calls = vi.mocked(customerApi.list).mock.calls.length
    await wrapper.get('[aria-label="Next month"]').trigger('click')
    expect(customerApi.list).toHaveBeenCalledTimes(calls)
    expect(customerApi.summary).toHaveBeenCalledTimes(1)
  })
})
