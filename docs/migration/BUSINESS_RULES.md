# Verified business rule mapping

Evidence is from the legacy ZIP source and the 2026-10-28 SQL dump. A value described as a page default is not promoted to a universal business rule unless validation or persistence confirms it.

## Daily bookings

| Rule | Legacy behavior | Porting requirement |
|---|---|---|
| Customer label | Required `guest_company_name` text; no customer FK | Keep this as a free-form snapshot; do not force customer master selection. |
| Vehicles | Optional `car_id`, `secondary_car_id`, `custom_car_name`; both registered IDs target `cars`; two registered cars must differ | Preserve both slots and custom text. Never use `monthly_cars` here. |
| Driver | Optional `driver_id` and `custom_driver_name`; ID targets `drivers` | Preserve registered/custom alternatives. |
| Operator | `operator_id` may be null in SQL; `operator_name` is required copied text. Form resolves active operator and snapshots its name. | Keep both fields. Preserve historical operator name if master changes/deletes. |
| Dates | Required DATE-only `start_date` / `end_date`; end >= start | No times exist in source. Do not add time-based pricing or duration formulas without an approved requirement. |
| Even/odd | Nullable enum `Even` / `Odd`; form requires it on writes | Preserve exact allowed values and investigate existing null rows before enforcing non-null in API. |
| Status | `Pending`, `Confirm`, `Completed`, `Cancelled`; DB default `Pending`; create form default `Confirm` | Preserve all four labels and their case. Do not normalize `Confirm` to `Confirmed`. |
| Availability | Maintenance cars rejected; Leave/Inactive drivers rejected; Assigned status alone does not reject | Validate current resource status on write, while allowing the existing Assigned behavior. |
| Overlap | On write when submitted status is `Confirm`, reject any inclusive date overlap with a different booking in `Pending` or `Confirm`. Cars compare against both car slots; drivers compare by driver ID. Edit excludes self. | Server-side validation is required. `Pending` can overlap another `Pending`; completed/cancelled rows do not block. Use transaction and concurrency-safe vehicle/driver locking in the new service. |
| List filtering | Free-text search across guest, car/plate/custom car, operator, driver/custom driver, even/odd and remark. Status exact match. `date_from` tests `start_date >=`; `date_to` tests `end_date <=`. Sort latest `updated_at` first. | Preserve those filters unless a deliberate UX change is approved. Add pagination as a new API capability. Avoid changing date filter semantics silently. |
| Delete | POST with CSRF; physical delete of `bookings` row | API authorization and dependency checks are required. No booking history table was found in legacy schema. |
| Pricing | None in booking schema/form/page | No booking charge calculation is recoverable from the source. `charges` is a prompt requirement without legacy evidence; hold that behavior pending clarification. |

## Monthly contracts

| Rule | Legacy behavior | Porting requirement |
|---|---|---|
| Customer | `customer_id` nullable FK to `customers`; required `customer_name` and other snapshot/contact fields are stored on contract | Preserve both master link and snapshot. Customer deletion sets FK NULL in the newest dump. |
| Vehicle and driver | IDs target `monthly_cars` and `monthly_drivers`, not daily fleet tables | Keep strict separate Eloquent relationships and validators. |
| Operator | Nullable `operator_id` plus nullable `operator_name` snapshot | Preserve both. |
| Assignment | Enum `Driver Only`, `Vehicle Only`, `Vehicle and Driver` | Preserve spelling. Resource requirements are assignment-specific as detailed below. |
| Assignment validation | Vehicle is required for `Vehicle Only` and `Vehicle and Driver`; it must exist in monthly cars and cannot be Maintenance or Inactive. Driver is required only for `Vehicle and Driver`; it must exist in monthly drivers and cannot be Leave or Inactive. `Driver Only` does not require a vehicle, and vehicle-only does not require a driver. | Port these exact conditional requirements and resource status checks. |
| Contract resource overlap | For `Draft` or `Active` writes, reject inclusive date overlaps for the same monthly car or monthly driver against other contracts in `Draft` or `Active`; edit excludes self. | Enforce on backend and lock resources around validation/write. Expired/Renewed/Cancelled rows do not block. |
| Period defaults | New contract defaults start to current date and end to current date +1 month -1 day | Treat as UI default only; enforce valid start/end and legacy validation. |
| Amounts | Stored as `DECIMAL(12,2)`, currency `MMK`/`USD`; contract insurance fee `insurance_price DECIMAL(12,2)` plus optional `insurance_company`. The legacy form accepts comma-formatted nonnegative whole numbers only for both amount inputs. | Preserve whole-number input semantics, strip display commas, then bind/store a fixed decimal string without float conversion. Insurance fee is not a policy premium; its currency linkage is unresolved. |
| Rental status | `rental_status` is stored as `VARCHAR(50)`; allowlist is `Including fuel`, `Excluding fuel`, `Car only`, `Driver only`; default UI value is `Including fuel`. | Validate against the source helper's exact allowlist; keep destination storage as string, not an invented DB enum. |
| Status | `Draft`, `Active`, `Expired`, `Renewed`, `Cancelled` | Preserve exact states. Renewal marks parent `Renewed`, new record defaults `Active`. |
| Renewal number | `MC-YYYYMM-` + the greatest lexically ordered number with that prefix incremented and padded to four digits; if no matching value, starts `0001`. It uses renewal start as month input. | Preserve this format. The current SELECT-then-increment can race; retain the format while making allocation transactionally safe under the unique key. |
| Renewal dates | Child starts one day after parent end; child ends one month minus one day after that start | Preserve calendar rules. Source uses date-only values. |
| Renewal content | Copies customer identity/contact snapshots, assignment, monthly fleet resources, operator, rate/currency, schedule, insurance snapshot, signatories and terms; form can change copied values before save | Preserve copied defaults and permitted edits. New child links via `renewal_parent_id`; old contract retained. |
| Renewal atomicity | Child insert, parent status update and customer status sync occur in a transaction; rollback on failure | Keep as one transaction and lock parent. Add explicit already-renewed check to prevent duplicate action while preserving source outcomes. |
| Customer sync | `monthly_contract_sync_customer_status` changes linked customer to `Active` when saved contract status is `Active`; all other statuses and missing IDs do nothing. Renewal calls this in its transaction. | Preserve this one-way update. Do not infer automatic Inactive transitions. |
| Delete | CSRF-protected hard delete by ID; DB FKs reject parent deletion when child references exist, but generic failure is shown | Preserve parent lineage and related data. Prefer conflict response for referenced contracts; no cascade. |
| Search/report | Legacy list includes customer/contract/resource/status/payment/rental/expiry/date filtering; Excel/PDF exports allow selected columns | Preserve source filters/columns and report scope after reviewing full report handlers. |

The assignment-specific resource requirements, number generator, customer status synchronization, and all validation details are in `monthly_contract/monthly-contracts-data.php`; these are explicitly marked for full service-level port and tests rather than approximated from page labels.

## Insurance

- Exactly one of `car_id` (daily) or `monthly_car_id` (monthly) must be set on each policy.
- Provider, policy number, insurance type, start/end date, premium, coverage amount, currency and notes are policy fields. Premium and coverage use DECIMAL(14,2); currency is MMK or USD.
- Policy term unique key is provider + policy number + start date. Renewal predecessor is unique, so each term has at most one successor.
- Archived, cancelled or already-renewed policies are read-only. A renewal starts strictly after predecessor expiration and retains its fleet subject and insurance type. Renewal is transactional with locks and unique predecessor guard.
- Existing same-subject/same-type coverage overlap is blocked when `INSURANCE_BLOCK_OVERLAP` is enabled. Insurance documentation presents this as a configurable business decision; preserve configuration and obtain decision before changing.
- Insurance dashboard expiry window and claim state semantics are documented in archived `docs/INSURANCE.md`; port those definitions and tests without recomputing them in the Vue client.
- Claims link to policies; each update appends a claim event with status/amount/notes/actor. Document replacement links are unique; files are private and downloads authorized.
- The current schema dump has actual insurance tables; do not recreate them or rerun the old standalone migration without first checking current destination schema and its migration ledger.
- Contract `insurance_price` and `insurance_company` remain contract values. No automatic conversion or provider inference.

## Cross-cutting authorization

Legacy admin access is an active-admin session check; no granular roles are evident in source schema. Laravel roles and permissions are not recoverable from legacy and must not be fabricated. The Vue SPA already uses protected session routes, but new APIs must add `auth:web` and explicit authorization policies. The mapping between legacy `admins` and Laravel `users` is unresolved.

## Deferred inputs (not needed for the implemented behaviors)

1. Daily booking charges/times: legacy source has neither, so the migrated API and UI leave them out. A future charging requirement needs its own authoritative formula and fields.
2. Insurance overlap environment setting: the archived default is `true` and is preserved. Deployment can set the existing environment variable intentionally if the installed legacy configuration differs.
3. Shared physical vehicles: daily and monthly fleet rows stay separate, including insurance counts, even when plates match.
4. Identity links: Laravel users authenticate; only manually provisioned `admin_user_links` connect a Laravel user to an active legacy admin for Insurance audit fields and permissions. Unlinked users receive 403.

## Decisions supplied for implementation (2026-10-28)

- Keep the existing MySQL production target and Sanctum cookie/session authentication.
- Keep legacy `admins` separate from Laravel `users`; Insurance uses only an explicit, unique `admin_user_links` association. No automatic identity matching, credential copying, or role creation is permitted.
- Preserve the existing `INSURANCE_BLOCK_OVERLAP` configuration and default. Same-plate records across `cars` and `monthly_cars` remain different subjects.
- Daily bookings remain date-only and have no computed charges. Keep the exact Pending/Confirm/Completed/Cancelled statuses and inclusive Confirm overlap behavior.
- Do not import legacy rows without explicit approval.
