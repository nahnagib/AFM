# Local Setup

## Prerequisites
- **PHP 8.2** with extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML.
- **Composer 2.6+**
- **Node 18+/npm** (Vite build + Tailwind)
- **MySQL 8.0+** (or MariaDB with JSON + generated columns), create a database `afm`.
- **Redis 6+** (optional but recommended for session token caching).

## 1. Clone & Install
```bash
composer install
npm install
```

## 2. Configure Environment
1. Copy `.env.example` → `.env`.
2. Set database credentials (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
3. Configure queues/caches if needed (`QUEUE_CONNECTION=database`, `REDIS_HOST=127.0.0.1`).
4. **SSO config:**
   - `AFM_SSO_SHARED_SECRET=change-me` (32+ char random string shared with SIS/dev simulator).
   - `AFM_SSO_ISS=LIMU-SIS` and `AFM_SSO_AUD=AFM` (must match payloads).
   - `AFM_SSO_ALLOWED_ROLES="student,qa,qa_officer,department_head,admin"`.
   - `AFM_SSO_TOKEN_TTL=300` (seconds cached in Redis).
   - `AFM_SSO_INTEGRATION_MODE=simulated` (enables `/dev/simulator`).
5. Set `AFM_CURRENT_TERM=202510` (see `config/afm.php`).
6. Run `php artisan key:generate`.

## 3. Database
```bash
php artisan migrate
# Seed default forms, SIS demo data, and staff roles
php artisan db:seed --class=DefaultFormsSeeder
php artisan db:seed --class=SimSisAfmDemoSeeder
php artisan db:seed --class=StaffRolesSeeder
```
Other helpful seeders: `UserSeeder` (admin login), `TestSsoPayloadSeeder` (fixtures for integration tests), `QaOverviewTestDataSeeder` (demo reporting data).

## 4. Running the app
Use the `dev` composer script to boot everything at once:
```bash
composer dev
```
This runs:
- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `php artisan pail --timeout=0` (log tail)
- `npm run dev` (Vite + Tailwind)

OR run them manually if you want separate shells. Remember to keep the queue worker alive for reminder jobs/outbox processing.

## 5. Logging in locally
- Visit `http://127.0.0.1:8000/dev/simulator` and pick a JSON payload (student or QA). The simulator signs the payload with your configured secret and posts it to `/dev/simulator/login`, which proxies to `/sso/json-intake`.
- For admin-only routes you can create a Laravel `users` record with `role=admin` using `php artisan tinker` or `UserSeeder`.

## 6. Helpful commands
- `php artisan migrate:fresh --seed` → rebuild DB when schema changes.
- `php artisan test` → run the full PHPUnit suite.
- `php artisan queue:work --queue=default --tries=1` → process notification outbox (stub today).
- `npm run build` → production Vite build for deployments.

Keep `.env` out of version control. Regenerate the SSO secret whenever you share the repo externally.
