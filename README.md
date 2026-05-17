# PHIDLMS Microfinance — Project Overview

PHIDLMS is a Laravel-based microfinance loan management system tailored for institutions in Tanzania. It supports multi-tenant operations, loan lifecycle management, billing/subscriptions, role-based access control, messaging (SMS), notifications, and background jobs.

## Tech Stack
- PHP 8.x, Laravel 10+
- MySQL 8.x (`utf8mb4`)
- Blade + TailwindCSS (views)
- Sanctum (API tokens)
- Spatie Activity Log (auditing)

## Core Modules
- Core: `plans`, `tenants`, `branches`, `users`, `clients`
- Loans: products, loans, schedules, repayments, documents, approvals
- Billing: subscriptions, items, plan addons, invoices, payments, Selcom transactions
- RBAC: roles, permissions, pivots (`role_permission`, `user_role`), role assignment workflow
- System: notifications, tokens, sessions, cache, jobs
- Messaging: SMS providers, wallets, topups, sender IDs, campaigns, logs

## Repository Layout
- `app/` — Application code (controllers, models, services, policies)
- `resources/views/` — Blade views (public, user, admin areas)
- `database/migrations/` — Laravel migrations
- `database/schema/` — SQL chunks mirroring migrations for quick bootstrap
- `database/seeders/` — Seeders (e.g., RBAC, Super Admin)
- `scripts/` — Utility scripts (DB setup, password reset, lookups)
- `public/` — Entry point

## Prerequisites
- XAMPP or PHP/MySQL installed on Windows
- Composer (`composer -V`)
- MySQL user with privileges to create DB/tables

## Setup (Windows)
1. Navigate to project:
   ```powershell
   cd "C:\xampp\htdocs\microfinance\phidlms"
   ```
2. Install dependencies:
   ```powershell
   composer install
   ```
3. Environment file:
   ```powershell
   copy .env.example .env
   ```
   - Update DB credentials (`DB_DATABASE=phidlms`, `DB_USERNAME`, `DB_PASSWORD`).
   - Set `APP_ENV=local`, `APP_URL=http://127.0.0.1:8002`.
4. App key:
   ```powershell
   php artisan key:generate
   ```
5. Initialize database (choose ONE path):
   - Migrations:
     ```powershell
     php artisan migrate
     ```
   - SQL chunks (faster bootstrap):
     ```powershell
     mysql -u root -p < "C:\xampp\htdocs\microfinance\phidlms\database\schema\01_core.sql"
     mysql -u root -p phidlms < "C:\xampp\htdocs\microfinance\phidlms\database\schema\02_loans.sql"
     mysql -u root -p phidlms < "C:\xampp\htdocs\microfinance\phidlms\database\schema\03_billing.sql"
     mysql -u root -p phidlms < "C:\xampp\htdocs\microfinance\phidlms\database\schema\04_rbac.sql"
     mysql -u root -p phidlms < "C:\xampp\htdocs\microfinance\phidlms\database\schema\05_system.sql"
     mysql -u root -p phidlms < "C:\xampp\htdocs\microfinance\phidlms\database\schema\06_messaging.sql"
     ```
   - Do not run both; pick the approach you prefer.
6. Seed baseline data (optional but recommended):
   ```powershell
   php artisan db:seed --class=RbacSeeder
   php artisan db:seed --class=SuperAdminSeeder
   ```

## Run Locally
- Start the dev server:
  ```powershell
  php artisan serve --host 127.0.0.1 --port 8002
  ```
- Open `http://127.0.0.1:8002`.
- Queue worker (for jobs like notifications):
  ```powershell
  php artisan queue:work
  ```

## Demo Credentials (Local)
- When `APP_ENV=local`, demo creds may be shown on the login page:
  - Admin: `admin@phidtech.com` / `password`
  - User: `user@phidtech.com` / `password`
- Remove demo hints before production.

## Configuration Notes
- SMS Providers: Configure per provider API keys and settings via DB (`sms_providers.config`) and/or `.env` as needed.
- Tenants: Messaging can be toggled per tenant (`tenants.messaging_enabled`). SMS credits and wallet management available.
- RBAC: Role assignment workflow (`role_assignments`) supports approvals; seeders add default roles and permissions.

## Schema Chunks
The schema chunks in `database/schema/` mirror migrations and can be applied independently for faster bootstrap. See `database/schema/README.md` for details.

## Testing
- Run PHPUnit tests:
  ```powershell
  php artisan test
  ```

## Deployment Tips
- Use environment-specific `.env` values.
- Run `php artisan config:cache` and `php artisan route:cache` when ready.
- Set up a real queue worker service (e.g., Windows Task Scheduler or a service manager) for `queue:work`.

## Troubleshooting
- DB connection: check `config/database.php` and `.env`.
- Permissions: ensure `storage/` and `bootstrap/cache` are writable.
- If using chunk SQL and later running migrations, ensure tables aren’t duplicated.