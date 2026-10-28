# Legacy source and current application audit

Audit date: 2026-10-28 (Asia/Rangoon)

## Inputs inspected

- Legacy PHP application: `C:\xampp\htdocs\fleet-management\fleet-management.zip`, extracted for read-only inspection under the Windows temporary directory. Relevant files include `bookings.php`, `create.php`, `edit.php`, `delete.php`, `includes/booking-form.php`, `monthly_contract/monthly-contracts.php`, `contract_create.php`, `contract_edit.php`, `contract_delete.php`, `contract_renew.php`, `monthly-contracts-data.php`, `monthly-contract-form.php`, `monthly-contract-view.php`, `insurance.php`, `insurance-api/**`, `resources/insurance/**`, and `docs/INSURANCE.md`.
- Legacy SQL files inside the ZIP: `database/fleet_management.sql` and `database/fleet-beta.sql`.
- Newer supplied schema: `C:\Users\zinwai\Downloads\fleet-beta-28-10-2026.sql`.
- Current application: repository `backend/` (Laravel) and `frontend/` (Vue).

The latest supplied dump contains 15 tables: nine core fleet tables, five insurance entities, and the `insurance_migrations` ledger. No row data, password hashes, or credentials were included in this audit report.

## Legacy workflow findings

### Daily bookings

- `bookings.php` lists records ordered by `updated_at DESC`, filters by free text, status, start date lower bound, and end date upper bound, and shows status totals. The legacy list is not paginated.
- `create.php` and `edit.php` save guest/company, optional registered or custom car, optional second registered car, optional registered or custom driver, operator ID and copied operator name, even/odd value, date-only period, status, and remark. There are no booking charge, time-of-day, or customer-ID fields in the schema.
- Status values are exactly `Pending`, `Confirm`, `Completed`, and `Cancelled`. New bookings default to `Confirm`; edit defaults to `Pending` only as a form fallback.
- A date range is valid when end date is the same as or later than start date. Registered cars in `Maintenance` are rejected. Drivers in `Leave` or `Inactive` are rejected. `Assigned` cars/drivers are not globally rejected.
- Availability checks run only when saving a `Confirm` booking. They reject inclusive date overlaps against other records whose status is `Pending` or `Confirm`; check primary and secondary car assignments together; check driver assignment; and exclude the current booking during edit. Despite the UI comment that selections are filtered, the backend check is the actual guard.
- The create/edit overlap check and save are not wrapped in a transaction or lock, so concurrent requests can race. The edit checker uses the same inclusive date condition.
- Delete is a POST workflow that permanently deletes the booking. The supplied form code includes a CSRF token; verify every legacy endpoint independently before deployment.

### Monthly contracts

- Contracts retain customer snapshot fields alongside nullable `customer_id`; the customer master record is also synchronized by legacy helper logic. Vehicle and driver references are to `monthly_cars` and `monthly_drivers`, not the daily tables.
- Create defaults the period to today through one month minus one day, status `Active`, rental status `Including fuel`, currency `MMK`, and a generated contract number. These are form defaults, not immutable business rules.
- The contract number generator, payload validation, relationship availability checks, expiry filters, and status/customer synchronization reside in `monthly_contract/monthly-contracts-data.php`; preserve these helpers' behavior when porting them.
- Renewal copies customer snapshots, assignment/resource IDs, operator, rate/currency, schedule fields, insurance company/price, sign-off names, and terms. It starts one day after the previous end date and defaults end date to one month minus one day after the renewal start. It creates a new row with `renewal_parent_id`, marks the old row `Renewed`, synchronizes customer status, and commits these changes transactionally. The form permits editing copied renewal values. This legacy path does not visibly guard against an already-renewed parent before insert; the unique contract number is the primary duplicate protection.
- Delete is hard delete by ID after CSRF check. FK constraints block deletion where dependent rows exist; the UI reports generic inability. Renewal history must not be cascade-deleted.
- Excel and PDF exports are present. Export column selection and report filters are implemented in their respective legacy export scripts.
- No daily booking price calculation exists in the booking schema or inspected booking forms. Contract pricing is an entered `DECIMAL(12,2)` monthly rate plus a separate contract-specific insurance fee. Do not infer a total or convert the insurance fee to a policy.

### Insurance

- `insurance-api/` is a distinct Laravel 12 application in the legacy archive with a Laravel migration, Eloquent models, Form Requests, Resources, policies, service, route handlers, tests, and private file handling. `docs/INSURANCE.md` describes its tested behavior and deliberate deployment boundary.
- Insurance policies support exactly one `cars` or `monthly_cars` reference. Premium and coverage are separate `DECIMAL(14,2)` values; currency is MMK/USD; insurance company/type and contract insurance fees are separate concepts.
- Policies retain a unique predecessor/successor renewal link. Renewal uses a DB transaction, row locks, a unique predecessor constraint, must start after the prior expiration, and retains vehicle and insurance type. Cancel and archive are timestamp transitions; history is retained.
- Claims have status/amount history. Documents are stored privately with metadata, replacement lineage, validation and authorized download.
- `docs/INSURANCE.md` records policy overlap, expiry, dashboard definitions and deployment decisions. In particular, same-type overlap blocking and duplicate physical-vehicle treatment were called out there as business decisions. Preserve these documented settings and surface unresolved choices before changing them.
- The newest supplied dump contains the insurance tables, but the ZIP's full insurance migration remains the best source for intended constraints and application behavior.

## Authentication and permissions

- Legacy core authentication is based on `admins` with `username`, optional email, `password_hash`, active flag, and last login. `login.php` authenticates username only, uses `password_verify`, optionally rehashes, rejects inactive accounts, regenerates the session ID, and stores `admin_id`; the shared bootstrap binds the session to user-agent and checks login state. `insurance-api` adapts this PHP session and only allows an active admin. No granular roles are present in the inspected core schema. `logout.php` calls `session_start()` twice; do not port that defect.
- New Laravel authentication already uses a Laravel `users` table with username/email/password and Sanctum stateful session authentication. Its `User` identity is not yet mapped to legacy `admins`; user ID equivalence, password-hash compatibility, active-admin enforcement, and permissions require a deliberate integration decision. Do not expose hashes or automatically copy credentials.

## Security and data-integrity observations

- SQL dump sources declare unsigned INT IDs for all legacy fleet tables, while the insurance migration intentionally uses unsigned INT references to those IDs. Keep these key widths when creating compatibility tables.
- Core SQL contains historical schema evolution and differs materially between the two dumps; treat the dated 2026-10-28 dump as the current source snapshot, subject to live schema verification before migrations.
- Booking overlap validation is vulnerable to a concurrency race because the check and write are separate and unlocked. It should be made atomic in the Laravel service with a consistent resource lock strategy.
- Legacy contract creation/edit code casts user-entered decimal values to PHP float before binding. The new API should validate decimal strings and persist decimal database values without binary floating-point arithmetic.
- Hard deletes exist for bookings and contracts. New API deletion must preserve these user-visible semantics only after checking dependencies; histories should be restricted from accidental deletion.
- The existing app's shared auth/session behavior is documented in `AUTH_IMPLEMENTATION.md`. Current route guards exist; module APIs must be added to the protected route group.

## Current repository architecture

- Backend: one Laravel 12.70.0 application, Sanctum 4.3.3, cookie/session auth, API exception formatting, PHPUnit, and only auth migrations/models/controllers. `routes/api.php` contains authenticated `/api/user` plus an unauthenticated health/test endpoint. There are no fleet-domain models, migrations, or APIs yet.
- Frontend: one Vue 3 + TypeScript + Vite application with Vue Router, Pinia, Axios, Bootstrap 5, Bootstrap Icons, GSS warm theme, responsive sidebar/header, auth store, common table/form/feedback components, and route placeholders for daily bookings, monthly contracts, and insurance.
- Installed Vue was `3.6.0-rc.12` under a broad set of `rc` overrides. The dependency graph was checked, then changed to stable Vue `3.5.44`; compiler/runtime packages resolve to the same stable version. Installed Vite is 8.3.5, plugin-vue 6.0.10, router 5.4.0, Vitest 4.1.11.
- The checked runtime is PHP 8.2.12, not the requested 8.5 target. Laravel's official support table lists Laravel 12 for PHP 8.2–8.5 ([release notes](https://laravel.com/docs/12.x/releases)); installed framework/dependency Composer constraints also allow PHP 8.5. Runtime behavior on PHP 8.5 remains unverified on this host. No PHP downgrade was made.
- Existing GSS UI styles and shell are in `frontend/src/assets/main.css`, `AppSidebar.vue`, `AppLayout.vue`, and common components. Preserve these.

## Database inspection boundary

The current destination was inspected read-only in this turn: `migrate:status` reports six applied Laravel migrations and `SHOW TABLES` returns only `cache`, `migrations`, `password_reset_tokens`, `personal_access_tokens`, `sessions`, and `users`. No fleet or insurance tables are present in that destination. The dated SQL dump is a source artifact; it does not prove that source rows match the dump. No data rows were queried, and no database was changed. A read-only live source schema inspection/reconciliation is still required before migration work.

## Verification performed in this audit

- Destination `php artisan migrate:status`: six migrations applied; read-only table-name query confirms the six auth/infrastructure tables listed above.
- Backend existing suite: 8 tests passed, 55 assertions, on PHP 8.2.12. Existing `route:list` exposes `/api/user`, `/login`, `/logout`, and the unauthenticated `/api/test`; no module APIs exist.
- Frontend existing suite: 12 tests passed across 3 files after the stable Vue update. `npm run build` passed type-checking and Vite production build.
- Composer dependency graph: `composer prohibits php 8.5 --locked` reports no installed package blocks PHP 8.5. This is constraint resolution, not execution under PHP 8.5.
- `npm audit --omit=dev`: no production dependency vulnerabilities. `npm install` reports two moderate advisories in the full development-inclusive dependency tree; no broad dependency upgrade was applied.
