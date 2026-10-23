# GSS Fleet Management: Phase 0 Audit and Architecture

## Audit outcome

The checkout contains the Laravel 12 API/auth backend and Vue 3/TypeScript shell. It does not contain `PROJECT_HANDOFF.md`, a `source/` directory, SQL dumps, or any fleet-domain migrations. The live MariaDB schema and records could not be inspected from the available repository artifacts. `AUTH_IMPLEMENTATION.md` is the only handoff-level reference and records prior isolated test results and an unresolved local key problem.

No fleet business rules, domain fields, or relationships should be inferred until the legacy PHP source and live schema are supplied or made accessible. There is no safe basis yet for implementing dashboard metrics or CRUD beyond the existing shell.

## Existing module inventory

| Area | Present implementation | Evidence / gap |
| --- | --- | --- |
| Authentication | Laravel session guard + Sanctum stateful SPA; CSRF cookie; login, user, logout; 1,440 minute database session; throttled login | `backend/app/Http/Controllers/AuthController.php`, routes, session/Sanctum/CORS config, `frontend/src/services/auth.ts`, Pinia auth store, `AUTH_IMPLEMENTATION.md`. The note reports passing isolated checks, but local live login is blocked by malformed APP_KEY (21 decoded bytes vs required 32). |
| Startup and layout | Root redirects to Dashboard; router restores session before protected navigation; shared responsive sidebar, GSS styling, user chip and logout | `frontend/src/router/index.ts`, `App.vue`, `components/AppSidebar.vue` and CSS. Breadcrumb component and explicit shared header module are not separated; header is currently in the sidebar shell component. |
| Dashboard | Presentation placeholders only | Summary metrics and recent bookings are explicitly not connected to an API. |
| Daily bookings | Placeholder route/view | No backend model, migration, endpoint, validation, pricing rule, or status definitions. |
| Fleet / vehicles | Placeholder route/view | No domain schema or API. |
| Customers | Placeholder route/view | No domain schema or API. |
| Drivers | Placeholder route/view | No domain schema or API. |
| Operators | Placeholder route/view | No domain schema or API; relationship to users/bookings is unknown. |
| Monthly contracts | Placeholder route/view | Sidebar route exists; no workflow/schema/API evidence. |
| Insurance | Placeholder route/view | No implementation or schema evidence. |
| Maintenance | Placeholder route/view | No implementation or schema evidence. |
| Payments | Placeholder route/view | Present in navigation, outside listed core modules; preserve pending legacy review. |
| Reports | Placeholder route/view | No legacy report definitions or export implementation found. |

## Database and relationship map

The live `gss_fleet_v2` inspection confirms six tables: `users`, `sessions`, `password_reset_tokens`, `personal_access_tokens`, `cache`, and `migrations`. All six repository migrations are applied. There are no fleet-domain tables and no declared foreign keys. `users` has `id`, `name`, unique `email`, `email_verified_at`, `password`, `remember_token`, timestamps, and nullable unique `username`. Session storage has the standard Laravel fields and is configured for 1,440 minutes. Exact safe-column query returned two users (IDs 1 and 2) and one session linked to user 2. It did not read password hashes, session payloads, or secrets.

The fleet relationship map cannot be produced from the current database: there are no booking, customer, vehicle, driver, operator, contract, insurance, maintenance, or report tables. Any expected links among those concepts remain unverified until legacy PHP/schema becomes available. The only observed data relationship is `sessions.user_id` referencing an existing user operationally; MySQL declares no foreign key for it.

## Architecture plan after source/schema access

Keep the existing Laravel 12 and Vue shell. Add domain endpoints inside authenticated routes, and keep each module's HTTP controller, Form Request validation, Eloquent relationships, API Resource, and substantial business rules in a focused service. Avoid migrations until the live schema has been checked; any necessary schema change must be additive and preserve legacy keys and history.

On Vue, retain the shared Axios client, Pinia auth store, router guard, GSS styles, and responsive shell. As modules are verified, add module-scoped pages, forms/tables, service calls, and types. Split the current shell into layout pieces only where that improves reuse; do not create empty scaffolding for unverified modules. Use server filtering/pagination and standard API error handling.

## Phase 1 status and verification boundary

The repository implements the chosen 24-hour persistent cookie session strategy. In this continuation, `php artisan test` passed 6 tests and 40 assertions, including CSRF rejection, username/email login, session rotation/restoration/logout, expiry renewal, and rate limiting. `npm.cmd run test:unit -- --run` passed 5 auth store tests. `npm.cmd run build` passed TypeScript checking and the Vite production build.

The current local `APP_KEY` is valid: Laravel successfully initialized the encrypter with its 32-byte decoded key. The active session/database configuration is `SESSION_DRIVER=database`, `SESSION_LIFETIME=1440`, `SESSION_EXPIRE_ON_CLOSE=false`, `SESSION_HTTP_ONLY=true` (Laravel default), and `SESSION_SAME_SITE=lax`. Automated auth lifecycle verification passes. A separate manual browser login using the existing account was not performed, because the account password was not accessed. The repository already has responsive navigation, protected-route initialization, Dashboard redirect, user information, and logout control; no second auth mechanism is needed.

## Required inputs / unresolved gaps

1. Restore the previous PHP application source (especially `source/`) or provide an accessible archive.
2. Provide a current schema-only SQL dump or read access to `gss_fleet_v2`, including table definitions, indexes, and foreign keys. A data dump is unnecessary if live DB access can report structure and representative statuses safely.
3. Resolve the malformed local APP_KEY using the key that belongs to this environment, or authorize a new local-only key if this database has no encrypted data that needs the previous key. Do not rotate a production key as part of this work.
4. Provide any missing permissions/role definitions if they live outside the legacy source/database.

After these inputs are available, complete the functional module/relationship map, compare legacy pages and queries against current code, then finish and verify Phase 1 before starting Dashboard work.

## Exact local commands

Backend (from `C:\xampp\htdocs\gss-fleet-management\backend`):

```powershell
& C:\xampp\php\php.exe artisan config:clear
& C:\xampp\php\php.exe artisan migrate:status
& C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Frontend (from `C:\xampp\htdocs\gss-fleet-management\frontend`):

```powershell
npm.cmd run dev -- --host 127.0.0.1 --port 5173
```

Start MySQL from XAMPP first, configure the valid stable application key and database connection, and restart Vite/backend after configuration changes. Verification completed in this continuation: `php artisan test` (6 passed, 40 assertions), `npm.cmd run test:unit -- --run` (5 passed), and `npm.cmd run build` (TypeScript and production build passed). The first sandboxed runs needed elevated access to the local PHP dependency directory and Vitest system temp directory; the elevated reruns passed.
