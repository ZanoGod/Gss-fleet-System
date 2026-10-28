# Implementation plan

This plan is grounded in the inspected legacy ZIP, its two schema generations, the supplied 2026-10-28 dump, and the current Laravel/Vue repository. It does not authorize a data import or destructive schema operation.

## Phase 1 — Audit (completed)

- [x] Extract and inspect legacy PHP application and separate Insurance Laravel project.
- [x] Read/compare `fleet_management.sql`, `fleet-beta.sql`, and `fleet-beta-28-10-2026.sql` structures.
- [x] Inspect current Laravel routes/models/migrations and Vue router/auth/theme/module placeholders.
- [x] Record field mapping, key widths, nullability, FKs, ER diagram and verified workflows.
- [x] Inspect current destination schema read-only: six Laravel auth/infrastructure tables only; no fleet/insurance tables.
- [x] Verify the supplied destination database and migration ledger read-only before schema work; the configured MySQL database contains six Laravel infrastructure tables and no domain tables.
- [x] Capture the requested decisions: retain Laravel users separately, no daily charges/times, preserve insurance overlap configuration, and keep same-plate daily/monthly fleet records distinct.

Deliverables: `LEGACY_AUDIT.md`, `DATABASE_MAPPING.md`, `BUSINESS_RULES.md`, this plan.

## Phase 2 — Database and shared foundations

1. Confirm destination DB schema and ensure the supplied SQL snapshot is the correct source. Record table counts and ID ranges without printing sensitive row values.
2. Decide identity integration (`admins` vs Laravel `users`), retain Sanctum cookie session, enforce active-account status, define the actual permission model, and avoid adding speculative roles.
3. Use the live destination as the authority for existing tables. Add only missing domain tables/columns with guarded, additive migrations; preserve source integer widths and avoid duplicate insurance schema. Do not migrate fleet data in this phase.
4. Add Eloquent models/relationships, Form Requests, Resources, API policies, and common pagination/filter conventions. Keep audit fields/password hashes off response resources.
5. Confirm migration behavior on a disposable schema matching MySQL/MariaDB and an isolated SQLite test DB where compatible.

## Phase 3 — Insurance

1. Port the archived insurance module into the existing Laravel backend; do not nest/copy the second Laravel app or its standalone auth adapter.
2. Inspect existing insurance tables/migration history first. If they already exist and match, add no duplicate DDL. Preserve separate `cars`/`monthly_cars` references, policy lineage, claims/events and private document storage.
3. Implement providers, dashboard, policies, renew/cancel/archive, claims/history, document upload/download with Laravel policies, Form Requests, Resources and transactions.
4. Replace only insurance placeholder views using current GSS theme and shared Axios/Pinia patterns.
5. Add PHPUnit/Vitest coverage for legacy behaviors, identity/authorization, overlap configuration, renewal races and private-file access.

## Phase 4 — Daily bookings

1. Implement `DailyBooking` model with legacy unsigned INT-compatible FKs and custom/secondary vehicle fields.
2. Port exact filters, status labels, date-only data, availability rules and inclusive conflict behavior.
3. Protect booking create/update with a transaction and resource locks so conflict check and write are atomic. Keep pending behavior as observed: submitted Confirm checks against Pending/Confirm records.
4. Add list/detail/create/edit API resources and Vue pages; add pagination and server-side filters without changing existing semantics.
5. Do not implement charges or times absent from legacy source pending rule input.

## Phase 5 — Monthly contracts

1. Implement contract/customer/monthly resource/operator models with monthly-specific FKs and snapshot fields.
2. Port contract-number generation, input validation, assignment-specific resource constraints, filters, customer status sync and generated reports from helpers/export scripts.
3. Implement create/update, protected delete semantics, history detail, and transactional renewal with parent locking, copied data, date rules, generated numbering, and duplicate prevention.
4. Add Excel/PDF exports after dependency choices are verified; preserve report columns and financial decimal precision.
5. Add feature/unit/UI tests for renewals, lineage, status transitions, reports and deletion constraints.

## Phase 6 — Data import (not authorized yet)

1. Implement a dry-run importer only after current source/destination schemas and ID collisions are inspected.
2. Import in FK order, preserve legacy IDs, use a transaction or idempotent source-key ledger, and report only counts and safe record identifiers.
3. Reconcile source/imported/skipped/failed counts plus FK and monetary/date validation; do not overwrite conflicts.
4. Obtain explicit approval before running any importer against a persistent/non-test destination.

## Phase 7 — Integration and verification

- Verify API auth/policies, navigation and module state handling.
- Run PHPUnit, Vitest, frontend type-check/build, and selected Playwright flows; report actual outputs only.
- Run PHP 8.5 and MySQL/MariaDB compatibility checks in an available target runtime before calling it verified.
- Prepare Windows/XAMPP setup/deploy notes, backup/recovery procedure, and a migration reconciliation template.

## Current blockers and risk controls

- Local runtime is PHP 8.2.12. Laravel 12.70.0 supports PHP 8.2 through 8.5, but this host cannot verify execution on PHP 8.5. No downgrade or runtime replacement is planned.
- The Laravel destination was inspected and has auth/infrastructure tables only; the live legacy source database has not been inspected against the new dated dump. Do not run migration against an unverified source/destination pair.
- Legacy `admins` identity versus current Laravel `users` identity has no confirmed mapping. Policy enforcement and Insurance `created_by` cannot be wired correctly until resolved.
- Daily booking charges are requested in the prompt but absent from all inspected booking source/schema. Do not invent calculation.
- The previous audit documents Insurance overlap/shared-vehicle decisions as unresolved; preserve current config and call out decision before deployment.
- Vue dependency graph was checked and moved from `3.6.0-rc.12` to stable `3.5.44`; all Vue compiler/runtime packages match. Lockfile refreshed and type-check/build passed.

## Audit-stage verification already executed

- Backend existing suite: 8 passed / 55 assertions under PHP 8.2.12; all six current migrations applied.
- Frontend existing suite: 12 passed; TypeScript and Vite production build passed with Vue 3.5.44.
- Destination table-name inspection: six auth/infrastructure tables only. No migration/import was executed.
- PHP 8.5 constraints resolve across the locked Composer graph, but execution remains unverified until a PHP 8.5 runtime is available.

## Implementation status — 2026-10-28

### Phase 2 — Database and shared foundations (implemented; persistent migration pending)

- Added guarded migrations for the nine legacy fleet tables, explicit admin-to-user links, five Insurance tables, and a monthly contract number sequence lock table.
- Added Eloquent models/relationships, preserving legacy `INT UNSIGNED` fleet keys, separate monthly vehicle/driver targets, date-only fields, enum spelling, and DECIMAL amounts.
- Added Forms Requests, Resources, and authorization policies. No role catalog or legacy admin data was invented or copied.
- Added `LegacyAdminIdentity`, which requires a manually created `admin_user_links` row and an active legacy admin before Insurance writes or reads. It never compares usernames or passwords to create links.
- PHPUnit executes the complete migration set against its in-memory SQLite test database. No migration was run on the configured persistent MySQL database. Latest `migrate:status` shows four new migrations pending.

### Phase 3 — Insurance (implemented)

- Added provider CRUD, policy create/edit/detail/renew/cancel/archive, renewal lineage, configurable overlap protection, fleet selector, dashboard summaries, claim updates/events, and private document upload/download/archive/replacement.
- Renewal, claim history updates, and document replacement use transactions. Policy and vehicle rows are locked for renewal/overlap checks. Document downloads require policy authorization and return private/no-store headers; upload storage refuses paths under Laravel's public directory.
- Replaced the insurance placeholder pages with GSS Bootstrap pages and Pinia/Axios integration.

### Phase 4 — Daily bookings (implemented)

- Added list/search/date/resource/status filters, paginated API, selector endpoint, create/edit/detail/delete, and GSS Vue pages.
- Confirm writes use transactions and resource locks; inclusive date overlap checks cover both car slots and driver against Pending/Confirm bookings. Pending writes may overlap. Exact legacy labels and operator snapshots are preserved. No time or charge fields were added.

### Phase 5 — Monthly contracts (implemented; report format note)

- Added list/summary/options, create/edit/detail/delete, exact monthly resource validation/overlap, customer snapshots/status sync, contract numbering, and duplicate-protected transactional renewals with parent lineage and legacy period dates.
- Contract insurance price/company stay on the contract and are never converted to Insurance policies.
- Added Excel-compatible UTF-8 CSV export. The report screen prints all filtered pages and uses the browser's Print / Save as PDF workflow; no PDF Composer dependency was introduced.

### Verification executed after implementation

- Backend: `php artisan test` — **25 passed, 171 assertions**, using PHPUnit's isolated in-memory SQLite database.
- Frontend: `npm run test:unit -- --run` — **16 passed across 6 files**.
- Frontend: `npm run type-check` — passed.
- Frontend: `npm run build` — passed with Vite 8.3.5.
- Playwright specs now cover booking creation and contract renewal workflows. `npx playwright test --project=chromium` was attempted, but this host returned `browserType.launch: spawn UNKNOWN` for the bundled Chromium executable before any test could run.
- PHP syntax: **76 files passed**.
- `php artisan route:list --path=api --except-vendor` — 38 API routes registered, including all three modules.
- `php artisan migrate:status` — read-only; six original migrations are applied, four new migrations remain pending. No persistent database schema or rows were modified.

### Remaining deployment checks

- Execute migrations only on a separately identified disposable/development MySQL schema first. The repository migrations deliberately stop if any legacy domain/Insurance table already exists; such a destination needs a schema-specific compatibility migration after review.
- Verify the database DDL on the deployment MySQL/MariaDB version and exercise the transaction/row-lock behavior there.
- PHP 8.5 is not installed on this host (runtime is PHP 8.2.12); dependency constraints were checked, but PHP 8.5 execution was not verified.
- No source data importer was run or added. Data import remains out of scope until explicit approval and a dry-run reconciliation.
- Playwright E2E execution and manual live-MySQL UI verification remain outstanding because the local browser process did not launch and no migration was run on the persistent database.
