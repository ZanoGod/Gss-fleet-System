import { expect, test, type Page } from '@playwright/test'

test.skip(!process.env.GSS_LIVE_QA_URL, 'Requires the isolated QA application, never production.')
test.use({ baseURL: process.env.GSS_LIVE_QA_URL })

async function create(page: Page, url: string, body: Record<string, unknown>) {
  return page.evaluate(
    async ({ url, body }) => {
      const token =
        document.cookie
          .split('; ')
          .find((v) => v.startsWith('XSRF-TOKEN='))
          ?.split('=')
          .slice(1)
          .join('=') || ''
      const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-XSRF-TOKEN': decodeURIComponent(token),
        },
        body: JSON.stringify(body),
      })
      if (!response.ok) throw new Error(`QA fixture request failed: ${response.status}`)
      return response.json()
    },
    { url, body },
  )
}

test('daily and monthly drivers persist, display dated assignments, and protect booking history', async ({
  page,
}) => {
  const runtimeErrors: string[] = []
  page.on('pageerror', (error) => runtimeErrors.push(error.message))
  const suffix = Date.now().toString()
  const name = `Daily Driver ${suffix}`,
    monthlyName = `Monthly Driver ${suffix}`
  await page.goto('/login')
  await page.getByLabel('Username or email').fill('modules.qa')
  await page.getByLabel('Password', { exact: true }).fill('GssModulesQA2026!')
  await page.getByRole('button', { name: 'Secure Login' }).click()
  await expect(page).toHaveURL(/dashboard/)
  await page
    .getByRole('navigation', { name: 'Primary navigation' })
    .getByRole('link', { name: 'Drivers', exact: true })
    .click()
  await page.getByRole('link', { name: 'Add driver', exact: true }).click()
  await page.getByRole('textbox', { name: 'Driver Name', exact: true }).fill(name)
  await page.getByRole('textbox', { name: 'Phone Number', exact: true }).fill('0912345678')
  await page.getByRole('textbox', { name: 'Note', exact: true }).fill('Airport service')
  await page.getByRole('button', { name: 'Save Driver', exact: true }).click()
  await expect(page).toHaveURL(/notice=saved/)
  await page.getByRole('searchbox', { name: 'Search', exact: true }).fill(name)
  await page.getByRole('button', { name: 'Filter', exact: true }).click()
  await expect(page).toHaveURL(/search=/)
  await expect(page.locator('tbody tr')).toHaveCount(1)
  await page.reload()
  await expect(page.getByRole('cell', { name, exact: true })).toBeVisible()
  await page.getByRole('link', { name: 'View', exact: true }).click()
  await expect(page).toHaveURL(/\/drivers\/\d+\?/)
  const dailyUrl = page.url(),
    dailyId = Number(new URL(dailyUrl).pathname.split('/').pop())
  await page.getByRole('link', { name: 'Edit Driver', exact: true }).click()
  await page.getByRole('textbox', { name: 'Phone Number', exact: true }).fill('0998765432')
  await page.getByRole('button', { name: 'Save Driver', exact: true }).click()
  await expect(page).toHaveURL(/notice=saved/)

  const op = await create(page, '/api/operators', {
    full_name: `Dispatcher ${suffix}`,
    operator_status: 'Active',
  })
  await page.goto('/daily-bookings/create')
  await page.getByLabel('Guest / company').fill(`Booked Company ${suffix}`)
  await page.getByLabel('Operator', { exact: true }).selectOption(String(op.data.id))
  await page.getByLabel('Start date').fill('2026-11-10')
  const lookup = page.waitForResponse(
    (r) => r.url().includes('/api/drivers/options?') && r.status() === 200,
  )
  await page.getByLabel('End date').fill('2026-11-12')
  await lookup
  await page.getByLabel('Driver', { exact: true }).selectOption(String(dailyId))
  await page.getByRole('button', { name: 'Save booking', exact: true }).click()
  await expect(page).toHaveURL(/\/daily-bookings\/\d+$/)
  await expect(page.getByText(name, { exact: true }).first()).toBeVisible()
  await page.goto(`/drivers?search=${encodeURIComponent(name)}&month=2026-11`)
  await expect(
    page.getByRole('link', { name: `Booked Company ${suffix}`, exact: true }),
  ).toBeVisible()
  await page.getByRole('button', { name: 'Next assignment month' }).click()
  await expect(page).toHaveURL(/month=2026-12/)
  await expect(page.getByText('No assignments', { exact: true })).toBeVisible()
  await page.getByRole('button', { name: 'Previous assignment month' }).click()
  await expect(page).toHaveURL(/month=2026-11/)
  await expect(
    page.getByRole('link', { name: `Booked Company ${suffix}`, exact: true }),
  ).toBeVisible()
  await page.screenshot({ path: 'test-results/drivers-desktop.png', fullPage: false })
  await page.goto(dailyUrl)
  await expect(page.getByText('0998765432', { exact: true })).toBeVisible()
  await page.getByRole('button', { name: 'Delete', exact: true }).click()
  await page.getByRole('button', { name: 'Delete record', exact: true }).click()
  await expect(page.getByRole('alert')).toContainText('history')

  await page.goto('/drivers?resource_type=monthly')
  await page.getByRole('link', { name: 'Add driver', exact: true }).click()
  await page.getByRole('textbox', { name: 'Driver Name', exact: true }).fill(monthlyName)
  await page.getByRole('textbox', { name: 'Phone Number', exact: true }).fill('0955555555')
  await page.getByRole('button', { name: 'Save Driver', exact: true }).click()
  await expect(page).toHaveURL(/resource_type=monthly.*notice=saved/)
  await page.getByRole('searchbox', { name: 'Search', exact: true }).fill(monthlyName)
  await page.getByRole('button', { name: 'Filter', exact: true }).click()
  await expect(page).toHaveURL(/search=/)
  await expect(page.locator('tbody tr')).toHaveCount(1)
  await page.getByRole('link', { name: 'View', exact: true }).click()
  await expect(page).toHaveURL(/\/drivers\/\d+\?resource_type=monthly/)
  const monthlyId = Number(new URL(page.url()).pathname.split('/').pop())
  const customer = await create(page, '/api/customers', {
    company_name: `Monthly Customer ${suffix}`,
    service_hour: '8 hours',
  })
  await create(page, '/api/monthly-contracts', {
    customer_id: customer.data.id,
    customer_name: `Monthly Customer ${suffix}`,
    assignment_type: 'Driver Only',
    driver_id: monthlyId,
    start_date: '2026-11-10',
    end_date: '2026-11-30',
    monthly_rate: '100',
    rental_currency: 'MMK',
    rental_status: 'Driver only',
    insurance_price: '0',
    status: 'Active',
  })
  await page.goto(
    `/drivers?resource_type=monthly&search=${encodeURIComponent(monthlyName)}&month=2026-11`,
  )
  await expect(
    page.getByRole('link', { name: `Monthly Customer ${suffix}`, exact: true }),
  ).toBeVisible()
  await page.setViewportSize({ width: 390, height: 844 })
  await expect(page.locator('.app-main')).toHaveCSS('margin-left', '0px')
  await expect(page.locator('.sidebar')).toHaveCSS('visibility', 'hidden')
  const filterButton = await page.getByRole('button', { name: 'Filter', exact: true }).boundingBox()
  expect(filterButton?.height).toBeLessThan(60)
  await page.screenshot({ path: 'test-results/drivers-mobile.png', fullPage: true })
  await page.getByRole('link', { name: 'View', exact: true }).click()
  await page.getByRole('button', { name: 'Delete', exact: true }).click()
  await page.getByRole('button', { name: 'Delete record', exact: true }).click()
  await expect(page.getByRole('alert')).toContainText('history')

  await page.goto('/drivers/create')
  await page.getByRole('textbox', { name: 'Driver Name', exact: true }).fill(`Unused ${suffix}`)
  await page.getByRole('textbox', { name: 'Phone Number', exact: true }).fill('0966666666')
  await page.getByRole('button', { name: 'Save Driver', exact: true }).click()
  await expect(page).toHaveURL(/notice=saved/)
  await page.getByRole('searchbox', { name: 'Search', exact: true }).fill(`Unused ${suffix}`)
  await page.getByRole('button', { name: 'Filter', exact: true }).click()
  await expect(page).toHaveURL(/search=/)
  await expect(page.locator('tbody tr')).toHaveCount(1)
  await page.getByRole('link', { name: 'View', exact: true }).click()
  await page.getByRole('button', { name: 'Delete', exact: true }).click()
  await page.getByRole('button', { name: 'Delete record', exact: true }).click()
  await expect(page.getByText('Driver deleted.', { exact: true })).toBeVisible()
  expect(runtimeErrors).toEqual([])
})
