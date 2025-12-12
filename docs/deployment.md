# Deployment & Operations

## Target Environments
- **Local:** `APP_ENV=local`, `AFM_SSO_INTEGRATION_MODE=simulated`, `/dev/simulator` available.
- **Staging/Production:** `APP_ENV=production`, `APP_DEBUG=false`, `AFM_SSO_INTEGRATION_MODE=live` (disables simulator UI).

## Required Services
- PHP 8.2 + Composer
- MySQL 8+ (with timezone set to UTC)
- Redis 6+ (token cache + queues)
- Node 18+ (build pipeline only)

## Build Pipeline
1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
4. Copy `.env` (never commit) with production secrets.

## Deployment Steps
1. **Maintenance mode:** `php artisan down --render="errors::maintenance"` (optional but recommended).
2. **Code sync:** Deploy build artifacts (`/vendor`, `/public/build`, `/bootstrap/cache/*.php`).
3. **Database migrations:** `php artisan migrate --force`.
4. **Seeders (if needed):** e.g., `php artisan db:seed --class=DefaultFormsSeeder --force` when forms change.
5. **Cache clear (if config changed):** `php artisan config:clear && php artisan config:cache`.
6. **Queues:** restart workers (`php artisan queue:restart`).
7. **Maintenance up:** `php artisan up`.

## Render.com / Container Tips
- Use a web service for PHP-FPM/NGINX and a worker for queues.
- Provide environment variables through Render dashboard: `APP_KEY`, `AFM_SSO_SHARED_SECRET`, DB/Redis URLs.
- Add a cron job or scheduled worker for reminder dispatching once implemented.
- Run `php artisan storage:link` once per environment so exports have access to `storage/app/public` if needed.

## Docker (optional)
If you leverage Laravel Sail or custom Docker compose:
- Copy `.env.example` → `.env` and set `SAIL_XDEBUG_MODE=off` for production builds.
- `./vendor/bin/sail up -d` boots MySQL + Redis containers for local parity.

## Monitoring & Logs
- Centralize logs (`storage/logs/laravel.log`) with your platform (Render log streams, ELK, etc.).
- Pay special attention to `ssotoken` log channel warnings – they indicate Redis issues that could impact SSO performance.
- Track `audit_logs` table growth; schedule archival if required for compliance.

## Backups & DR
- Snapshot the MySQL database nightly (responses/completion flags are critical records).
- Back up `.env` values securely (password vault) – redeployments require the same secrets to validate SSO payloads.

