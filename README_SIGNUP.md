# Signup Endpoint Notes

## Files

- `signup.php` - receives homepage signup form submissions
- `signup.sqlite` - local SQLite storage (ignored by git)

## Stored Fields

Table: `signup_requests`

- `id` (autoincrement)
- `email_hash` (nullable)
- `phone_hash` (nullable)
- `diet_style`
- `timezone`
- `ip_hash`
- `user_agent_hash`
- `source_path`
- `created_at` (unix timestamp)

No raw email/phone values are stored.

## Hashing

`signup.php` uses:

- `hash_hmac('sha256', value, pepper)`

Set a production pepper via env var:

- `WEIGHTSY_HASH_PEPPER`

If this env var is missing, code falls back to a dev placeholder pepper.
Do not use fallback in production.

## Local Test

1. Open `http://weightsy.test/` (or your local host mapping).
2. Submit the signup form.
3. Verify DB rows:
   - open `signup.sqlite` with SQLite browser, or
   - use a quick script/query tool.

## Laragon/Apache Env Var (example)

Set environment variable in Apache virtual host config:

```apache
SetEnv WEIGHTSY_HASH_PEPPER your-long-random-secret
```

Then restart Apache.

