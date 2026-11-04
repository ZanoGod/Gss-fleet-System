import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusCard from './StatusCard.vue'
import PageHeader from './PageHeader.vue'
import { dashboardMetrics } from '@/modules/dashboard/overview'
import { fleetMetrics } from '@/modules/fleet/overview'
import { driversMetrics } from '@/modules/drivers/overview'
import { operatorsMetrics } from '@/modules/operators/overview'
import { customersMetrics } from '@/modules/customers/overview'
import { contractMetrics } from '@/modules/monthly-contracts/overview'
import { insuranceMetrics } from '@/modules/insurance/overview'
import { maintenanceMetrics } from '@/modules/maintenance/overview'

describe('page-specific summaries', () => {
  it('maps independent resource fields and respects the vehicle pool', () => {
    const cars = { total: 20, available: 8, assigned: 7, maintenance: 3, inactive: 2 }
    expect(fleetMetrics(cars, 'daily').map((item) => item.value)).toEqual([20, 8, 7, 3])
    expect(fleetMetrics(cars, 'monthly').map((item) => item.value)).toEqual([20, 8, 7, 3, 2])
    expect(
      driversMetrics({ total: 30, available: 10, assigned: 9, leave: 6, inactive: 5 }).map(
        (item) => item.value,
      ),
    ).toEqual([30, 10, 9, 6, 5])
    expect(
      operatorsMetrics({ total: 12, active: 9, inactive: 3 }).map((item) => item.value),
    ).toEqual([12, 9, 3])
    expect(
      customersMetrics({ total: 40, active: 35, with_contracts: 17 }).map((item) => item.value),
    ).toEqual([40, 35, 17])
  })

  it('maps dashboard and contract states without substituting table row counts', () => {
    expect(
      dashboardMetrics({
        total_bookings: 100,
        available_cars: 9,
        available_drivers: 8,
        ongoing_bookings: 7,
        expiring_contracts: 6,
        recent_bookings: [],
      }).map((item) => item.value),
    ).toEqual([100, 9, 8, 7, 6])
    expect(
      contractMetrics({
        total: 25,
        statuses: { Draft: 1, Active: 9, Expired: 7, Renewed: 5, Cancelled: 3 },
      }).map((item) => item.value),
    ).toEqual([25, 1, 9, 7, 5, 3])
  })

  it('uses insurance dashboard totals and open claims rather than loaded policies', () => {
    const summary = {
      total_policies: 40,
      active_policies: 30,
      expiring_soon_policies: 8,
      claims: { open: 3 },
    }
    expect(
      insuranceMetrics(summary as Parameters<typeof insuranceMetrics>[0]).map((item) => item.value),
    ).toEqual([40, 30, 8, 3])
  })

  it('compares maintenance months and keeps currencies separate', () => {
    const summary = {
      due_this_week: 8,
      overdue: 3,
      in_workshop: 4,
      completed_this_month: 5,
      completed_previous_month: 7,
      spend_this_month: [
        { currency: 'USD', total: '100.50' },
        { currency: 'MMK', total: '25000' },
      ],
      month_label: 'November',
      previous_month_label: 'October',
    }
    const cards = maintenanceMetrics(summary)
    expect(cards.slice(0, 3).map((item) => item.value)).toEqual([8, 4, 5])
    expect(cards[0]?.description).toContain('3 services overdue')
    expect(cards[2]?.description).toBe('↓ 2 vs October')
    expect(cards[3]?.value).toContain('USD 100.5')
    expect(cards[3]?.value).toContain('MMK')
    expect(maintenanceMetrics({ ...summary, spend_this_month: [] })[3]?.value).toBe(0)
  })

  it('renders unavailable, loading and zero as distinct states', async () => {
    const metric = dashboardMetrics(null)[0]!
    const wrapper = mount(StatusCard, { props: metric })
    expect(wrapper.text()).toContain('Data unavailable')
    await wrapper.setProps({ loading: true })
    expect(wrapper.text()).toContain('Loading summary…')
    expect(wrapper.attributes('aria-busy')).toBe('true')
    await wrapper.setProps({ loading: false, value: 0 })
    expect(wrapper.find('strong').text()).toBe('0')
    expect(wrapper.text()).toContain(metric.description)
  })

  it('allows header copy and actions to be customized', () => {
    const wrapper = mount(PageHeader, {
      props: { title: 'Custom title', description: 'Custom subtitle', icon: 'bi-people' },
      slots: { default: '<button>Add</button>' },
    })
    expect(wrapper.find('h2').text()).toBe('Custom title')
    expect(wrapper.find('p').text()).toBe('Custom subtitle')
    expect(wrapper.find('button').text()).toBe('Add')
  })
})
