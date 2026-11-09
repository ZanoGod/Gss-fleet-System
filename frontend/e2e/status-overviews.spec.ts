import { expect, test } from '@playwright/test'

// Deliberately different totals catch accidental reuse of a shared count or current table length.
test.beforeEach(async ({ page }) => {
  await page.route('**/api/**', async (route) => {
    const url = new URL(route.request().url())
    const monthly = url.searchParams.get('resource_type') === 'monthly'
    const responses: Record<string, unknown> = {
      '/api/user': { success: true, user: { id: 7, username: 'tester', name: 'Test User' } },
      '/api/dashboard': {
        data: {
          total_bookings: 101,
          available_cars: 12,
          available_drivers: 9,
          ongoing_bookings: 7,
          expiring_contracts: 3,
          recent_bookings: [],
        },
      },
      '/api/cars/summary': {
        data: { total: monthly ? 50 : 20, available: 8, assigned: 7, maintenance: 5, inactive: 0 },
      },
      '/api/drivers/summary': {
        data: { total: monthly ? 60 : 30, available: 10, assigned: 9, leave: 6, inactive: 5 },
      },
      '/api/drivers/options': {
        data: { statuses: ['Available', 'Assigned', 'Leave', 'Inactive'], drivers: [] },
      },
      '/api/operators/summary': { data: { total: 12, active: 9, inactive: 3 } },
      '/api/customers/summary': { data: { total: 40, active: 35, with_contracts: 17 } },
      '/api/monthly-contracts/options': {
        data: { cars: [], drivers: [], customers: [], operators: [] },
      },
      '/api/monthly-contracts/summary': {
        data: {
          total: 25,
          statuses: { Draft: 1, Active: 9, Expired: 7, Renewed: 5, Cancelled: 3 },
        },
      },
      '/api/insurance/dashboard': {
        data: {
          total_policies: 40,
          active_policies: 30,
          expiring_soon_policies: 8,
          claims: { open: 3 },
        },
      },
      '/api/maintenances/summary': {
        data: {
          dashboard: {
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
          },
        },
      },
      '/api/maintenances/options': { data: { vehicles: [] } },
    }
    await route.fulfill({
      json: responses[url.pathname] ?? {
        data: [],
        meta: { total: 0, current_page: 1, last_page: 1 },
        total: 0,
        current_page: 1,
        last_page: 1,
      },
    })
  })
})

const pages = [
  ['/dashboard', 'Dashboard', ['101', '12', '9', '7', '3']],
  ['/fleet', 'Vehicles', ['20', '8', '7', '5']],
  ['/drivers', 'Drivers', ['30', '10', '9', '6', '5']],
  ['/operators', 'Operators', ['12', '9', '3']],
  ['/customers', 'Customers', ['40', '35', '17']],
  ['/monthly-contracts', 'Monthly Contracts', ['25', '1', '9', '7', '5', '3']],
  ['/insurance', 'Insurance Management', ['40', '30', '8', '3']],
  ['/maintenance', 'Maintenances', ['8', '4', '5', 'USD 100.5 · MMK 25,000']],
] as const

for (const [path, title, values] of pages) {
  test(`${title} shows its own summary and responsive header`, async ({ page }) => {
    const errors: string[] = []
    page.on('pageerror', (error) => errors.push(error.message))
    page.on('console', (message) => {
      if (message.type() === 'error' || message.type() === 'warning') errors.push(message.text())
    })
    await page.goto(path)
    await expect(page.getByRole('heading', { level: 1, name: title, exact: true })).toBeVisible()
    await expect(page.locator('.status-card__value')).toHaveText([...values])
    await expect(page.locator('vite-error-overlay')).toHaveCount(0)
    if (process.env.GSS_QA_SCREENSHOTS && (path === '/dashboard' || path === '/maintenance')) {
      await page.screenshot({
        path: `${process.env.GSS_QA_SCREENSHOTS}/${path.slice(1)}-desktop.png`,
      })
    }
    await page.setViewportSize({ width: 390, height: 844 })
    for (const card of await page.locator('.status-card').all()) {
      const box = await card.boundingBox()
      expect(box).not.toBeNull()
      expect(box!.x).toBeGreaterThanOrEqual(0)
      expect(box!.x + box!.width).toBeLessThanOrEqual(391)
    }
    if (process.env.GSS_QA_SCREENSHOTS && path === '/maintenance') {
      await page.screenshot({ path: `${process.env.GSS_QA_SCREENSHOTS}/maintenance-mobile.png` })
    }
    expect(errors).toEqual([])
  })
}

test('switching daily/monthly pools reloads page-specific cards', async ({ page }) => {
  await page.goto('/fleet')
  await expect(page.locator('.status-card__value').first()).toHaveText('20')
  await page.getByRole('link', { name: 'Monthly contract vehicles', exact: true }).click()
  await expect(page).toHaveURL(/resource_type=monthly/)
  await expect(page.locator('.status-card__value')).toHaveText(['50', '8', '7', '5', '0'])
  await page.goto('/drivers')
  await page.getByRole('link', { name: 'Monthly contract drivers', exact: true }).click()
  await expect(page.locator('.status-card__value').first()).toHaveText('60')
})

test('failed dashboard summary is unavailable and Retry loads real zeros', async ({ page }) => {
  let failed = true
  await page.route('**/api/dashboard', (route) =>
    route.fulfill(
      failed
        ? { status: 503, json: { message: 'Service unavailable' } }
        : {
            json: {
              data: {
                total_bookings: 0,
                available_cars: 0,
                available_drivers: 0,
                ongoing_bookings: 0,
                expiring_contracts: 0,
                recent_bookings: [],
              },
            },
          },
    ),
  )
  await page.goto('/dashboard')
  await expect(page.locator('.status-card__value')).toHaveText(['—', '—', '—', '—', '—'])
  await expect(page.getByRole('alert')).toBeVisible()
  failed = false
  await page.getByRole('button', { name: 'Retry', exact: true }).click()
  await expect(page.locator('.status-card__value')).toHaveText(['0', '0', '0', '0', '0'])
})
