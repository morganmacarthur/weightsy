# Architecture

## Recommended Stack

- API: TypeScript (NestJS/Fastify) or Go.
- Messaging: Twilio (SMS) and optional push provider.
- DB: Postgres.
- Job scheduler: queue + cron worker for reminders.

## Core Components

- `identity-service`: phone verification and consent state.
- `reminder-service`: reminder schedule and delivery.
- `ingest-service`: parses inbound replies.
- `metrics-service`: stores normalized daily metrics.
- `insights-service`: trend summaries and chart data.

## Data Model (High Level)

- `users` (phone, timezone, consent flags)
- `daily_entries` (date, metric type, value fields, source)
- `messages` (sent/inbound, parsing status)
- `reminder_rules`

## Parsing Rules (MVP)

- Integer only => weight.
- `int/int` => systolic/diastolic.
- `int%` => body fat.
- Reject ambiguous input with clarifying response.

