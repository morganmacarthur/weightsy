# Weightsy Milepost

This repo has been reset from the old static/mobile experiment into a Laravel 13 + SQLite application focused on an email-first MVP.

## What works

- The app is intended to run at `https://weightsy.com/app/`
- Inbound email is fetched by IMAP polling
- Supported first-checkin formats:
  - `123`
  - `120/70`
  - `14.0%`
- A first inbound message creates a pending user and records the check-in
- First-contact onboarding email is sent through Postmark
- Signed links support:
  - confirm reminders
  - edit reminder time
  - unsubscribe
- Confirmed users can receive recurring reminder emails
- Bounce / mailer-daemon messages are skipped by the poller

## Production assumptions

- Root site and legacy blog stay at `weightsy.com/`
- Laravel product routes live under `/app`
- `APP_URL=https://weightsy.com`
- Route prefixing adds `/app`
- Working inbox / sender identity is `update@weightsy.com`
- Postmark is used for first-contact onboarding messages
- Regular reminders can use normal SMTP

## Important commands

```bash
php artisan migrate --force
php artisan weightsy:imap:poll --limit=10
php artisan weightsy:reminders:send --limit=50
php artisan weightsy:postmark:test someone@example.com
php artisan test
```

## Suggested cron

```bash
* * * * * cd /home/nhlfunco/weightsy.com/app && php artisan weightsy:imap:poll --limit=10 >/dev/null 2>&1
*/10 * * * * cd /home/nhlfunco/weightsy.com/app && php artisan weightsy:reminders:send --limit=50 >/dev/null 2>&1
```

## Deployment shape

- Public root stays static / legacy
- Laravel app lives in `/home/nhlfunco/weightsy.com/app`
- `/app` rewrites into Laravel `public/`

## Recent end-to-end validation

- Real external Gmail signup processed successfully
- Postmark onboarding email delivered successfully
- Production confirmation page worked successfully

## Next likely work

- Tighten cron / operational logging
- Add OTP / magic-link login for timeline access
- Add graph generation for confirmation messages
- Add manual add/edit timeline UI
- Build lightweight admin/debug visibility
