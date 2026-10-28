import { expect, test } from '@playwright/test'

const user = { success: true, user: { id: 7, name: 'Test User', username: 'tester', email: 'tester@example.test' } }

test('creates a date-only confirmed booking and opens its saved details', async ({ page }) => {
  await page.route('**/api/user', route => route.fulfill({ status: 200, json: user }))
  await page.route('**/api/daily-bookings/options', route => route.fulfill({ status: 200, json: { data: {
    cars: [{ id: 1, plate_no: 'BOOK-1', model_name: 'Van', availability_status: 'Available' }],
    drivers: [{ id: 2, full_name: 'Daily Driver', driver_status: 'Available' }],
    operators: [{ id: 3, full_name: 'Daily Operator' }], statuses: ['Pending', 'Confirm', 'Completed', 'Cancelled'],
  } } }))
  const booking = { id: 41, guest_company_name: 'E2E Guest', car_id: 1, car: { id: 1, plate_no: 'BOOK-1', model_name: 'Van' }, custom_car_name: null, secondary_car_id: null, secondary_car: null, driver_id: 2, driver: { id: 2, full_name: 'Daily Driver' }, custom_driver_name: null, operator_id: 3, operator_name: 'Daily Operator', operator: { id: 3, full_name: 'Daily Operator' }, even_odd: 'Even', start_date: '2026-11-10', end_date: '2026-11-12', status: 'Confirm', remark: null }
  let submitted: Record<string, unknown> | undefined
  await page.route('**/api/daily-bookings', async route => {
    if (route.request().method() === 'POST') {
      submitted = route.request().postDataJSON()
      await route.fulfill({ status: 201, json: { data: booking } })
    } else {
      await route.fulfill({ status: 200, json: { data: [], meta: { current_page: 1, last_page: 1, total: 0 }, links: [] } })
    }
  })
  await page.route('**/api/daily-bookings/41', route => route.fulfill({ status: 200, json: { data: booking } }))
  await page.goto('/daily-bookings/create')
  await page.getByLabel('Guest / company').fill('E2E Guest')
  await page.getByLabel('Operator').selectOption('3')
  await page.getByLabel('Primary vehicle').selectOption('1')
  await page.getByLabel('Driver', { exact: true }).selectOption('2')
  await page.getByLabel('Start date').fill('2026-11-10')
  await page.getByLabel('End date').fill('2026-11-12')
  await page.getByRole('button', { name: 'Save booking' }).click()
  await expect(page.getByRole('heading', { name: 'Booking #41 · E2E Guest' })).toBeVisible()
  expect(submitted?.status).toBe('Confirm')
  expect(submitted).not.toHaveProperty('charges')
})

test('renews a monthly contract with the legacy dates and linked history', async ({ page }) => {
  await page.route('**/api/user', route => route.fulfill({ status: 200, json: user }))
  const customer = { id: 5, company_name: 'Monthly Guest', guest_name: 'Guest', contact_person: 'Contact', contact_phone: '09-111', contact_email: 'guest@example.test', customer_address: 'Yangon', off_day: 'Sunday', service_hour: '08:00-17:00', customer_status: 'Active' }
  const car = { id: 8, plate_no: 'MONTH-8', model_name: 'Monthly Van', availability_status: 'Available' }
  const driver = { id: 9, full_name: 'Monthly Driver', driver_status: 'Available' }
  const parent = { id: 42, contract_no: 'MC-202611-0001', customer_id: 5, customer, customer_name: 'Monthly Guest', guest_name: 'Guest', contact_person: 'Contact', customer_phone: '09-111', customer_email: 'guest@example.test', customer_address: 'Yangon', assignment_type: 'Vehicle and Driver', car_id: 8, car, driver_id: 9, driver, operator_id: null, operator_name: null, operator: null, even_odd: 'Even', operation_sheet: null, start_date: '2026-11-01', end_date: '2026-11-30', monthly_rate: '125000.00', rental_currency: 'MMK', rental_status: 'Including fuel', status: 'Active', renewal_parent_id: null, renewals: [], renewal_parent: null, note: null, prepared_by: null, invoice_sender: null, checked_by: null, off_day: 'Sunday', service_hour: '08:00-17:00', insurance_price: '15000.00', insurance_company: 'Snapshot insurer', terms_conditions: 'Terms' }
  const child = { ...parent, id: 43, contract_no: 'MC-202612-0001', start_date: '2026-12-01', end_date: '2026-12-31', renewal_parent_id: 42, renewal_parent: parent }
  await page.route('**/api/monthly-contracts/options', route => route.fulfill({ status: 200, json: { data: { customers: [customer], cars: [car], drivers: [driver], operators: [], statuses: ['Draft', 'Active', 'Expired', 'Renewed', 'Cancelled'] } } }))
  await page.route('**/api/monthly-contracts/42', route => route.fulfill({ status: 200, json: { data: parent } }))
  await page.route('**/api/monthly-contracts/42/renew', async route => {
    const body = route.request().postDataJSON()
    expect(body.start_date).toBe('2026-12-01')
    expect(body.end_date).toBe('2026-12-31')
    await route.fulfill({ status: 201, json: { data: child } })
  })
  await page.route('**/api/monthly-contracts/43', route => route.fulfill({ status: 200, json: { data: child } }))
  await page.goto('/monthly-contracts/42/renew')
  await expect(page.getByRole('heading', { name: 'Renew Monthly Contract' })).toBeVisible()
  await page.getByRole('button', { name: 'Save contract' }).click()
  await expect(page.getByText('MC-202612-0001', { exact: true })).toBeVisible()
  await expect(page.getByText('Previous: MC-202611-0001')).toBeVisible()
})
