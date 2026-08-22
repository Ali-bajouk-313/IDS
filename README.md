# HelpDeskPro

HelpDeskPro is a role-based help desk ticketing system with a Laravel API and a React/Vite frontend.

The UI/UX prototype is available in [Figma](https://www.figma.com/design/SVWKyKEJy6Qr4HT1zM9Cyf/Untitled?node-id=0-1&t=2DspEWQMCY2Pow9Y-1).

## Technologies

- Backend: Laravel 12, PHP 8.2+, MySQL, Eloquent, JWT authentication.
- Frontend: React 19, Vite, React Router, Tailwind CSS, Recharts, Axios.
- Supporting services: SMTP mail, Laravel queues/cache, local or S3-compatible file storage, configurable AI providers.

## Features

- Registration, login, JWT authentication, email verification, password reset, and profile management.
- RBAC for Admin, Manager, IT Support, and Employee users.
- Ticket creation, assignment, status workflow, priorities, comments, internal notes, attachments, and history.
- Notifications, dashboards, activity logs, reports, PDF/Excel exports, and knowledge-base search.
- AI ticket summaries, priority recommendations, troubleshooting, knowledge-base assistant, and chatbot.

## Project layout

```text
backend/   Laravel API
frontend/  React/Vite application
```

## Installation

Install PHP dependencies in `backend` and JavaScript dependencies in `frontend`:

```text
cd backend
composer install

cd ../frontend
npm install
```

## Backend setup

Copy `backend/.env.example` to `backend/.env`, then provide local values. Generate an application key and JWT secret for a new local installation:

```text
cd backend
php artisan key:generate
php artisan jwt:secret
```

Configure the database, mail, storage, and AI provider in `backend/.env`. AI provider keys are backend-only and must never be placed in the frontend.

## Frontend setup

Copy `frontend/.env.example` to `frontend/.env` and set:

```text
VITE_API_URL=http://127.0.0.1:8000/api
```

The Vite proxy also targets the local Laravel server during development. Production builds must use the deployed API origin through `VITE_API_URL`.

## Database setup

The repository includes a baseline migration for the current core application tables plus Laravel migrations for supporting and incremental tables. Department support was removed from the current runtime, so a fresh database intentionally does not include a `departments` table. Validate migrations on a disposable database before production and do not run `migrate:fresh` against any database.

For an existing local database, inspect migration state with:

```text
php artisan migrate:status
```

## Running locally

Start the API from `backend`:

```text
php artisan serve
```

Start the frontend from `frontend`:

```text
npm run dev
```

The frontend development server uses the Vite proxy for `/api` requests.

## Testing

Backend tests:

```text
cd backend
php artisan test
```

Frontend lint and production build:

```text
cd frontend
npm run lint
npm run build
```

## Production deployment

Read [DEPLOYMENT.md](DEPLOYMENT.md) before deployment. Production requires HTTPS, environment-provided secrets, MySQL, configured SMTP, durable attachment storage, exact CORS origins, a queue worker when using queued jobs, and a successful migration plan. Deployment has not been performed.

## AI architecture

AI requests originate in Laravel services under `backend/app/Services/Ai`. Provider selection, model names, timeouts, and credentials are read from `backend/config/ai.php` and environment variables. React calls authenticated Laravel endpoints; it never receives provider API keys.

## User roles

- Admin: full administration, users, tickets, reports, activity, notifications, and AI tools.
- Manager: owned-ticket oversight, reports, notifications, and AI tools.
- IT Support: assigned-ticket workflow, knowledge base, reports, notifications, and AI tools.
- Employee: create and track personal tickets, reports, notifications, and AI chatbot access.