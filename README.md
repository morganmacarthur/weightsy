# Weightsy

Weightsy is being rebuilt as a message-first Laravel app for tracking three kinds of health check-ins on one timeline:

- weight like `123`
- blood pressure like `120/70`
- body fat like `14.0%`

The normal product loop is:

1. A user sends their first check-in to `update@weightsy.com`.
2. We create a pending account from the sending address and record that first data point.
3. We send a confirmation email with links to confirm reminders, change the reminder time, or unsubscribe.
4. Only confirmed users get recurring reminder emails (typically one reminder per day). Each reminder includes a magic link to the timeline so users can review progress without a separate “recorded” reply email after every check-in.
5. If the user visits the site, they log in with one-time passwords or magic links and can review or edit their timeline.

## Current foundation

This restart includes:

- Laravel 13 with SQLite
- a product-specific schema for users, contact points, messages, check-ins, reminder schedules, and login tokens
- an inbound check-in endpoint at `POST /app/inbound/checkins` (CSRF-exempt for external providers)
- an IMAP polling command at `php artisan weightsy:imap:poll`
- a pending-vs-confirmed reminder onboarding flow with signed confirm/settings/unsubscribe links
- parsing for the three supported check-in message formats
- a new landing page that reflects the actual product direction
- a single outbound mail path through Laravel mail, which can be backed by SES for onboarding, replies, and reminders

## Local setup

```bash
composer install
php artisan migrate:fresh
php artisan test
php artisan weightsy:imap:poll --limit=10
```

## Inbound payload

`POST /app/inbound/checkins`

Include a stable `external_id` from your mail provider when available so retries do not create duplicate check-ins.

```json
{
  "from": "5551234567@vtext.com",
  "channel": "mms",
  "text": "120/70",
  "received_at": "2026-04-03T08:30:00-07:00"
}
```

Accepted messages:

- `123`
- `120/70`
- `14.0%`

## IMAP polling

Weightsy can poll unread messages directly from an inbox instead of relying on an inbound webhook.

Set these env vars:

- `WEIGHTSY_IMAP_HOST`
- `WEIGHTSY_IMAP_PORT`
- `WEIGHTSY_IMAP_ENCRYPTION`
- `WEIGHTSY_IMAP_MAILBOX`
- `WEIGHTSY_IMAP_USERNAME`
- `WEIGHTSY_IMAP_PASSWORD`
- `WEIGHTSY_IMAP_DELETE_AFTER_PROCESSING`

Then run:

```bash
php artisan weightsy:imap:poll
```

The command will:

1. fetch unread IMAP messages
2. parse the sender, subject, date, and plain-text body
3. record valid check-ins
4. send onboarding email for a first-time sender, or a help reply when the message is not recognized (existing users do not get a “recorded” auto-reply; the daily reminder carries the timeline link)
5. mark the source message seen, and optionally deleted

## Notes

- **Reminder failures** (no recipient, mail transport error): the schedule’s `next_run_at` is deferred to the next daily slot so cron does not hammer every run. `reminder_failure_count`, `last_reminder_failure_at`, and `last_reminder_failure_reason` are updated on the `reminder_schedules` row (reset after a successful send). Check `storage/logs/reminders-*.log` for structured warnings, and query `messages` where `metadata->category` is `reminder_failed` for a durable audit trail in the database.
- The Laravel product app is intended to live at `https://weightsy.com/app/`, leaving the site root and legacy blog URLs untouched.
- The current working inbox/check-in address is `update@weightsy.com`.
- The previous non-Laravel iteration has been preserved locally in `_archive/2026-04-03-pre-laravel-restart`.
- Inbound and outbound message records are kept locally in the app database for debugging and history.

## Deployment sync rule

For FTP deploys, the safest rule is:

- upload everything in the Weightsy app folder
- do not overwrite:
  - `.env`
  - `.htaccess`
  - `public/.htaccess`
  - `database/database.sqlite`

That avoids losing server-specific config while also avoiding partial code deploys that leave reminder or onboarding logic out of sync.
