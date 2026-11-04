# Page headers and status summaries

`PageHeader.vue` renders the module introduction and its action slot. `StatusSummary.vue`
lays out `StatusCard.vue` components. These components only present data: page calculations
belong in each module's `overview.ts`, using its own API summary, never the paginated table.

To customize a status box, edit its module's `overview.ts`: `label`, `description`, `icon`
(Bootstrap Icons), and `tone` (`info`, `success`, `warning`, `danger`). Tones use the existing
theme variables. A null/undefined value means unavailable; zero is a valid result. The
summary's `loading` prop takes precedence over values.

To change a module heading, edit its exported header in `overview.ts` (Customers,
Monthly Contracts, Insurance) or its existing `config.ts` (Cars, Drivers, Operators,
Maintenance). The shell's main title and subtitle, including Dashboard, are in the
route's `meta.title` and `meta.description` in `src/router/index.ts`.

Summary scopes intentionally preserve the existing APIs:

| Page | Source and calculation |
| --- | --- |
| Dashboard | `/api/dashboard`: all daily bookings; daily cars/drivers free today (manual status, reservations and car maintenance); confirmed bookings covering today; current Active contracts ending today through 30 days ahead. Dates are inclusive and use the server's application date. Recent bookings are the latest five updated records. |
| Cars | `/api/cars/summary`: stored statuses in the selected daily/monthly pool, matching search/status filters. Inactive applies to monthly cars only. |
| Drivers | `/api/drivers/summary`: whole selected daily/monthly pool, independent of table filters. Assigned includes legacy On Trip. Stored Available is distinct from date-specific assignment availability. |
| Operators | `/api/operators/summary`: total, Active and Inactive matching table filters. |
| Customers | `/api/customers/summary`: all profiles, Active profiles, and customers with contracts, independent of table filters. |
| Monthly Contracts | `/api/monthly-contracts/summary`: selected date range, independently of table status/search filters; each contract status is counted separately. |
| Insurance | `/api/insurance/dashboard`: whole portfolio, computed coverage dates/configured expiry window, and submitted/under_review open claims. |
| Maintenance | `/api/maintenances/summary` dashboard: whole fleet; scheduled jobs through week end (including overdue), jobs in progress, monthly completions and prior-month difference, monthly spend kept separate by currency. |
