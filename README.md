# Weightsy

Weightsy is being rebuilt as a message-first Laravel app for tracking three kinds of health check-ins on one timeline:

- weight like `123`
- blood pressure like `120/70`
- body fat like `14.0%`

The normal product loop is:

1. A user sends their first check-in to `update@weightsy.com`.
2. We create a pending account from the sending address and record that first data point.
3. We send a confirmation email with links to confirm reminders, change the reminder time, or unsubscribe.
4. Only confirmed users get recurring reminder emails.
5. If the user visits the site, they log in with one-time passwords or magic links and can review or edit their timeline.

## Current foundation

This restart includes:

- Laravel 13 with SQLite
- a product-specific schema for users, contact points, messages, check-ins, reminder schedules, and login tokens
- an inbound check-in endpoint at `POST /inbound/checkins`
- an IMAP polling command at `php artisan weightsy:imap:poll`
- a pending-vs-confirmed reminder onboarding flow with signed confirm/settings/unsubscribe links
- parsing for the three supported check-in message formats
- a new landing page that reflects the actual product direction

## Local setup

```bash
composer install
php artisan migrate:fresh
php artisan test
php artisan weightsy:imap:poll --limit=10
```

## Inbound payload

`POST /inbound/checkins`

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
4. send a confirmation or help reply
5. mark the source message seen, and optionally deleted

## Notes

- The Laravel product app is intended to live at `https://weightsy.com/app/`, leaving the site root and legacy blog URLs untouched.
- The current working inbox/check-in address is `update@weightsy.com`.
- The previous non-Laravel iteration has been preserved locally in `_archive/2026-04-03-pre-laravel-restart`.
- Postmark is still the likely deliverability assist later, but this foundation keeps inbound and outbound message records local so we can self-host most of the workflow.
