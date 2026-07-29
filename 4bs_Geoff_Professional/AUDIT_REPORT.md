# 4BS Geoff Professional — Application Audit

**Audit date:** July 29, 2026  
**Scope:** Supplied Laravel project, database schema, authentication, booking, inventory, AI assistant, admin operations, frontend design, onboarding, and deployment readiness.

## Executive assessment

The supplied project was a useful prototype but was not safe to deploy as a company system. It contained no persistent conversation model, no administrator takeover flow, no React/TypeScript build, no onboarding or profile module, minimal validation, no standard Laravel authentication setup, no migrations or automated tests, and most backend logic lived in one route file.

The revised project implements the requested core workflow and removes the highest-risk defects. It is substantially closer to a production baseline, but no honest audit can guarantee that any application has “no room for error.” Final acceptance still requires dependency installation, automated tests, browser testing, SMTP/Gemini testing, load testing, and deployment-hardening in the target environment.

## Findings and remediation

| Severity | Original issue | Risk | Remediation in revised project |
|---|---|---|---|
| Critical | Custom `session('user_id')` authorization and route helper | Session fixation, inconsistent access control, difficult testing | Laravel `Auth`, session regeneration, `auth` middleware, and explicit `role` middleware |
| Critical | No saved AI/user conversations | Admin could not review AI responses or assist users | `chat_conversations` and `chat_messages` tables with complete transcripts |
| Critical | Chat used `innerHTML` with user/AI content | Stored/reflected XSS risk | React rendering, which escapes message text by default; plaintext API responses |
| Critical | No live-support handoff | Requested business workflow was missing | Waiting, live, AI, and closed states; request, claim, reply, end, and close actions |
| Critical | Verification/reset codes stored in plaintext session with no expiry | Code theft/reuse and unlimited guessing | Hashed codes, 10-minute expiry, five-attempt limit, and endpoint/email/IP rate limiting |
| High | Login did not regenerate the session ID | Session fixation | Session regeneration after successful login and invalidation on logout |
| High | Logout used GET and flushed the whole session | CSRF/logout abuse and fragile behavior | CSRF-protected POST logout |
| High | SMTP exception details were shown to users | Secret/system information disclosure | Generic user error plus server-side logging |
| High | Appointment database unique key blocked reuse of cancelled slots | Legitimate bookings could remain permanently blocked | Status-aware application check plus mechanic-row locking and a nonunique composite index |
| High | Appointment check and insert were not concurrency-safe | Two simultaneous requests could double book | Transaction and `lockForUpdate()` on the mechanic row |
| High | Inventory read/update was not atomic | Overselling and negative stock under concurrent requests | Transaction, product-row lock, validation, and automatic availability status |
| High | Admin could complete pending jobs directly | Invalid workflow states | Only approved appointments can be completed; UI and backend both enforce it |
| High | Models used unrestricted mass assignment | Future privilege/data overwrite risk | Explicit `$fillable` fields |
| High | No profile or photo controls | Requested feature missing | Profile edit plus validated upload and camera capture |
| High | No first-user tutorial | New-user usability requirement missing | Driver.js tour, server-side completion timestamp, and one-time launch |
| High | `APP_DEBUG=true` in example environment | Sensitive stack traces in accidental deployments | Debug disabled by default and production checklist added |
| High | No migrations or tests | Nonreproducible database and regressions | Full guarded migrations, upgrade migration, seeders, PHPUnit configuration, and feature tests |
| Medium | All backend logic lived in `routes/web.php` | Difficult maintenance and testing | Dedicated controllers for auth, booking, feedback, admin operations, chat, profile, and analytics |
| Medium | No authentication configuration | Laravel Auth could not be used reliably | Added `config/auth.php` and an Authenticatable user model |
| Medium | Weak password policy | Easily guessed accounts | Eight-character minimum with letters, mixed case, number, and confirmation |
| Medium | Password reset revealed whether email existed | User enumeration | Neutral response for existing and unknown emails |
| Medium | Limited validation and unbounded text | Data quality and resource abuse | Length, type, range, existence, image, and state validation |
| Medium | Repeated sidebars and inconsistent navigation | Design drift | Shared admin/client navigation partials and active states |
| Medium | Prototype chat and profile design | Poor company presentation | Responsive React UI, professional states, accessible labels, empty/error/loading states |
| Medium | Wildcard CORS configuration | Unnecessary cross-origin exposure | Origin list restricted through environment configuration |
| Medium | Unencrypted session payload configuration | Higher impact if session storage is read | Session encryption enabled by default |

## Revised architecture

### Backend

- Laravel 12 web application
- Standard session authentication using the `web` guard
- Role middleware for strict client/admin separation
- Controllers separated by business capability
- Database transactions and row locks for critical state changes
- Persistent chat and message audit trail
- Gemini service isolated in `GarageAssistant`
- Generic external-service errors logged server-side
- Standard migrations, seeders, and feature tests

### Frontend

- React and TypeScript components built through Vite
- `ChatPage.tsx` for client AI/live support
- `AdminLiveChatPage.tsx` for transcript review and administrator assistance
- `ProfilePage.tsx` for contact details, upload, and browser-camera capture
- Driver.js onboarding launched for new client accounts
- Shared professional CSS, responsive navigation, status indicators, empty states, and accessible form controls

## Live-chat controls

- Every message records conversation, sender type, optional sender ID, body, and timestamps.
- Admins can view AI-only conversations before a live request, satisfying the transcript-visibility requirement.
- A client request moves the existing conversation to `waiting_for_admin`; it does not create a separate transcript.
- Claiming is performed inside a transaction with a row lock.
- A conversation already claimed by another administrator returns a conflict response.
- AI replies stop while the conversation is waiting or live.
- Only the assigned admin can send replies or end a live session.
- Ending live support returns the same conversation to AI mode and preserves history.
- Closing preserves the transcript for audit review.

## Security controls added

- CSRF protection on all state-changing web requests
- Authentication/role middleware
- Session ID regeneration and secure logout
- Rate limiting on login, verification, reset, avatar, AI chat, and live-support actions
- Hashed short-lived verification/reset codes
- Neutral password-reset enumeration response
- Password hashing through the Eloquent hashed cast
- Output escaping through Blade and React
- File type/size validation and controlled public storage paths
- Restricted CORS origins
- Transactions and pessimistic row locks
- State-transition validation
- Production debug disabled in the example environment

## Automated checks included

- PHP syntax validation across application, configuration, routes, migrations, seeders, and tests
- Strict source-level TypeScript check of the authored TS/TSX using temporary dependency declarations; the installed-package typecheck remains a release gate
- Feature test for client live-support request, admin claim, admin reply, and persistence
- Feature test proving clients cannot access admin conversations
- Feature test for validated profile-photo storage

## Remaining risks and required acceptance work

1. **Dependency/runtime verification:** The audit environment did not have Composer and could not access external package registries, so `composer install`, the real Vite build, Artisan route boot, and PHPUnit execution must be run on the target machine.
2. **Real-time transport:** The current live chat uses reliable 1.5–2.5 second polling. For large concurrent traffic or instant delivery requirements, deploy Laravel Reverb/Pusher and broadcast the same events.
3. **AI safety:** AI output is probabilistic. The prompt contains safety boundaries and the interface shows a disclaimer, but the business must define allowed advice, escalation rules, and review procedures.
4. **Privacy and retention:** Company management must define who can view transcripts, how long chat/photo data is retained, and how deletion/access requests are handled.
5. **Operational controls:** Production still needs HTTPS, backups, monitoring, alerting, log retention, secret management, least-privilege database credentials, and incident procedures.
6. **Load/concurrency testing:** Transaction logic is present, but simultaneous booking, claiming, and stock tests should be run against the exact production database configuration.
7. **Browser/device acceptance:** Camera permission, mobile layouts, SMTP delivery, and the Driver.js tour must be tested on the supported browsers and devices.
8. **Authorization expansion:** If multiple admin roles are needed later, replace the single `admin` role with granular permissions/policies.

## Release gate

Do not call the system production-ready until all commands below pass in the deployment environment:

```bash
composer install
php artisan migrate --seed
php artisan storage:link
npm install
npm run typecheck
npm run build
php artisan test
php artisan route:list
```

Then complete manual end-to-end tests for registration, reset, booking collisions, stock concurrency, AI chat, live handoff, admin reply, camera capture, tutorial completion, and mobile responsiveness.
