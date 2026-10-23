# Authentication implementation

## Root causes and current blocker

No PROJECT_HANDOFF.md or AGENTS.md was found. The active Pinia store bypassed the shared Axios client and sent /api/login without initializing CSRF. Sanctum stateful middleware requires a matching CSRF cookie/header for this origin. This conflicting implementation explains the reported 419; the original request was not captured.

The latest Laravel log reports an unsupported cipher/incorrect key length. Resolving the encrypter reproduced it: the configured APP_KEY decodes to 21 bytes, but AES-256-CBC requires 32. Config was not cached. The invalid APP_KEY remains unchanged pending permission to replace that secret. Local login remains blocked until it is repaired.

Earlier missing sessions/cache errors are historical. All migrations are applied; sessions exists; users has username, email and password. No database schema or password changes were necessary.

## Strategy and lifecycle

Sanctum SPA cookies use Laravel's session guard. Stateful middleware supplies API session/CSRF handling; auth:web explicitly rejects legacy bearer tokens. Removed the unused token controller and token login/logout endpoints. Supported endpoints: POST /login, GET /api/user, POST /logout, GET /sanctum/csrf-cookie.

One Axios client uses credentials and XSRF headers. The Vite proxy handles /api, /sanctum, /login and /logout. VITE_API_URL optionally selects a separate backend origin. Use 127.0.0.1 consistently locally. FRONTEND_URL configures allowed CORS origins.

Database sessions expire after 1,440 minutes of inactivity. Cookies persist across browser restarts, use HttpOnly and SameSite=Lax, and renew alongside server last_activity on stateful requests. Login regenerates the session ID. CSRF cookies are readable for Axios; authentication cookies are not. Password checking uses Laravel and user responses allowlist id/name/username/email.

Initialization requests /api/user with concurrent requests deduplicated. Routing waits before rendering, guards every existing module and checks the server on protected navigation. Actual pointer/keyboard interaction checks authentication at most once per minute. There is no unattended renewal interval. An expired idle screen redirects on its next interaction/request/navigation when the server rejects it. Future background polling must not be introduced as a keepalive.

Protected 401/419 responses clear identity and redirect to Login. Error messages distinguish validation, credentials, server failures, throttling and network failures. Logout invalidates the server session and rotates CSRF. A stale logout CSRF token is refreshed once. Network/server failures display an error and retain identity for retry. A late user response cannot restore identity after logout.

## Changed files

- Backend: app/Http/Controllers/AuthController.php; removed app/Http/Controllers/Api/AuthController.php; routes/api.php; routes/web.php; config/session.php; config/sanctum.php; config/cors.php; .env (session settings only); .env.example; tests/Feature/AuthenticationTest.php; tests/Feature/ExampleTest.php.
- Frontend: src/services/api.ts; src/services/auth.ts; src/stores/auth.ts; src/stores/auth.spec.ts; src/router/index.ts; src/main.ts; src/views/LoginView.vue; src/components/AppSidebar.vue; vite.config.ts.
- This document. The pre-existing frontend/src.rar deletion was left alone. Existing api.ts credentials/XSRF changes were retained. GSS styles and module components were preserved.

## Executed verification

- Backend: 6 tests passed, 40 assertions. Tests use isolated SQLite, database session storage, a test-only key and actual CSRF validation. Coverage includes username/email login, missing CSRF rejection, validation, invalid credentials, safe response fields, session rotation, persistent HttpOnly cookie expiry, restoration, logout, unauthorized access, 24-hour expiry, activity renewal and rate limiting.
- Frontend: 5 store tests passed: deduplicated restoration, expired identity, failed logout/retry, initialization retry and stale response after logout.
- Production build and TypeScript check passed.
- Migration status: all applied. Route listing: expected session endpoints.

Not executed: real-browser refresh/new-tab/restart authentication, live existing-user MySQL login, browser 401 redirects or comprehensive module interaction. These are not claimed as passed. Module pages in this checkout are largely placeholders; no fleet data API routes exist beyond auth and /api/test. Future module APIs must join the protected route group.

## Exact PowerShell commands

Start MySQL in XAMPP. The invalid APP_KEY must be repaired first. Replacing that secret requires the owner's pending approval; do not rotate a valid production key casually.

Backend terminal:

```powershell
Set-Location C:\xampp\htdocs\gss-fleet-management\backend
& C:\xampp\php\php.exe artisan config:clear
& C:\xampp\php\php.exe artisan migrate:status
& C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

Frontend terminal; restart Vite to load the changed proxy:

```powershell
Set-Location C:\xampp\htdocs\gss-fleet-management\frontend
npm.cmd run dev -- --host 127.0.0.1 --port 5173
```

Verification terminal:

```powershell
Set-Location C:\xampp\htdocs\gss-fleet-management\backend
& C:\xampp\php\php.exe artisan test
& C:\xampp\php\php.exe artisan route:list --except-vendor
Set-Location C:\xampp\htdocs\gss-fleet-management\frontend
npm.cmd run test:unit -- --run
npm.cmd run build
Invoke-RestMethod http://127.0.0.1:8000/api/test
Start-Process http://127.0.0.1:5173
```

Browser checklist: existing-account login; refresh; new tab; close/reopen without clearing cookies; all module navigation; logout; refresh and protected URLs; incorrect password; disconnected server. Check the session cookie's HttpOnly flag and expiry about 24 hours ahead.

Production: HTTPS, APP_ENV=production, APP_DEBUG=false, correct APP_URL, SESSION_SECURE_COOKIE=true, SESSION_HTTP_ONLY=true, SESSION_SAME_SITE=lax, SESSION_DRIVER=database, SESSION_LIFETIME=1440, SESSION_EXPIRE_ON_CLOSE=false. Configure exact SANCTUM_STATEFUL_DOMAINS and FRONTEND_URL. Prefer a same-origin production reverse proxy; otherwise configure VITE_API_URL and compatible same-site domains. Keep APP_KEY stable and secret. Vite's proxy applies only in development. No new migrations are required for the inspected database.

Reference: https://laravel.com/docs/12.x/sanctum#spa-authentication
