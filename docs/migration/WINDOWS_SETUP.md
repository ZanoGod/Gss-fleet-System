# Windows / XAMPP setup for migration development

These steps target the existing Laravel 12 backend and Vue frontend in this repository. They do not import legacy rows or migrate the configured persistent database.

## Requirements

- PHP 8.2+ for the current Laravel 12 dependency graph; PHP 8.5 is the target but is not installed on the implementation host.
- Composer, MySQL/MariaDB, Node.js compatible with the locked Vite 8 toolchain, and npm.
- PHP extensions used by Laravel plus PDO MySQL, `fileinfo`, and `mbstring`.

Check the actual local versions before changing dependencies:

```powershell
php -v
composer --version
node --version
npm --version
```

## Backend

```powershell
cd C:\xampp\htdocs\gss-fleet-management\backend
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Configure the backend `.env` to point at a separately created disposable development MySQL schema. Verify `DB_HOST`, `DB_PORT`, `DB_DATABASE`, and the selected MySQL server before running any migrations. The implementation migrations intentionally stop if legacy fleet or Insurance domain tables already exist; compare their live `SHOW CREATE TABLE` output with `DATABASE_MAPPING.md` and prepare a schema-specific compatibility migration first.

Safe checks and tests:

```powershell
php artisan migrate:status
php artisan test
```

PHPUnit uses its configured in-memory SQLite database. Running `php artisan migrate` uses the `.env` database; never point that command at production or a persistent database without a separately approved migration plan and backup. No legacy import command is currently enabled.

Run the API locally:

```powershell
php artisan serve
```

The first-party SPA uses the existing `auth:web` Sanctum session and CSRF flow. Existing Laravel users remain the login identities. Insurance endpoints additionally require a manually reviewed `admin_user_links` row to an active legacy `admins` record; no identities or credentials are copied or matched automatically.

## Frontend

```powershell
cd C:\xampp\htdocs\gss-fleet-management\frontend
npm ci
npm run dev
```

In a second terminal, run requested checks:

```powershell
npm run test:unit -- --run
npm run type-check
npm run build
```

Set `VITE_API_URL` only if the frontend and Laravel API use different origins. For cross-origin local development, configure Sanctum stateful domains, session cookie domain, CORS credentials, and Axios XSRF handling together; do not move session secrets into local storage.

## Current migration status

The configured persistent MySQL database was inspected read-only on 2026-10-28. It has six applied Laravel infrastructure migrations and four new migration files pending. No production or persistent rows have been modified. The migration files have been executed by automated tests against in-memory SQLite only.
