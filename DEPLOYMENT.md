# HelpDeskPro Deployment Checklist

## 1. Requirements

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `xml`, and `zip` extensions.
- Composer 2.x.
- Node.js and npm versions supported by the frontend toolchain.
- MySQL 8.0+ or a compatible managed MySQL service.
- HTTPS for both the frontend and API.
- A process manager or service for the Laravel queue worker if queued jobs are enabled.

## 2. Backend deployment

1. Deploy the repository to the backend host.
2. Run `composer install --no-dev --prefer-dist --optimize-autoloader`.
3. Create `backend/.env` from `backend/.env.example` and provide secrets through the host secret manager where available.
4. Set `APP_ENV=production`, `APP_DEBUG=false`, and the real HTTPS `APP_URL`.
5. Generate an application key with `php artisan key:generate --force` only for a new installation. Preserve the existing key when updating an installation.
6. Run `php artisan optimize:clear`, then cache configuration with `php artisan config:cache` after all environment values are present.
7. Configure the web server document root to `backend/public` and route requests to `backend/public/index.php`.

Do not expose `backend/.env`, `storage`, or the application source through the web server.

## 3. MySQL database setup

Create a dedicated database and least-privilege application user. Set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

The repository now includes a baseline migration for the current core schema. The current application intentionally has no `departments` table because department support was removed. Validate the full migration sequence on a disposable MySQL database before production use. Do not run `migrate:fresh` or run unreviewed migrations against production.

After the schema is reconciled and tested against a disposable database, run:

```text
php artisan migrate --force
```

Back up the database before every production migration.

## 4. Environment variables

Use `backend/.env.example` as the variable-name reference. Required production values include:

- Application: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`.
- Database: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Authentication: `JWT_SECRET` and any JWT key settings required by the selected algorithm.
- CORS: `CORS_ALLOWED_ORIGINS`, containing the exact HTTPS frontend origin(s), comma-separated.
- Mail: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME`.
- Runtime: `FILESYSTEM_DISK`, `QUEUE_CONNECTION`, `CACHE_STORE`, and session settings.
- AI: `AI_PROVIDER`, model settings, and provider API keys. AI keys must remain backend-only.

Never put private AI keys or database credentials in the frontend or any `.env.example` file.

## 5. Laravel migration

Do not use `php artisan migrate:fresh`. Once the baseline migration issue is resolved and verified on a disposable MySQL database, use `php artisan migrate --force` during a controlled maintenance window.

## 6. Storage setup

Ticket attachments use Laravel storage. Configure a durable private local volume or an S3-compatible disk through the `FILESYSTEM_DISK` and `AWS_*` variables. Back up uploaded files separately from the database.

For public Laravel storage links, run once from `backend`:

```text
php artisan storage:link
```

Keep private attachments behind the existing authenticated download endpoint. Do not expose `storage/app/private` directly.

## 7. Frontend deployment

From `frontend`:

```text
npm ci
npm run build
```

Publish the generated `frontend/dist` directory through the frontend web host. Configure history-fallback routing so React routes serve `index.html`.

## 8. VITE_API_URL configuration

Create `frontend/.env.production` on the deployment system, not in Git:

```text
VITE_API_URL=https://api.example.com/api
```

Replace the example URL with the actual API origin and path. The Vite development proxy is local-only and is not used in the production build.

## 9. CORS configuration

Set `CORS_ALLOWED_ORIGINS` to the exact frontend origin, for example `https://app.example.com`. Do not use `*` for an authenticated deployment. Clear and recache Laravel configuration after changing it:

```text
php artisan optimize:clear
php artisan config:cache
```

## 10. Build commands

Backend:

```text
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

Frontend:

```text
npm ci
npm run build
```

## 11. Testing

Backend:

```text
php artisan test
```

Frontend:

```text
npm ci
npm run lint
npm run build
```

Run these checks in CI before deployment. Do not modify tests to hide failures.

## 12. Post-deployment verification

- Confirm HTTPS redirects and security headers at both origins.
- Confirm login, registration, email verification, password reset, and logout.
- Verify each role can access only its intended dashboard and ticket workflows.
- Create a ticket, upload and download an attachment, add comments, and verify history and notifications.
- Verify reports and exports.
- Test AI summary, priority, troubleshooting, knowledge-base, and chatbot requests with backend-only provider credentials.
- Confirm queue workers, mail delivery, cache, logs, backups, and monitoring.
- Confirm CORS rejects unapproved origins and no debug details are returned.

## 13. Updating the application after future changes

1. Back up the database and uploaded files.
2. Review the change and migration plan.
3. Put the application in maintenance mode if required.
4. Pull the release and run `composer install --no-dev --prefer-dist --optimize-autoloader`.
5. Run reviewed migrations with `php artisan migrate --force`.
6. Build the frontend with `npm ci && npm run build`.
7. Run `php artisan optimize:clear`, then `config:cache` and `route:cache`.
8. Restart queue workers so they load the new release.
9. Run the post-deployment verification checklist and monitor logs.
10. Never commit `.env`, credentials, API keys, logs, or generated build output.

## Current readiness note

The application has been prepared without deployment, database destruction, or secret changes. The migration baseline has been validated from zero on disposable MySQL. Production still requires actual secrets, URLs, mail, storage, CORS values, and operational verification to be supplied by the deployment environment.
