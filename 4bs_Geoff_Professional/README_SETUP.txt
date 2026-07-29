# 4BS Geoff Professional

A Laravel 12 automotive service system with a React/TypeScript frontend for AI support, admin-assisted live chat, profile photo capture/upload, and Driver.js onboarding.

## Main features

- Secure admin and client authentication with role middleware and session regeneration
- Email verification and password-reset codes with expiration, hashing, attempt limits, and rate limiting
- Appointment booking with active-slot collision protection and validated status transitions
- Inventory deductions protected by database transactions and row locks
- Persistent AI conversations visible to administrators
- Client-requested live-chat handoff, atomic admin claiming, admin replies, and retained audit history
- Client profile editing with image upload or browser-camera capture
- Driver.js tutorial for first-time client users
- Feedback, mechanics, services, archive, and analytics modules
- Laravel migrations, demo seed data, PHPUnit feature tests, and Vite React/TSX assets

## Requirements

- PHP 8.2 or newer
- Composer 2
- Node.js 20 or newer and npm
- MySQL 8 or MariaDB with compatible JSON/foreign-key support
- SMTP credentials for verification/reset emails
- Optional Gemini API key for AI-generated responses

## Fresh installation

```bash
composer install
copy .env.example .env
php artisan key:generate
```

On macOS/Linux, use `cp .env.example .env` instead of `copy`.

Create the database named `geoff_prado_professional_2026`, update the database credentials in `.env`, then run:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan optimize:clear
php artisan serve
```

Open `http://127.0.0.1:8000`.

For frontend development, use `npm run dev` in a second terminal instead of `npm run build`.

## Existing installation upgrade

1. Back up the database and uploaded files.
2. Replace the application files.
3. Review `.env` against `.env.example`.
4. Run:

```bash
composer install
php artisan migrate
php artisan storage:link
npm install
npm run build
php artisan optimize:clear
php artisan test
```

The included migration checks for existing tables, columns, and indexes so it can upgrade the supplied SQL-based installation. A refreshed full import remains available at `database/geoff_prado_professional_2026_import.sql`.

## Demo accounts

- Admin: `admin@4bs.test` / `admin123`
- Client: `client@4bs.test` / `client123`

Change or remove demo credentials before any real deployment.

## Mail configuration

Set these values in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="4BS Geoff Professional"
```

Use an app password or a dedicated transactional mail provider. Do not use a normal personal-account password.

## Gemini configuration

```dotenv
GEMINI_API_KEY=your_key
GEMINI_MODEL=gemini-2.5-flash
```

Without a key, the system uses a restricted inventory-aware fallback response. AI responses are stored in the conversation history so administrators can review them.

## Live support behavior

1. A client chats with the AI assistant.
2. Every user and AI message is stored in `chat_messages`.
3. The client selects **Request live admin**.
4. The conversation changes to `waiting_for_admin` and appears at the top of the admin inbox.
5. One admin claims it using a database lock; other admins cannot take the same active session.
6. While live support is active, the AI stops replying and client messages go to the assigned admin.
7. The admin can end live support and return the client to AI mode, or close the conversation.

The current implementation uses short-interval polling for reliable near-real-time messaging without requiring a WebSocket server. For very high concurrency, Laravel Reverb or another WebSocket service can replace polling while keeping the same database workflow.

## Profile photo and camera

Run `php artisan storage:link`. The profile page accepts JPG, PNG, and WebP images up to 5 MB. Camera capture requires browser permission and HTTPS in production; localhost is normally allowed during development.

## Testing and release checks

Run before every release:

```bash
php artisan test
npm run typecheck
npm run build
php artisan route:list
php artisan config:cache
php artisan view:cache
```

Also manually test:

- Client registration, email delivery, expiry, and incorrect-code limits
- Password reset and neutral responses for unknown email addresses
- Admin/client authorization boundaries
- Duplicate appointment attempts from two browser sessions
- Concurrent inventory deductions
- AI chat, live-support request, admin claim, admin reply, end, and close
- Profile upload and camera capture on desktop and mobile
- First-user tutorial and responsive layout

## Production checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Serve only over HTTPS and set `SESSION_SECURE_COOKIE=true`
- Use strong database credentials and a least-privilege database user
- Remove demo accounts or rotate their passwords
- Configure backups for the database and `storage/app/public`
- Add centralized error monitoring, uptime checks, and log retention
- Define privacy, chat-retention, and employee-access policies
- Review AI responses and safety wording with the business owner
- Add a queue worker if mail or AI requests are moved to queued jobs

See `AUDIT_REPORT.md` for the detailed audit and remaining risks.
