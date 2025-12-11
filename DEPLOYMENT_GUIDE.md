# AFM Deployment Guide

This document describes how to deploy the AFM Laravel application to a free cloud host such as Render. The same steps apply to Railway/Koyeb with small adjustments to the build/start commands.

## 1. Prepare the Repository
1. Commit all local changes and push the repository to GitHub (or another Git host supported by the platform).
2. Ensure `.env.example.production` contains the environment variables you plan to set in the hosting dashboard.
3. Run `php artisan optimize:clear` locally to verify there are no cached artifacts committed to source control.

## 2. Required Environment Variables
Copy `.env.example.production` into the provider’s dashboard and fill in the database credentials they supply. The key variables are:

- `APP_URL`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `AFM_SSO_SHARED_SECRET` (already populated with the production key)
- `SERVER_HOST=0.0.0.0`, `SERVER_PORT=8080`

## 3. Render Hosting Example
1. Create a **Web Service** in Render and connect it to your GitHub repo.
2. Choose the **PHP 8.2** runtime.
3. Set the **Build Command** to:
   ```
   ./render-build.sh
   ```
4. Set the **Start Command** to:
   ```
   php artisan serve --host=0.0.0.0 --port=10000
   ```
   (Render injects `$PORT`; you can replace `10000` with `$PORT` if preferred.)
5. Add the environment variables from `.env.example.production` and fill in the database credentials Render provides.
6. Add a free MySQL add-on (Render “MySQL” or another managed database). Copy the connection settings into your environment variables.
7. Deploy. Render will install dependencies, migrate, seed, and boot the app.

## 4. Storage & Cache Requirements
- Run `php artisan storage:link` once after deployment to expose `storage/app/public`.
- Ensure the hosting service allows write access to:
  - `storage/`
  - `bootstrap/cache/`

## 5. Verification Checklist
After the service boots:
1. Visit `/afm` – this should be the default landing page.
2. Confirm `/dev/simulator` still works (handy for test logins).
3. Log in via simulator using a student payload and verify `/student/dashboard` loads.
4. Log in via simulator using a QA payload and verify `/qa` and `/qa/reports/responses` are reachable.
5. Inspect logs for any database connectivity errors.

## 6. Useful Commands
- `php artisan migrate --force` – run pending migrations.
- `php artisan db:seed --force` – seed default data.
- `php artisan optimize:clear` – clear caches before debugging.

Following these steps ensures AFM can run on Render/Railway/Koyeb without manual intervention during deployment.
