# Deployment & Environment Notes

## 1. Environment Configuration

### 1.1 Critical Variables
The application requires specific `.env` variables to function securely in production.
- `APP_ENV=production`: Disables debug mode and dev routes (`/dev/simulator`).
- `AFM_SSO_SHARED_SECRET`: The encryption key used to sign SIS payloads. Must be 32+ chars.
- `SESSION_SECURE_COOKIE=true`: Enforces HTTPS-only cookies.
- `DB_CONNECTION=mysql`: Production database driver.

### 1.2 Optimization Flags
To handle high traffic during feedback weeks, the following optimizations are applied:
- `php artisan config:cache`: Freezes configuration to avoid file I/O per request.
- `php artisan route:cache`: Speeds up route dispatch matching.
- `php artisan view:cache`: Pre-compiles Blade templates.

## 2. Token & Secret Management
### 2.1 Secret Rotation Policy
- **Type:** Manual Rotation.
- **Trigger:** Semester boundaries or suspected compromise.
- **Procedure:**
    1. Generate new string.
    2. Update SIS configuration.
    3. Update AFM `AFM_SSO_SHARED_SECRET`.
    4. Restart AFM service to flush config cache.

## 3. Post-Migration Steps
After deploying a new version:
1.  **Maintenance Mode:** Run `php artisan down` to prevent partial writes.
2.  **Migrations:** Run `php artisan migrate --force` to apply schema changes.
3.  **Seeding:** If new forms are introduced, run `php artisan db:seed --class=FormSeeder`.
4.  **Storage:** Ensure `php artisan storage:link` is active for public assets.
5.  **Live:** Run `php artisan up`.

## 4. Server Requirements
- **PHP:** 8.2 or higher (Extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML).
- **Web Server:** Nginx or Apache with URL rewriting enabled.
- **Database:** MySQL 8.0+ or PostgreSQL 13+.
- **Cache:** Redis (Recommended) or Memcached.
