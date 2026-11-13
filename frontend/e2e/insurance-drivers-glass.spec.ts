import { expect, test } from '@playwright/test'

// API fixtures keep UI verification deterministic and avoid changing live fleet records.
test.beforeEach(async ({ page }) => {
  await page.route('**/api/**', async (route) => {
    const url = new URL(route.request().url())
    const monthly = url.searchParams.get('resource_type') === 'monthly'
    const responses: Record<string, unknown> = {
      '/api/user': { success: true, user: { id: 7, username: 'tester', name: 'Test User' } },
      '/api/drivers/summary': {
        data: { total: monthly ? 60 : 30, available: 10, assigned: 9, leave: 6, inactive: 5 },
      },
      '/api/drivers/options': {
        data: { statuses: ['Available', 'Assigned', 'Leave', 'Inactive'], drivers: [] },
      },
      '/api/insurance/dashboard': {
        data: {
          total_policies: 40,
          active_policies: 30,
          expiring_soon_policies: 8,
          claims: { open: 3 },
        },
      },
    }
    await route.fulfill({
      json: responses[url.pathname] ?? {
        data: [],
        meta: { total: 0, current_page: 1, last_page: 1 },
      },
    })
  })
})

for (const width of [1280, 390]) {
  test(`register flows at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 })
    await page.goto('/insurance')
    await expect(page.getByText('No policies yet', { exact: true })).toBeVisible()
    const policies = page.getByRole('button', { name: 'Policies', exact: true })
    await policies.focus()
    await policies.press('ArrowRight')
    await expect(page.getByRole('button', { name: 'Claims', exact: true })).toBeFocused()
    await page.keyboard.press('Enter')
    await expect(page.getByRole('button', { name: 'New claim', exact: true })).toBeDisabled()
    await page.getByRole('button', { name: 'Providers', exact: true }).click()
    await page.getByRole('button', { name: 'Add provider', exact: true }).click()
    await expect(page.getByLabel('Provider name', { exact: true })).toBeVisible()
    await page.getByRole('button', { name: 'Cancel', exact: true }).click()
    await policies.click()
    await page.getByRole('searchbox', { name: 'Search policies' }).fill('missing')
    await page.getByRole('button', { name: 'Search', exact: true }).click()
    await expect(page.getByText('No matching policies', { exact: true })).toBeVisible()
    await page.getByRole('button', { name: 'Clear filters', exact: true }).click()
    await expect(page.getByText('No policies yet', { exact: true })).toBeVisible()
    await page.screenshot({ path: `test-results/insurance-${width}.png`, fullPage: true })
    await page.getByRole('link', { name: 'Add policy', exact: true }).click()
    await expect(page).toHaveURL(/insurance\/create/)
    for (const group of ['daily', 'monthly']) {
      await page.goto('/drivers')
      await page
        .getByRole('link', {
          name: group === 'daily' ? 'Daily booking drivers' : 'Monthly contract drivers',
          exact: true,
        })
        .click()
      await expect(page.locator('.status-card__value').first()).toHaveText(
        group === 'daily' ? '30' : '60',
      )
      await page.getByRole('searchbox', { name: 'Search', exact: true }).fill('test')
      await page.getByRole('button', { name: 'Filter', exact: true }).click()
      await expect(page).toHaveURL(/search=test/)
      const box = await page.locator('.glass-segments').boundingBox()
      expect(box!.x + box!.width).toBeLessThanOrEqual(width)
      await page.screenshot({ path: `test-results/drivers-${group}-${width}.png`, fullPage: true })
      await page.getByRole('link', { name: 'Add driver', exact: true }).click()
      await expect(page).toHaveURL(new RegExp(`drivers/create\\?resource_type=${group}`))
      await expect(page.getByRole('textbox', { name: 'Driver Name', exact: true })).toBeVisible()
    }
  })
}
for (const width of [1280, 390]) {
  test(`populated policies, claim dialog and retry at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 900 })
    let fail = false
    await page.route('**/api/insurance/policies**', (route) =>
      route.fulfill(
        fail
          ? { status: 503, json: { message: 'Unavailable' } }
          : {
              json: {
                data: [
                  {
                    id: 1,
                    policy_number: 'POL-001',
                    insurance_type: 'comprehensive',
                    status: 'active',
                    currency: 'MMK',
                    premium_amount: '100000',
                    start_date: '2026-01-01',
                    expiration_date: '2027-01-01',
                    provider: { name: 'Test provider' },
                    vehicle: { plate_no: 'AB-1234', fleet: 'daily' },
                  },
                ],
              },
            },
      ),
    )
    await page.goto('/insurance')
    await expect(page.getByRole('link', { name: 'POL-001', exact: true })).toBeVisible()
    await page.getByRole('button', { name: 'Claims', exact: true }).click()
    await page.getByRole('button', { name: 'New claim', exact: true }).click()
    await expect(page.getByLabel('Policy', { exact: true })).toHaveValue('1')
    await page.getByRole('button', { name: 'Cancel', exact: true }).click()
    await page.getByRole('button', { name: 'Policies', exact: true }).click()
    fail = true
    await page.getByRole('button', { name: 'Search', exact: true }).click()
    await expect(
      page.getByRole('heading', { name: 'Unable to load insurance records' }),
    ).toBeVisible()
    fail = false
    await page.getByRole('button', { name: 'Retry', exact: true }).click()
    await expect(page.getByRole('link', { name: 'POL-001', exact: true })).toBeVisible()
  })
}

for (const group of ['daily', 'monthly']) {
  test(`populated ${group} driver rows and save request`, async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 })
    await page.route('**/api/drivers?**', (route) =>
      route.fulfill({
        json: {
          data: [
            {
              id: 1,
              full_name: 'Test driver',
              phone_number: '0912345678',
              driver_status: 'Available',
              assignments: [
                {
                  id: 7,
                  name: 'Test assignment',
                  start_date: '2026-11-01',
                  end_date: '2026-11-30',
                  status: 'Active',
                },
              ],
            },
          ],
          meta: { total: 1, current_page: 1, last_page: 1 },
        },
      }),
    )
    await page.goto(`/drivers?resource_type=${group}`)
    await expect(page.getByRole('cell', { name: 'Test driver', exact: true })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Test assignment', exact: true })).toHaveAttribute(
      'href',
      group === 'daily' ? '/daily-bookings/7' : '/monthly-contracts/7',
    )
    await page.getByRole('button', { name: 'Next assignment month' }).click()
    await expect(page).toHaveURL(/month=/)
    await page.screenshot({ path: `test-results/drivers-${group}-populated.png`, fullPage: true })
    await page.setViewportSize({ width: 1280, height: 900 })
    await expect(page.getByRole('cell', { name: 'Test driver', exact: true })).toBeVisible()
    await page.screenshot({ path: `test-results/drivers-${group}-populated-desktop.png`, fullPage: true })
    await page.getByRole('link', { name: 'Add driver', exact: true }).click()
    await page.getByRole('textbox', { name: 'Driver Name', exact: true }).fill('New driver')
    await page.getByRole('textbox', { name: 'Phone Number', exact: true }).fill('0912345678')
    const request = page.waitForRequest(
      (r) => r.method() === 'POST' && r.url().includes('/api/drivers'),
    )
    await page.getByRole('button', { name: 'Save Driver', exact: true }).click()
    const saved = await request
    expect(saved.postDataJSON()).toMatchObject({ full_name: 'New driver' })
    expect(new URL(saved.url()).searchParams.get('resource_type')).toBe(group)
    await expect(page).toHaveURL(new RegExp(`/drivers\\?resource_type=${group}$`))
  })
}
