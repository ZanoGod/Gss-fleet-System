import { expect, test, type Page } from '@playwright/test'

const customer = {
  id: 1,
  company_name: 'Test customer',
  guest_name: 'Test guest',
  contact_person: 'Contact',
  contact_phone: '0912345678',
  contact_email: 'test@example.com',
  customer_address: 'Yangon',
  off_day: null,
  service_hour: null,
  remark: null,
  customer_status: 'Active',
  contract_count: 0,
  active_contract_count: 0,
  latest_expiry: null,
  can_delete: true,
  contracts: [],
}

const booking = {
  id: 1,
  guest_company_name: 'Test guest',
  car_id: null,
  car: null,
  custom_car_name: null,
  secondary_car_id: null,
  secondary_car: null,
  driver_id: null,
  driver: null,
  custom_driver_name: null,
  operator_id: null,
  operator_name: 'Test operator',
  operator: null,
  even_odd: null,
  start_date: '2026-11-13',
  end_date: '2026-11-14',
  status: 'Pending',
  remark: null,
}

async function mockApi(page: Page) {
  await page.route('**/api/**', async (route) => {
    const url = new URL(route.request().url())
    const pathname = url.pathname
    let body: unknown = { data: {} }

    if (pathname === '/api/user') body = { user: { id: 7, name: 'Tester', email: 'test@example.com' } }
    else if (pathname === '/api/daily-bookings/1') body = { data: booking }
    else if (pathname === '/api/customers/1') body = { data: customer }
    else if (pathname === '/api/drivers/options') body = { data: { statuses: ['Available'] } }
    else if (pathname === '/api/cars/1') {
      body = {
        data: {
          id: 1,
          car_type: 'Sedan',
          plate_no: 'ABC-1234',
          model_name: 'Test model',
          seat_capacity: 4,
          availability_status: 'Available',
          effective_status: 'Available',
          assignments: [],
          maintenances: [],
          insurance_policies: [],
        },
      }
    } else if (pathname === '/api/drivers/1') {
      body = {
        data: {
          id: 1,
          full_name: 'Test driver',
          phone_number: '0912345678',
          driver_status: 'Available',
          assignments: [],
        },
      }
    } else if (pathname === '/api/operators/1') {
      body = {
        data: {
          id: 1,
          full_name: 'Test operator',
          phone_number: '0912345678',
          operator_status: 'Active',
          note: '',
          assignments: [],
        },
      }
    }

    await route.fulfill({ json: body })
  })
}

const cases = [
  { name: 'daily booking', path: '/daily-bookings/1', list: '/daily-bookings', edit: '/daily-bookings/1/edit' },
  { name: 'customer', path: '/customers/1', list: '/customers', edit: '/customers/1/edit' },
  { name: 'daily vehicle', path: '/fleet/1?resource_type=daily', list: '/fleet?resource_type=daily', edit: '/fleet/1/edit?resource_type=daily' },
  { name: 'monthly vehicle', path: '/fleet/1?resource_type=monthly', list: '/fleet?resource_type=monthly', edit: '/fleet/1/edit?resource_type=monthly' },
  { name: 'daily driver', path: '/drivers/1?resource_type=daily', list: '/drivers?resource_type=daily', edit: '/drivers/1/edit?resource_type=daily' },
  { name: 'monthly driver', path: '/drivers/1?resource_type=monthly', list: '/drivers?resource_type=monthly', edit: '/drivers/1/edit?resource_type=monthly' },
  { name: 'operator', path: '/operators/1', list: '/operators', edit: '/operators/1/edit' },
]

for (const width of [1280, 390]) {
  for (const view of cases) {
    test(`${view.name} detail actions at ${width}px`, async ({ page }) => {
      await mockApi(page)
      await page.setViewportSize({ width, height: 900 })
      await page.goto(view.path)

      const toolbar = page.locator('.record-detail-action-bar')
      await expect(toolbar).toBeVisible()
      const actions = toolbar.locator('.record-action')
      await expect(actions).toHaveCount(3)
      await expect(actions.nth(0)).toHaveAttribute('href', view.list)
      await expect(actions.nth(1)).toHaveAttribute('href', view.edit)
      await expect(actions.nth(0)).toContainText(/Back|All/)
      await expect(actions.nth(1)).toContainText('Edit')
      await expect(actions.nth(2)).toContainText('Delete')

      const bar = await toolbar.boundingBox()
      const back = await actions.nth(0).boundingBox()
      const edit = await actions.nth(1).boundingBox()
      const deleteAction = await actions.nth(2).boundingBox()
      expect(bar && back && edit && deleteAction).toBeTruthy()
      if (width > 575) {
        expect(Math.abs(back!.x - bar!.x)).toBeLessThan(2)
        expect(edit!.x).toBeGreaterThan(back!.x)
        expect(deleteAction!.x).toBeGreaterThan(edit!.x)
      }
      expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width)

      if (view.name === 'daily booking') {
        page.once('dialog', (dialog) => dialog.dismiss())
        await actions.nth(2).click()
      } else if (view.name === 'customer') {
        await actions.nth(2).click()
        await expect(page.getByRole('alert').filter({ hasText: 'Delete Test customer?' })).toBeVisible()
      } else {
        await actions.nth(2).click()
        await expect(page.getByRole('dialog')).toBeVisible()
        await page.getByRole('button', { name: 'Keep record' }).click()
      }

      await page.screenshot({
        path: `test-results/detail-actions-${view.name.replaceAll(' ', '-')}-${width}.png`,
        fullPage: true,
      })
    })
  }
}
