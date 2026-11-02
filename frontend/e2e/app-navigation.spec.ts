import { expect, test } from '@playwright/test'

const protectedPaths = [
  ['/dashboard', 'Dashboard'],
  ['/daily-bookings', 'Daily Bookings'],
  ['/daily-bookings/create', 'Create Daily Booking'],
  ['/daily-bookings/42', 'Booking Details'],
  ['/daily-bookings/42/edit', 'Edit Daily Booking'],
  ['/fleet', 'Vehicles'],
  ['/fleet/create', 'Create Vehicle'],
  ['/fleet/42', 'Vehicle details'],
  ['/fleet/42/edit', 'Edit Vehicle'],
  ['/customers', 'Customers'],
  ['/customers/create', 'Create Customer'],
  ['/customers/42', 'Customer details'],
  ['/customers/42/edit', 'Edit Customer'],
  ['/drivers', 'Drivers'],
  ['/drivers/create', 'Create Driver'],
  ['/drivers/42', 'Driver details'],
  ['/drivers/42/edit', 'Edit Driver'],
  ['/operators', 'Operators'],
  ['/operators/create', 'Create Operator'],
  ['/operators/42', 'Operator details'],
  ['/operators/42/edit', 'Edit Operator'],
  ['/insurance', 'Insurance Management'],
  ['/insurance/create', 'Create Insurance Policy'],
  ['/insurance/42', 'Insurance Policy'],
  ['/insurance/42/edit', 'Edit Insurance Policy'],
  ['/maintenance', 'Maintenances'],
  ['/maintenance/create', 'Create Maintenance'],
  ['/maintenance/42', 'Maintenance details'],
  ['/maintenance/42/edit', 'Edit Maintenance'],
  ['/monthly-contracts', 'Monthly Contracts'],
  ['/monthly-contracts/create', 'Create Monthly Contract'],
  ['/monthly-contracts/42', 'Monthly Contract Details'],
  ['/monthly-contracts/42/edit', 'Edit Monthly Contract'],
  ['/reports', 'Reports'],
] as const

// Navigation must remain usable even when a module's data service is unavailable.
test.beforeEach(async ({ page }) => {
  await page.route('**/api/**', (route) =>
    route.fulfill({
      status: 503,
      json: { message: 'Data service unavailable in navigation smoke test.' },
    }),
  )
})

test('protected routes redirect to login and keep the login outside the app shell', async ({
  page,
}) => {
  await page.route('**/api/user', (route) =>
    route.fulfill({
      status: 401,
      contentType: 'application/json',
      body: JSON.stringify({ message: 'Unauthenticated.' }),
    }),
  )
  await page.goto('/customers')
  await expect(page).toHaveURL(/\/login/)
  await expect(page.getByRole('heading', { name: 'Fleet Management' })).toBeVisible()
  await expect(page.getByRole('navigation', { name: 'Primary navigation' })).toHaveCount(0)
  await expect(page.getByLabel('Username or email')).toBeVisible()
})

test('every module route loads directly inside the shared authenticated layout', async ({
  page,
}) => {
  await page.route('**/api/user', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        user: { id: 7, name: 'Test User', username: 'tester', email: 'tester@example.test' },
      }),
    }),
  )
  for (const [path, title] of protectedPaths) {
    await page.goto(path)
    await expect(page.getByRole('heading', { level: 1, name: title })).toBeVisible()
    await expect(page.getByRole('navigation', { name: 'Primary navigation' })).toBeVisible()
    await expect(page.getByText('tester', { exact: true })).toBeVisible()
  }
})

test('sidebar navigation opens the daily bookings list', async ({ page }) => {
  await page.route('**/api/user', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        user: { id: 7, name: 'Test User', username: 'tester', email: 'tester@example.test' },
      }),
    }),
  )
  await page.goto('/dashboard')
  await page
    .getByRole('navigation', { name: 'Primary navigation' })
    .getByRole('link', { name: 'Daily Bookings', exact: true })
    .click()
  await expect(page).toHaveURL(/\/daily-bookings$/)
  await expect(page.getByRole('heading', { level: 1, name: 'Daily Bookings' })).toBeVisible()
})

test('reloading /login with a valid server session redirects to the dashboard', async ({
  page,
}) => {
  await page.route('**/api/user', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        user: { id: 7, name: 'Test User', username: 'tester', email: 'tester@example.test' },
      }),
    }),
  )
  const response = await page.goto('/login')
  expect(response?.status()).toBe(200)
  await expect(page).toHaveURL(/\/dashboard$/)
  await expect(page.getByRole('heading', { level: 1, name: 'Dashboard' })).toBeVisible()
})
