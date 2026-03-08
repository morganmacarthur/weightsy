# Data Schema (Draft v0)

## User Identity

| Field | Purpose | Notes |
| --- | --- | --- |
| `id` | Internal key | UUID or equivalent |
| `phone_hash` | Account lookup | Hash of normalized phone number |
| `timezone` | Correct day bucketing | User-configurable |
| `diet_style` | Aggregate leaderboard grouping | Optional |
| `created_at` | Auditing | UTC timestamp |

## Daily Entries

| Field | Purpose | Notes |
| --- | --- | --- |
| `id` | Internal key | UUID or equivalent |
| `user_id` | Ownership link | FK to users |
| `entry_date` | Logical date of measurement | Timezone-aware |
| `weight` | Body weight | Numeric |
| `bp_sys` | Blood pressure systolic | Integer |
| `bp_dia` | Blood pressure diastolic | Integer |
| `body_fat_pct` | Body fat percentage | Numeric |
| `created_at` | Auditing | UTC timestamp |

## Leaderboard Aggregates

| Field | Purpose | Notes |
| --- | --- | --- |
| `date` | Aggregate date key | UTC or reporting timezone |
| `diet_style` | Group dimension | Optional from user profile |
| `n_users` | Sample size | Count only |
| `avg_weight_delta_7d` | Group trend | Aggregate only |
| `avg_bp_sys_7d` | Group trend | Aggregate only |
| `avg_body_fat_delta_7d` | Group trend | Aggregate only |

